<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PdsCampaignExport implements FromArray, WithHeadings, WithEvents
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
                $row['name'],
                $row['campaign'],
                $row['total_agent'],
                $row['data_size'] ?? '0',
                $row['data_utilize'] ?? '0',
                $row['still_thinking'] ?? '0',
                $row['disagree'] ?? '0',
                $row['incoming'] ?? '0',
                $row['callback'] ?? '0',
                $row['uncontacted'] ?? '0',
                $row['abandoned'] ?? '0',
                $row['unutilize'] ?? '0',
                $row['duration_pds'] ?? '0',
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            [
                'PDS Name','Marketing Campaign','Agent Ready','Data Size', 'Data Utilize',
                'Data Contacted','','','',
                'Uncontacted', 'Abandon', 'Unutilize PDS', 'Duration PDS'
            ],
            [
                '','','','', '',
                'Still Thinking','Disagree','Incoming','Callback',
                '', '', '', ''
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function($event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                // Merge header cells sesuai colspan/rowspan
                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:C2');
                $sheet->mergeCells('D1:D2');
                $sheet->mergeCells('E1:E2');
                $sheet->mergeCells('F1:I1');
                $sheet->mergeCells('J1:J2');
                $sheet->mergeCells('K1:K2');
                $sheet->mergeCells('L1:L2');
                $sheet->mergeCells('M1:M2');

                // Alignment header
                $sheet->getStyle('A1:M2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Bold header
                $sheet->getStyle('A1:M2')->getFont()->setBold(true);
            }
        ];
    }
}
