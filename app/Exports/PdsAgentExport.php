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
                array_fill(0, max(count($visibleOutbounds) - 1, 0), ''),
                ['No Status']
            ),
            array_merge(
                ['', '', '', ''],
                $visibleOutbounds,
                ['']
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
                $noStatusColumnIndex = 4 + $visibleOutboundCount + 1;
                $lastColumnIndex = $noStatusColumnIndex;
                $lastColumnLetter = $this->excelColumn($lastColumnIndex);
                $callStatusEndColumn = $this->excelColumn(4 + $visibleOutboundCount);
                $noStatusColumnLetter = $this->excelColumn($noStatusColumnIndex);

                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:C2');
                $sheet->mergeCells('D1:D2');
                $sheet->mergeCells("{$noStatusColumnLetter}1:{$noStatusColumnLetter}2");

                if ($visibleOutboundCount > 0) {
                    $sheet->mergeCells(
                        'E1:' . $callStatusEndColumn . '1'
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
                    $startSessionDataRow = $currentRow;
                    $rowCount = count($sessGroup['rows']);

                    foreach ($sessGroup['rows'] as $index => $row) {
                        $statusColumns = [];
                        foreach ($visibleOutbounds as $statusName) {
                            $statusColumns[] = (string) $this->findStatusValue($row, $statusName);
                        }

                        $noStatusValue = (string) $this->findStatusValue($row, 'No Status');

                        $rows[] = array_merge([
                            $index === 0 ? $sessGroup['session_start'] : '',
                            $index === 0 ? $sessGroup['session_end'] : '',
                            (string) ($row['agent'] ?? '-'),
                            (string) ($row['contacted'] ?? $row['data_contacted'] ?? 0),
                        ], $statusColumns, [$noStatusValue]);

                        $currentRow++;
                    }

                    $endSessionDataRow = $currentRow - 1;
                    if ($rowCount > 1) {
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
                $dateMap[$dateKey] = [
                    'date_key' => $dateKey,
                    'date_label' => $dateLabel,
                    '_pds_map' => [],
                ];
                $dateGroups[] = &$dateMap[$dateKey];
            }

            $dateGroup = &$dateMap[$dateKey];
            $pdsKey = ($row['name'] ?? '-') . '__' . ($row['spv'] ?? '-');

            if (!isset($dateGroup['_pds_map'][$pdsKey])) {
                $dateGroup['_pds_map'][$pdsKey] = [
                    'key' => $pdsKey,
                    'title' => ($row['name'] ?? '-') . ' - ' . ($row['spv'] ?? '-'),
                    '_sess_map' => [],
                ];
            }

            $pdsGroup = &$dateGroup['_pds_map'][$pdsKey];

            $sStart = !empty($row['start_date']) ? trim((string) $row['start_date'] . ' ' . ($row['start_time'] ?? '')) : (string) ($row['session_start'] ?? '');
            $sEnd = !empty($row['end_date']) ? trim((string) $row['end_date'] . ' ' . ($row['end_time'] ?? '')) : (string) ($row['session_end'] ?? '');
            $sessKey = $sStart . '__' . $sEnd;

            if (!isset($pdsGroup['_sess_map'][$sessKey])) {
                $pdsGroup['_sess_map'][$sessKey] = [
                    'session_key' => $sessKey,
                    'session_start' => $sStart ?: '-',
                    'session_end' => $sEnd ?: '-',
                    'rows' => [],
                ];
            }

            $pdsGroup['_sess_map'][$sessKey]['rows'][] = $row;
            unset($dateGroup, $pdsGroup);
        }

        $result = [];
        foreach ($dateMap as $dateKey => $dg) {
            $pdsGroups = [];
            foreach ($dg['_pds_map'] as $pdsKey => $pg) {
                $sessionGroups = [];
                foreach ($pg['_sess_map'] as $sessKey => $sg) {
                    $sessionGroups[] = [
                        'session_key' => $sg['session_key'],
                        'session_start' => $sg['session_start'],
                        'session_end' => $sg['session_end'],
                        'rows' => $sg['rows'],
                    ];
                }
                $pdsGroups[] = [
                    'key' => $pg['key'],
                    'title' => $pg['title'],
                    'session_groups' => $sessionGroups,
                ];
            }
            $result[] = [
                'date_key' => $dg['date_key'],
                'date_label' => $dg['date_label'],
                'pds_groups' => $pdsGroups,
            ];
        }

        return $result;
    }

    private function keyNormalize(string $s): string
    {
        return preg_replace('/[\s\-_()]/u', '', mb_strtolower(trim($s)));
    }

    private function findStatusValue(array $row, string $statusName): int
    {
        $target = $this->keyNormalize($statusName);
        $normNoStatus = $this->keyNormalize('No Status');

        if ($target === $normNoStatus) {
            $ticketStatus = $row['ticket_status'] ?? [];
            if (is_array($ticketStatus)) {
                foreach ($ticketStatus as $k => $v) {
                    if ($this->keyNormalize((string) $k) === $normNoStatus && $v !== null && $v !== '') {
                        return (int) $v;
                    }
                }
            }
            if (array_key_exists('NoStatus', $row) && $row['NoStatus'] !== null && $row['NoStatus'] !== '') {
                return (int) $row['NoStatus'];
            }
            if (array_key_exists('no_status', $row) && $row['no_status'] !== null && $row['no_status'] !== '') {
                return (int) $row['no_status'];
            }
            return 0;
        }

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
        foreach ($ticketStatus as $key => $value) {
            if ($this->keyNormalize((string) $key) === $target) {
                if ($value !== null && $value !== '') {
                    return (int) $value;
                }
                return 0;
            }
        }
        $aliases = $this->callStatusAliases();
        foreach ($aliases as $aliasName => $variants) {
            foreach ($variants as $variant) {
                if ($this->keyNormalize($variant) === $target || $this->keyNormalize($aliasName) === $target) {
                    $keyInTs = null;
                    foreach (array_keys($ticketStatus) as $k) {
                        $nk = $this->keyNormalize((string) $k);
                        if ($nk === $this->keyNormalize($variant) || $nk === $this->keyNormalize($aliasName)) {
                            $keyInTs = $k;
                            break;
                        }
                    }
                    if ($keyInTs !== null) {
                        $v = $ticketStatus[$keyInTs];
                        if ($v !== null && $v !== '') {
                            return (int) $v;
                        }
                        return 0;
                    }
                }
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
            'KP' => ['KP', 'Kp', 'kp'],
            'VisitRequestContacted' => ['Visit Request - Contacted', 'Visit Request-Contacted', 'VR - Contacted', 'Visit Request Contacted', 'Contacted', 'visit request - contacted'],
            'VisitRequest' => ['Visit Request', 'VisitRequest', 'VR', 'visit request'],
        ];
    }

    private function callStatusOrder(): array
    {
        return ['PTP', 'CallBack', 'BPPartial', 'NBPA', 'NBPB', 'NBPC', 'PaidinConfins', 'KP', 'VisitRequestContacted', 'VisitRequest'];
    }

    private function excludedStatuses(): array
    {
        return [$this->keyNormalize('Contacted')];
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

        $filtered = [];
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
                $filtered[] = (string) $name;
            }
        }

        $fromPropsSet = [];
        foreach ($filtered as $s) {
            $fromPropsSet[$this->keyNormalize($s)] = true;
        }

        $extra = [];
        $extraSeen = [];
        foreach ($this->data as $row) {
            $ts = $row['ticket_status'] ?? [];
            if (!is_array($ts)) continue;
            foreach (array_keys($ts) as $key) {
                $norm = $this->keyNormalize((string) $key);
                if (in_array($norm, $excluded, true)) continue;
                if (isset($fromPropsSet[$norm]) || isset($extraSeen[$norm])) continue;
                $extraSeen[$norm] = true;
                $extra[] = (string) $key;
            }
        }

        $aliasToLabel = [];
        foreach ($aliases as $alias => $variants) {
            if (!empty($variants)) {
                $aliasToLabel[$alias] = $variants[0];
            }
        }

        $mandatoryLabels = [];
        foreach ($order as $alias) {
            if (isset($aliasToLabel[$alias])) {
                $mandatoryLabels[] = $aliasToLabel[$alias];
            }
        }

        $seenCombined = [];
        $ordered = [];
        $pushUnique = function ($name) use (&$ordered, &$seenCombined) {
            $n = $this->keyNormalize((string) $name);
            if (!$n) return;
            if (isset($seenCombined[$n])) return;
            $seenCombined[$n] = true;
            $ordered[] = (string) $name;
        };

        foreach ($filtered as $s) $pushUnique($s);
        foreach ($mandatoryLabels as $s) $pushUnique($s);
        foreach ($extra as $s) $pushUnique($s);

        if (!empty($ordered)) {
            return $this->visibleOutbounds = $ordered;
        }

        $firstRow = $this->data[0] ?? null;
        if ($firstRow && !empty($firstRow['ticket_status']) && is_array($firstRow['ticket_status'])) {
            $names = [];
            foreach (array_keys($firstRow['ticket_status']) as $k) {
                if (!in_array($this->keyNormalize((string) $k), $excluded, true)) {
                    $names[] = (string) $k;
                }
            }
            return $this->visibleOutbounds = $names;
        }

        return $this->visibleOutbounds = $mandatoryLabels;
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
