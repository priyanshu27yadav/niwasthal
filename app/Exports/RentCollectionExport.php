<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RentCollectionExport implements FromCollection, WithHeadings
{
    public function __construct(protected Collection $records) {}

    public function headings(): array
    {
        return ['Student Name', 'Room', 'Month/Year', 'Rent Amount', 'Late Fee', 'Total Amount', 'Paid Amount', 'Outstanding', 'Status'];
    }

    public function collection(): Collection
    {
        return $this->records->map(function ($r) {
            $monthYear = date('M', mktime(0, 0, 0, $r->month, 1)) . ' ' . $r->year;
            return [
                $r->student_name,
                $r->room_number ?? '-',
                $monthYear,
                $r->rent_amount,
                $r->late_fee,
                $r->total_amount,
                $r->paid_amount,
                $r->outstanding,
                ucfirst($r->status),
            ];
        });
    }
}
