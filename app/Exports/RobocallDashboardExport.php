<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class RobocallDashboardExport implements FromArray, WithHeadings, WithEvents
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return array_map(function ($row) {
            return [
                $row['data_size'] ?? 0,
                $row['count'] ?? 0,
                $row['contacted'] ?? 0,
                $row['failed'] ?? 0,
                $row['dialed'] ?? 0,
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            'Total Datasize',
            'Total Calls',
            'Call Answered',
            'Call Not Answered',
            'Call in Progress',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:E1')
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle('A1:E1')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
