<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PdsCampaignExport implements FromArray, WithHeadings, WithEvents
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
            ],
            $statusColumns,
            [
                (string) $row['uncontacted'] ?? '0',
                (string) $row['abandoned'] ?? '0',
                (string) $row['unutilize'] ?? '0',
                $row['duration_pds'],
            ]);
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
                    'PDS Name','Marketing Campaign','Agent Ready','Data Size','Data Utilize',
                    'Data Contacted'
                ],
                array_fill(0, count($statusNames) - 1, ''),
                ['Uncontacted','Abandon','Unutilize PDS','Duration PDS']
            ),
            array_merge(
                ['','','','',''],
                $statusNames,
                ['','','','']
            )
        ];
    }


    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function($event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $fixedLeft = 5;
                $statusCount = count($this->outbounds);
                $statusStartIndex = $fixedLeft + 1;
                $statusEndIndex   = $statusStartIndex + $statusCount - 1;

                $totalColumnCount = 9 + $statusCount;
                $lastColumnLetter = $this->excelColumn($totalColumnCount);

                // === MERGE KOLOM FIXED (A–E) ===
                for ($i = 1; $i <= $fixedLeft; $i++) {
                    $col = $this->excelColumn($i);
                    $sheet->mergeCells("{$col}1:{$col}2");
                }

                // === MERGE HEADER STATUS (F1 : ?1) ===
                if ($statusCount > 0) {
                    $sheet->mergeCells(
                        $this->excelColumn($statusStartIndex) . '1:' .
                        $this->excelColumn($statusEndIndex) . '1'
                    );
                }

                // === MERGE KOLOM SETELAH STATUS ===
                for ($i = $statusEndIndex + 1; $i <= $totalColumnCount; $i++) {
                    $col = $this->excelColumn($i);
                    $sheet->mergeCells("{$col}1:{$col}2");
                }

                // === STYLE HEADER ===
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
