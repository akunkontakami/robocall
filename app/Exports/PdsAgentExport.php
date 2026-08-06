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
        if ($data instanceof Collection) {
            $dataArr = $data->values()->all();
        } else {
            $dataArr = is_array($data) ? $data : [];
            if (isset($dataArr['data']) && is_array($dataArr['data'])) {
                $dataArr = $dataArr['data'];
            }
            $dataArr = array_values($dataArr);
        }
        $this->data = $dataArr;

        if ($outbounds instanceof Collection) {
            $this->outbounds = $outbounds->values()->all();
        } else {
            $outArr = is_array($outbounds) ? $outbounds : [];
            if (isset($outArr['data']) && is_array($outArr['data'])) {
                $outArr = $outArr['data'];
            }
            $this->outbounds = array_values($outArr);
        }
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
                    $type = $layout['type'] ?? 'header';
                    if ($type === 'date_header' || $type === 'pds_header') {
                        $row = $layout['row'];
                        $sheet->mergeCells("A{$row}:{$lastColumnLetter}{$row}");
                        $sheet->getStyle("A{$row}:{$lastColumnLetter}{$row}")
                            ->getFont()
                            ->setBold(true);
                        $sheet->getStyle("A{$row}:{$lastColumnLetter}{$row}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                            ->setVertical(Alignment::VERTICAL_CENTER);
                    } elseif ($type === 'session_merge') {
                        $startRow = $layout['start_row'];
                        $endRow = $layout['end_row'];
                        if ($endRow > $startRow) {
                            foreach (($layout['merge_columns'] ?? ['A', 'B']) as $column) {
                                $sheet->mergeCells("{$column}{$startRow}:{$column}{$endRow}");
                            }
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

        foreach ($this->groupedByDate() as $dateGroup) {
            $rows[] = ['Tanggal: ' . $dateGroup['date_label']];
            $this->groupLayouts[] = ['type' => 'date_header', 'row' => $currentRow];
            $currentRow++;

            foreach ($dateGroup['pds_groups'] as $pdsGroup) {
                $rows[] = [$pdsGroup['title']];
                $this->groupLayouts[] = ['type' => 'pds_header', 'row' => $currentRow];
                $currentRow++;

                foreach ($pdsGroup['session_groups'] as $sessGroup) {
                    $sessionStart = '';
                    $sessionEnd = '';
                    if (!empty($sessGroup['rows'][0])) {
                        $firstRow = $sessGroup['rows'][0];
                        if (!empty($firstRow['start_date'])) {
                            $sessionStart = trim((string) $firstRow['start_date'] . ' ' . ($firstRow['start_time'] ?? ''));
                        }
                        if ($sessionStart === '' || $sessionStart === ' ') {
                            $sessionStart = (string) ($sessGroup['session_start'] ?? $firstRow['session_start'] ?? '-');
                        }
                        if (!empty($firstRow['end_date'])) {
                            $sessionEnd = trim((string) $firstRow['end_date'] . ' ' . ($firstRow['end_time'] ?? ''));
                        }
                        if ($sessionEnd === '' || $sessionEnd === ' ') {
                            $sessionEnd = (string) ($sessGroup['session_end'] ?? $firstRow['session_end'] ?? '-');
                        }
                    }

                    $startSessionDataRow = $currentRow;

                    foreach ($sessGroup['rows'] as $index => $row) {
                        $statusColumns = [];
                        foreach ($visibleOutbounds as $statusName) {
                            $statusColumns[] = (string) $this->findStatusValue($row, $statusName);
                        }

                        $rows[] = array_merge([
                            $index === 0 ? $sessionStart : '',
                            $index === 0 ? $sessionEnd : '',
                            (string) ($row['agent'] ?? '-'),
                            (string) ($row['data_utilize'] ?? $row['data_contacted'] ?? 0),
                        ], $statusColumns);

                        $currentRow++;
                    }

                    $endSessionDataRow = $currentRow - 1;
                    if (count($sessGroup['rows']) > 1) {
                        $this->groupLayouts[] = [
                            'type' => 'session_merge',
                            'start_row' => $startSessionDataRow,
                            'end_row' => $endSessionDataRow,
                            'merge_columns' => ['A', 'B'],
                        ];
                    }
                }
            }
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

    private function groupedByDate(): array
    {
        $dateGroups = [];
        $dateMap = [];

        foreach ($this->data as $row) {
            $dateKey = (string) ($row['date'] ?? '');
            if ($dateKey === '') {
                $dateKey = !empty($row['date_label']) ? (string) $row['date_label'] : 'unknown';
            }
            $dateLabel = !empty($row['date_label']) ? (string) $row['date_label'] : ($dateKey !== 'unknown' ? $dateKey : '-');

            if (!isset($dateMap[$dateKey])) {
                $dateGroup = [
                    'date_key' => $dateKey,
                    'date_label' => $dateLabel,
                    'pds_groups' => [],
                    '_pds_map' => [],
                ];
                $dateMap[$dateKey] = $dateGroup;
                $dateGroups[] = &$dateGroup;
                unset($dateGroup);
            }

            $dateGroup = &$dateMap[$dateKey];
            $pdsKey = ($row['name'] ?? '-') . '__' . ($row['spv'] ?? '-');

            if (!isset($dateGroup['_pds_map'][$pdsKey])) {
                $pdsG = [
                    'key' => $pdsKey,
                    'title' => ($row['name'] ?? '-') . ' - ' . ($row['spv'] ?? '-'),
                    'session_groups' => [],
                    '_sess_map' => [],
                ];
                $dateGroup['_pds_map'][$pdsKey] = $pdsG;
                $dateGroup['pds_groups'][] = &$pdsG;
                unset($pdsG);
            }

            $pdsGroup = &$dateGroup['_pds_map'][$pdsKey];

            $sStart = !empty($row['start_date']) ? trim((string) $row['start_date'] . ' ' . ($row['start_time'] ?? '')) : (string) ($row['session_start'] ?? '');
            $sEnd = !empty($row['end_date']) ? trim((string) $row['end_date'] . ' ' . ($row['end_time'] ?? '')) : (string) ($row['session_end'] ?? '');
            $sessKey = $sStart . '__' . $sEnd;

            if (!isset($pdsGroup['_sess_map'][$sessKey])) {
                $sg = [
                    'session_key' => $sessKey,
                    'session_start' => $sStart ?: '-',
                    'session_end' => $sEnd ?: '-',
                    'rows' => [],
                ];
                $pdsGroup['_sess_map'][$sessKey] = $sg;
                $pdsGroup['session_groups'][] = &$sg;
                unset($sg);
            }

            $pdsGroup['_sess_map'][$sessKey]['rows'][] = $row;
            unset($dateGroup, $pdsGroup);
        }

        foreach ($dateGroups as &$dg) {
            foreach ($dg['pds_groups'] as &$pg) {
                unset($pg['_sess_map']);
            }
            unset($dg['_pds_map']);
        }
        unset($dg, $pg);

        return $dateGroups;
    }

    private function keyNormalize(string $s): string
    {
        return preg_replace('/[\s\-_()]/u', '', mb_strtolower(trim($s)));
    }

    private function findStatusValue(array $row, string $statusName): int
    {
        $ticketStatus = $row['ticket_status'] ?? [];
        if (!is_array($ticketStatus)) {
            return 0;
        }
        if (array_key_exists($statusName, $ticketStatus)) {
            $v = $ticketStatus[$statusName];
            if ($v !== null && $v !== '') {
                return (int) $v;
            }
        }
        $target = $this->keyNormalize($statusName);
        foreach ($ticketStatus as $key => $value) {
            if ($this->keyNormalize((string) $key) === $target) {
                if ($value !== null && $value !== '') {
                    return (int) $value;
                }
                return 0;
            }
        }
        return 0;
    }

    private function callStatusAliases(): array
    {
        return [
            'PTP' => ['Promised to Pay (PTP)', 'Promised to Pay', 'PTP'],
            'CallBack' => ['Call Back', 'Callback', 'CallBack', 'CALL BACK'],
            'BPPartial' => ['BP Partial', 'Bp Partial', 'BPPartial'],
            'NBPA' => ['NBP-A', 'NBP A', 'NBPA'],
            'NBPB' => ['NBP-B (Salah Sambung)', 'NBP-B', 'NBP B', 'NBPB', 'Salah Sambung'],
            'NBPC' => ['NBP-C (Invalid Number)', 'NBP-C', 'NBP C', 'NBPC', 'Invalid Number'],
            'PaidinConfins' => ['Paid in Confins', 'Paid In Confins', 'PaidinConfins'],
        ];
    }

    private function callStatusOrder(): array
    {
        return ['PTP', 'CallBack', 'BPPartial', 'NBPA', 'NBPB', 'NBPC', 'PaidinConfins'];
    }

    private function excludedStatuses(): array
    {
        return ['visitrequestcontacted', 'visitrequest', 'contacted', 'vr'];
    }

    private function visibleOutboundNames(): array
    {
        if ($this->visibleOutbounds !== null) {
            return $this->visibleOutbounds;
        }

        $aliases = $this->callStatusAliases();
        $order = $this->callStatusOrder();
        $excluded = $this->excludedStatuses();

        $rawNames = collect($this->outbounds)
            ->map(fn($outbound) => is_array($outbound) ? ($outbound['name'] ?? null) : $outbound)
            ->filter()
            ->values()
            ->all();

        $names = [];
        foreach ($rawNames as $name) {
            $kn = $this->keyNormalize((string) $name);
            if (in_array($kn, $excluded, true)) {
                continue;
            }
            $found = false;
            foreach ($aliases as $variants) {
                foreach ($variants as $variant) {
                    if ($this->keyNormalize($variant) === $kn) {
                        $found = true;
                        break 2;
                    }
                }
            }
            if ($found) {
                $names[] = (string) $name;
            }
        }

        if (empty($names)) {
            $firstRow = $this->data[0] ?? null;
            if ($firstRow && !empty($firstRow['ticket_status']) && is_array($firstRow['ticket_status'])) {
                $names = array_keys($firstRow['ticket_status']);
            }
        }

        if (empty($names)) {
            $names = $order;
        }

        return $this->visibleOutbounds = $names;
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
