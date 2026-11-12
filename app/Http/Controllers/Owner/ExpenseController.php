<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $owner = Auth::user();
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        $query = Expense::with('property')->whereIn('property_id', $propertyIds);

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('month')) {
            $query->whereMonth('expense_date', $request->month);
        }
        if ($request->filled('year')) {
            $query->whereYear('expense_date', $request->year);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%");
            });
        }

        $expenses   = $query->orderByDesc('expense_date')->paginate(15)->withQueryString();
        $properties = Property::where('owner_id', $owner->id)->get();
        $categories = $this->expenseCategories();

        $monthlyTotal = Expense::whereIn('property_id', $propertyIds)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        $yearlyTotal = Expense::whereIn('property_id', $propertyIds)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        return view('owner.expenses.index', compact(
            'expenses',
            'properties',
            'categories',
            'monthlyTotal',
            'yearlyTotal'
        ));
    }

    public function create()
    {
        $owner      = Auth::user();
        $properties = Property::where('owner_id', $owner->id)->get();
        $categories = $this->expenseCategories();

        return view('owner.expenses.create', compact('properties', 'categories'));
    }

    public function store(Request $request)
    {
        $owner = Auth::user();

        $validated = $request->validate([
            'property_id'  => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($owner) {
                    if (!Property::where('id', $value)->where('owner_id', $owner->id)->exists()) {
                        $fail('The selected property does not belong to you.');
                    }
                },
            ],
            'category'     => 'required|in:' . implode(',', $this->expenseCategories()),
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'vendor'       => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:2000',
            'receipt'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        Expense::create([
            'property_id'  => $validated['property_id'],
            'category'     => $validated['category'],
            'description'  => $validated['description'],
            'amount'       => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'vendor'       => $validated['vendor'] ?? null,
            'receipt_path' => $receiptPath,
            'notes'        => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('owner.expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    public function edit(Expense $expense)
    {
        $this->authorizeOwnership($expense->property);

        $owner      = Auth::user();
        $properties = Property::where('owner_id', $owner->id)->get();
        $categories = $this->expenseCategories();

        return view('owner.expenses.edit', compact('expense', 'properties', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $owner = Auth::user();
        $this->authorizeOwnership($expense->property);

        $validated = $request->validate([
            'property_id'  => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($owner) {
                    if (!Property::where('id', $value)->where('owner_id', $owner->id)->exists()) {
                        $fail('The selected property does not belong to you.');
                    }
                },
            ],
            'category'     => 'required|in:' . implode(',', $this->expenseCategories()),
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'vendor'       => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:2000',
            'receipt'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $receiptPath = $expense->receipt_path;
        if ($request->hasFile('receipt')) {
            if ($receiptPath && Storage::disk('public')->exists($receiptPath)) {
                Storage::disk('public')->delete($receiptPath);
            }
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        $expense->update([
            'property_id'  => $validated['property_id'],
            'category'     => $validated['category'],
            'description'  => $validated['description'],
            'amount'       => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'vendor'       => $validated['vendor'] ?? null,
            'receipt_path' => $receiptPath,
            'notes'        => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('owner.expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorizeOwnership($expense->property);
        $expense->delete();

        return redirect()
            ->route('owner.expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }

    private function expenseCategories(): array
    {
        return ['electricity', 'water', 'maintenance', 'repairs', 'salary', 'cleaning', 'internet', 'taxes', 'miscellaneous', 'other'];
    }

    private function authorizeOwnership(Property $property): void
    {
        if ($property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
