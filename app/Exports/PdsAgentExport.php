<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PdsAgentExport implements FromArray, WithHeadings, WithEvents
{
    private array $data;
    private array $outbounds;
    private ?array $visibleOutbounds = null;
    private array $groupLayouts = [];

    public function __construct(array|Collection $data, array|Collection $outbounds)
    {
        $this->data = $data instanceof Collection ? $data->values()->all() : array_values($data);
        $this->outbounds = $outbounds instanceof Collection ? $outbounds->values()->all() : array_values($outbounds);
    }

    public function array(): array
    {
        return $this->rows();
    }

    public function headings(): array
    {
        $visibleOutbounds = $this->visibleOutboundNames();

        return [
            array_merge(
                ['SessionStart', 'SessionEnd', 'Deskcoll', 'Data Contacted'],
                $visibleOutbounds ? ['Call Status'] : [],
                array_fill(0, max(count($visibleOutbounds) - 1, 0), '')
            ),
            array_merge(
                ['', '', '', ''],
                $visibleOutbounds
            ),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $visibleOutboundCount = count($this->visibleOutboundNames());
                $lastColumnIndex = 4 + $visibleOutboundCount;
                $lastColumnLetter = $this->excelColumn($lastColumnIndex);

                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:C2');
                $sheet->mergeCells('D1:D2');

                if ($visibleOutboundCount > 0) {
                    $sheet->mergeCells(
                        'E1:' . $this->excelColumn($lastColumnIndex) . '1'
                    );
                }

                foreach ($this->groupLayouts as $layout) {
                    $sheet->mergeCells("A{$layout['header_row']}:{$lastColumnLetter}{$layout['header_row']}");
                    $sheet->getStyle("A{$layout['header_row']}:{$lastColumnLetter}{$layout['header_row']}")
                        ->getFont()
                        ->setBold(true);

                    $sheet->getStyle("A{$layout['header_row']}:{$lastColumnLetter}{$layout['header_row']}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    if ($layout['rowspan'] > 1) {
                        foreach ($layout['merge_columns'] as $column) {
                            $sheet->mergeCells("{$column}{$layout['data_start_row']}:{$column}{$layout['data_end_row']}");
                        }
                    }
                }

                $sheet->getStyle("A1:{$lastColumnLetter}2")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A1:{$lastColumnLetter}2")
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle("A3:{$lastColumnLetter}" . max($sheet->getHighestRow(), 3))
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }

    private function rows(): array
    {
        $visibleOutbounds = $this->visibleOutboundNames();
        $rows = [];
        $currentRow = 3;
        $this->groupLayouts = [];

        foreach ($this->groupedData() as $group) {
            $rows[] = [$group['title']];
            $headerRow = $currentRow;
            $currentRow++;
            $dataStartRow = $currentRow;

            foreach ($group['rows'] as $index => $row) {
                $statusColumns = [];

                foreach ($visibleOutbounds as $statusName) {
                    $statusColumns[] = (string) ($row['ticket_status'][$statusName] ?? 0);
                }

                $rows[] = array_merge([
                    $index === 0 ? (string) ($row['session_start'] ?? '-') : '',
                    $index === 0 ? (string) ($row['session_end'] ?? '-') : '',
                    (string) ($row['agent'] ?? '-'),
                    (string) ($row['data_utilize'] ?? 0),
                ], $statusColumns);

                $currentRow++;
            }

            $dataEndRow = $currentRow - 1;
            $this->groupLayouts[] = [
                'header_row' => $headerRow,
                'data_start_row' => $dataStartRow,
                'data_end_row' => $dataEndRow,
                'rowspan' => count($group['rows']),
                'merge_columns' => ['A', 'B'],
            ];
        }

        return $rows;
    }

    private function groupedData(): array
    {
        $groups = [];

        foreach ($this->data as $row) {
            $groupKey = ($row['name'] ?? '-') . '__' . ($row['spv'] ?? '-');

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'title' => ($row['name'] ?? '-') . ' - ' . ($row['spv'] ?? '-'),
                    'rows' => [],
                ];
            }

            $groups[$groupKey]['rows'][] = $row;
        }

        return array_values($groups);
    }

    private function visibleOutboundNames(): array
    {
        if ($this->visibleOutbounds !== null) {
            return $this->visibleOutbounds;
        }

        return $this->visibleOutbounds = collect($this->outbounds)
            ->map(fn($outbound) => is_array($outbound) ? ($outbound['name'] ?? null) : $outbound)
            ->filter()
            ->values()
            ->all();
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
