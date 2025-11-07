<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PdsTrackingExport implements FromArray, WithHeadings, WithEvents
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
                $row['calls'] ?? '0',
                $row['utilize_call_ratio'] ?? '0',
                $row['utilize_percentage'] ?? '0%',
                $row['unutilize'] ?? '0',
                $row['unutilize_percentage'] ?? '0%',
                $row['duration_pds'] ?? '0',
                $row['contacted'] ?? '0',
                $row['contacted'] ?? '0',
                $row['contacted_percentage'] ?? '0%',
                $row['uncontacted'] ?? '0',
                $row['uncontacted'] ?? '0',
                $row['uncontacted_percentage'] ?? '0%',
                $row['abandoned'] ?? '0',
                $row['abandoned_rate'] ?? '0',
                $row['still_thinking'] ?? '0',
                $row['disagree'] ?? '0',
                $row['incoming'] ?? '0',
                $row['callback'] ?? '0',
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            [
                'PDS Name','Marketing Campaign','Agent Ready','Data Size PDS',
                'Utilize PDS','','','',
                'Unutilize PDS','',
                'Duration',
                'Contacted','','',
                'UnContacted','','',
                'Abandon','',
                'Call Status','','',''
            ],
            [
                '', '', '', '',
                'Data','Calls','Call Ratio','% Utilize',
                'Data','% Unutilize',
                '',
                'Data','Calls','%',
                'Data','Calls','%',
                'Data','%',
                'Still Thinking','Disagree','Incoming','Callback'
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
                $sheet->mergeCells('E1:H1'); // Utilize PDS
                $sheet->mergeCells('I1:J1'); // Unutilize PDS
                $sheet->mergeCells('K1:K2');
                $sheet->mergeCells('L1:N1'); // Contacted
                $sheet->mergeCells('O1:Q1'); // UnContacted
                $sheet->mergeCells('R1:S1'); // Abandon
                $sheet->mergeCells('T1:W1'); // Call Status

                // Alignment header
                $sheet->getStyle('A1:W2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Bold header
                $sheet->getStyle('A1:W2')->getFont()->setBold(true);
            }
        ];
    }
}
