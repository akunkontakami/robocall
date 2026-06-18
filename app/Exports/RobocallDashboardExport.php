<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RobocallDashboardExport implements FromArray, WithHeadings, WithEvents
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return array_map(function ($row) {
            return [
                $row['data_size'] ?? '0',
                $row['total_call'] ?? '0',
                $row['redial'] ?? '0',
                $row['duration'] ?? '0',
                $row['answer'] ?? '0',
                $row['no_answer'] ?? '0',
                $row['abandon'] ?? '0',
                $row['answer_rate'] ?? '0',
                $row['no_answer_rate'] ?? '0',
                $row['abandon_rate'] ?? '0',
                $row['avg'],
                $row['total_duration'],
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            [
                'Agent Ready', 'Data Size', 'Total Call', 'Redial', 'Duration', 'Performance Calls', '', '', 'Percentage Calls', '', '', 'Average Handling Time (AHT)', 'Duration Call', 'Idle Time',
            ],
            [
                '', '', '', '', '', 'Answer', 'No Answer', 'Abandon', 'Answer Rate', 'No Answer Rate', 'Abandon Rate', '', '', '',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function ($event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                // Merge header cells sesuai colspan/rowspan
                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:C2');
                $sheet->mergeCells('D1:D2');
                $sheet->mergeCells('E1:E2');
                $sheet->mergeCells('F1:H1');
                $sheet->mergeCells('I1:K1');
                $sheet->mergeCells('L1:L2');
                $sheet->mergeCells('M1:M2');
                $sheet->mergeCells('N1:N2');

                // Alignment header
                $sheet->getStyle('A1:N2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Bold header
                $sheet->getStyle('A1:N2')->getFont()->setBold(true);
            },
        ];
    }
}
