<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportRobocallExport implements FromArray, WithHeadings
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
                $row['campaign_id'] ?? '',
                $row['customer_number'] ?? '',
                $row['customer_name'] ?? '',
                $row['dialed_number'] ?? '',
                $row['dial_time'] ?? '',
                $row['dial_status'] ?? '',
                $row['call_status'] ?? '',
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            'Marketing Campaign',
            'Customer Number',
            'Name',
            'Phone Number',
            'Call Date',
            'Dial Status',
            'Call Status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {
                $sheet = $event->sheet->getDelegate();

                $lastColumn = 'G';

                $sheet->getStyle("A1:{$lastColumn}1")
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle("A1:{$lastColumn}1")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A1:{$lastColumn}1")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                foreach (range('A', $lastColumn) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}
