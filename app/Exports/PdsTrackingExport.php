<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PdsTrackingExport implements FromArray, WithHeadings, WithEvents
{
    private $data, $outbounds;

    public function __construct(array $data, array $outbounds)
    {
        $this->data = $data;
        $this->outbounds = $outbounds;
    }

    public function array(): array
    {
        return array_map(function ($row) {
            $ticketCounts = $row['ticket_status_count'] ?? [];
            $statusColumns = [];
            foreach ($this->outbounds as $status) {
                $name = is_array($status) ? $status['name'] : $status;
                $statusColumns[] = (string) ($ticketCounts[$name] ?? '0');
            }

            return array_merge([
                $row['name'],
                $row['campaign'],
                $row['total_agent'],
                (string) $row['data_size'] ?? '0',
                (string) $row['data_utilize'] ?? '0',
                (string) $row['calls'] ?? '0',
                (string) $row['utilize_call_ratio'] ?? '0',
                (string) $row['utilize_percentage'] ?? '0%',
                (string) $row['unutilize'] ?? '0',
                (string) $row['unutilize_percentage'] ?? '0%',
                (string) $row['duration_pds'] ?? '0',
                (string) $row['contacted'] ?? '0',
                (string) $row['contacted'] ?? '0',
                (string) $row['contacted_percentage'] ?? '0%',
                (string) $row['uncontacted'] ?? '0',
                (string) $row['uncontacted'] ?? '0',
                (string) $row['uncontacted_percentage'] ?? '0%',
                (string) $row['abandoned'] ?? '0',
                (string) $row['abandoned_rate'] ?? '0',
            ],
            $statusColumns);
        }, $this->data);
    }

    public function headings(): array
    {
        $statusNames = [];

        foreach ($this->outbounds as $status) {
            $statusNames[] = is_array($status) ? $status['name'] : $status;
        }

        return [
            array_merge(
                [
                    'PDS Name','Marketing Campaign','Agent Ready','Data Size PDS',
                    'Utilize PDS','','','',
                    'Unutilize PDS','',
                    'Duration',
                    'Contacted','','',
                    'UnContacted','','',
                    'Abandon','',
                ],
                array_fill(0, count($statusNames) - 1, '')
            ),
            array_merge(
                [
                    '', '', '', '',
                    'Data','Calls','Call Ratio','% Utilize',
                    'Data','% Unutilize',
                    '',
                    'Data','Calls','%',
                    'Data','Calls','%',
                    'Data','%',
                ],
                $statusNames
            )
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

                $callStatusStartIndex = 20; // kolom T
                $statusCount = count($this->outbounds);

                if ($statusCount > 0) {
                    $callStatusEndIndex = $callStatusStartIndex + $statusCount - 1;

                    $sheet->mergeCells(
                        $this->excelColumn($callStatusStartIndex) . '1:' .
                        $this->excelColumn($callStatusEndIndex) . '1'
                    );
                }

                $lastColumnIndex = $callStatusStartIndex + $statusCount - 1;
                $lastColumnLetter = $this->excelColumn($lastColumnIndex);

                // Alignment header
                $sheet->getStyle("A1:{$lastColumnLetter}2")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A1:{$lastColumnLetter}2")
                    ->getFont()
                    ->setBold(true);
            }
        ];
    }

    private function excelColumn(int $index): string
    {
        $column = '';

        while ($index > 0) {
            $index--;
            $column = chr($index % 26 + 65) . $column;
            $index = intdiv($index, 26);
        }

        return $column;
    }
}
