<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithHeadings
{
    public function __construct(protected Collection $students) {}

    public function headings(): array
    {
        return ['Name', 'Mobile', 'Email', 'Property', 'Room / Bed', 'Joining Date', 'Monthly Rent', 'Status'];
    }

    public function collection(): Collection
    {
        return $this->students->map(function ($s) {
            $roomBed = $s->bed ? ($s->bed->room?->room_number . ' / Bed ' . $s->bed->bed_number) : 'Unassigned';
            return [
                $s->name,
                $s->mobile,
                $s->email ?? '-',
                $s->property?->name ?? '-',
                $roomBed,
                $s->joining_date ? $s->joining_date->format('Y-m-d') : '-',
                $s->monthly_rent,
                ucfirst($s->status),
            ];
        });
    }
}
