<?php

namespace App\Http\Controllers\Owner;

use App\Exports\GenericCollectionExport;
use App\Exports\PendingDuesExport;
use App\Exports\RentCollectionExport;
use App\Exports\StudentsExport;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Room;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        return view('owner.reports.index');
    }

    public function rentCollection(Request $request)
    {
        $owner = Auth::user();
        $month = (int) $request->get('month', date('n'));
        $year  = (int) $request->get('year', date('Y'));

        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        $query = RentRecord::with(['student', 'property'])
            ->whereIn('property_id', $propertyIds)
            ->where('month', $month)
            ->where('year', $year);

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        $records = $query->orderBy('student_name')->get();

        $totalBilled    = $records->sum('total_amount');
        $totalCollected = $records->sum('paid_amount');
        $totalPending   = $records->sum('outstanding');

        $summary = compact('totalBilled', 'totalCollected', 'totalPending', 'month', 'year');
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $format = $request->get('format');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('owner.reports.rent_collection_pdf', compact('records', 'summary', 'monthName'))
                ->setPaper('a4', 'landscape');
            return $pdf->download("rent-collection-{$year}-{$month}.pdf");
        }

        if ($format === 'excel') {
            return Excel::download(
                new RentCollectionExport($records),
                "rent-collection-{$year}-{$month}.xlsx"
            );
        }

        $properties = Property::where('owner_id', $owner->id)->get();
        $months     = collect(range(1, 12))->mapWithKeys(fn($m) => [$m => date('F', mktime(0, 0, 0, $m, 1))]);
        $years      = collect(range(date('Y') - 2, date('Y') + 1))->mapWithKeys(fn($y) => [$y => $y]);

        return view('owner.reports.rent_collection', compact(
            'records',
            'summary',
            'monthName',
            'properties',
            'months',
            'years',
            'month',
            'year'
        ));
    }

    public function pendingDues(Request $request)
    {
        $owner = Auth::user();
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        $query = RentRecord::with(['student', 'property'])
            ->whereIn('property_id', $propertyIds)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('outstanding', '>', 0);

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        $pendingRecords = $query->orderBy('year')->orderBy('month')->get();

        $totalDues     = $pendingRecords->sum('outstanding');
        $unpaidAmount  = $pendingRecords->where('status', 'unpaid')->sum('outstanding');
        $partialAmount = $pendingRecords->where('status', 'partial')->sum('outstanding');

        $summary = compact('totalDues', 'unpaidAmount', 'partialAmount');
        $format = $request->get('format');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('owner.reports.pending_dues_pdf', compact('pendingRecords', 'summary'))
                ->setPaper('a4', 'portrait');
            return $pdf->download('pending-dues-' . date('Y-m-d') . '.pdf');
        }

        if ($format === 'excel') {
            return Excel::download(
                new PendingDuesExport($pendingRecords),
                'pending-dues-' . date('Y-m-d') . '.xlsx'
            );
        }

        $properties = Property::where('owner_id', $owner->id)->get();

        return view('owner.reports.pending_dues', compact(
            'pendingRecords',
            'summary',
            'properties'
        ));
    }

    public function occupancy(Request $request)
    {
        $owner = Auth::user();
        $propertiesQuery = Property::where('owner_id', $owner->id)->with(['rooms.beds', 'rooms.activeStudents']);

        if ($request->filled('property_id')) {
            $propertiesQuery->where('id', $request->property_id);
        }

        $properties = $propertiesQuery->get();

        $occupancyData = $properties->map(function ($property) {
            $occ = Room::summarizeOccupancy($property->rooms);

            return [
                'property'       => $property,
                'total_rooms'    => $property->rooms->count(),
                'total_beds'     => $occ['totalCapacity'],
                'occupied_beds'  => $occ['occupiedCapacity'],
                'vacant_beds'    => $occ['availableCapacity'],
                'occupancy_rate' => $occ['occupancyPercent'],
            ];
        });

        $totalBeds    = $occupancyData->sum('total_beds');
        $occupiedBeds = $occupancyData->sum('occupied_beds');
        $overallRate  = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0;

        $summary = compact('totalBeds', 'occupiedBeds', 'overallRate');
        $format = $request->get('format');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('owner.reports.occupancy_pdf', compact('occupancyData', 'summary'))
                ->setPaper('a4', 'portrait');
            return $pdf->download('occupancy-' . date('Y-m-d') . '.pdf');
        }

        if ($format === 'excel') {
            $rows = $occupancyData->map(fn($d) => [
                $d['property']->name,
                $d['total_rooms'],
                $d['total_beds'],
                $d['occupied_beds'],
                $d['vacant_beds'],
                $d['occupancy_rate'] . '%',
            ]);

            return Excel::download(
                new GenericCollectionExport($rows, ['Property', 'Total Rooms', 'Total Beds', 'Occupied Beds', 'Vacant Beds', 'Occupancy Rate']),
                'occupancy-' . date('Y-m-d') . '.xlsx'
            );
        }

        $allProperties = Property::where('owner_id', $owner->id)->get();

        return view('owner.reports.occupancy', compact(
            'occupancyData',
            'summary',
            'allProperties'
        ));
    }

    public function expenses(Request $request)
    {
        $owner = Auth::user();
        $month = $request->get('month', 'all');
        $year  = (int) $request->get('year', date('Y'));

        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        $query = Expense::with('property')
            ->whereIn('property_id', $propertyIds)
            ->whereYear('expense_date', $year);

        if ($request->filled('month') && $request->month !== 'all') {
            $query->whereMonth('expense_date', (int) $month);
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        $expenses = $query->orderBy('expense_date')->get();
        $totalAmount = $expenses->sum('amount');

        $categoryBreakdown = $expenses->groupBy('category')->map(function ($items, $category) {
            return [
                'category' => $category,
                'total'    => $items->sum('amount'),
                'count'    => $items->count(),
            ];
        })->sortByDesc('total')->values();

        $propertyBreakdown = $expenses->groupBy('property_id')->map(function ($items) {
            return [
                'property' => $items->first()->property->name ?? 'Unknown',
                'total'    => $items->sum('amount'),
                'count'    => $items->count(),
            ];
        })->sortByDesc('total')->values();

        $summary = compact('totalAmount', 'year', 'month');
        $format = $request->get('format');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('owner.reports.expenses_pdf', compact(
                'expenses',
                'summary',
                'categoryBreakdown',
                'propertyBreakdown'
            ))->setPaper('a4', 'portrait');
            return $pdf->download("expenses-{$year}.pdf");
        }

        if ($format === 'excel') {
            $rows = $expenses->map(fn($e) => [
                $e->expense_date->format('Y-m-d'),
                $e->property->name ?? '-',
                $e->description,
                ucfirst($e->category),
                $e->vendor ?? '-',
                $e->amount,
            ]);

            return Excel::download(
                new GenericCollectionExport($rows, ['Date', 'Property', 'Description', 'Category', 'Vendor', 'Amount']),
                "expenses-{$year}.xlsx"
            );
        }

        $allProperties = Property::where('owner_id', $owner->id)->get();
        $months        = collect(range(1, 12))->mapWithKeys(fn($m) => [$m => date('F', mktime(0, 0, 0, $m, 1))]);
        $years         = collect(range(date('Y') - 3, date('Y') + 1))->mapWithKeys(fn($y) => [$y => $y]);

        return view('owner.reports.expenses', compact(
            'expenses',
            'summary',
            'categoryBreakdown',
            'propertyBreakdown',
            'allProperties',
            'months',
            'years'
        ));
    }

    public function students(Request $request)
    {
        $owner = Auth::user();
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        $query = Student::with(['property', 'bed.room'])
            ->whereIn('property_id', $propertyIds);

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('name')->get();

        $totalActive   = $students->where('status', 'active')->count();
        $totalInactive = $students->where('status', '!=', 'active')->count();

        $summary = compact('totalActive', 'totalInactive');
        $format = $request->get('format');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('owner.reports.students_pdf', compact('students', 'summary'))
                ->setPaper('a4', 'portrait');
            return $pdf->download('students-' . date('Y-m-d') . '.pdf');
        }

        if ($format === 'excel') {
            return Excel::download(
                new StudentsExport($students),
                'students-' . date('Y-m-d') . '.xlsx'
            );
        }

        $allProperties = Property::where('owner_id', $owner->id)->get();

        return view('owner.reports.students', compact(
            'students',
            'summary',
            'allProperties'
        ));
    }
}
