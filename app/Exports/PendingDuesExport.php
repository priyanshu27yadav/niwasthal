<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PendingDuesExport implements FromCollection, WithHeadings
{
    public function __construct(protected Collection $records) {}

    public function headings(): array
    {
        return ['Student Name', 'Mobile', 'Room', 'Month/Year', 'Outstanding', 'Due Date', 'Status'];
    }

    public function collection(): Collection
    {
        return $this->records->map(function ($r) {
            $monthYear = date('M', mktime(0, 0, 0, $r->month, 1)) . ' ' . $r->year;
            return [
                $r->student_name,
                $r->student?->mobile ?? '-',
                $r->room_number ?? '-',
                $monthYear,
                $r->outstanding,
                $r->due_date ? $r->due_date->format('Y-m-d') : '-',
                ucfirst($r->status),
            ];
        });
    }
}
