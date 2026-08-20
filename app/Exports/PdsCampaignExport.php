<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PdsCampaignExport implements FromArray, WithHeadings, WithEvents
{
    private array $data;
    private array $outbounds;
    private ?array $visibleOutbounds = null;

    private const CONTACTED_STATUSES = [
        'Promised to Pay (PTP)',
        'Call Back',
        'Visit Request - Contacted',
        'BP Partial',
        'NBP-A',
        'NBP-B (Salah Sambung)',
        'NBP-C (Invalid Number)',
        'Paid in Confins',
    ];

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
        $primaryColumns = [
            'PDS Name',
            'SessionStart',
            'SessionEnd',
            'Agent Ready',
        ];
        $customerColumns = [
            'Data Size',
            'Data Utilize',
            'Data Unutilize',
            'Attempt',
            'Contacted',
            'Uncontacted',
            'Abandon',
        ];

        return [
            array_merge(
                $primaryColumns,
                ['Customer'],
                array_fill(0, count($customerColumns) - 1, ''),
                $visibleOutbounds ? ['Call Status(Contract)'] : [],
                array_fill(0, max(count($visibleOutbounds) - 1, 0), ''),
                ['Duration PDS']
            ),
            array_merge(
                array_fill(0, count($primaryColumns), ''),
                $customerColumns,
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

                $primaryColumnCount = 4;
                $customerColumnCount = 7;
                $visibleOutbounds = $this->visibleOutboundNames();
                $visibleOutboundCount = count($visibleOutbounds);

                for ($columnIndex = 1; $columnIndex <= $primaryColumnCount; $columnIndex++) {
                    $column = $this->excelColumn($columnIndex);
                    $sheet->mergeCells("{$column}1:{$column}2");
                }

                $customerStartIndex = $primaryColumnCount + 1;
                $customerEndIndex = $customerStartIndex + $customerColumnCount - 1;
                $sheet->mergeCells(
                    $this->excelColumn($customerStartIndex) . '1:' .
                    $this->excelColumn($customerEndIndex) . '1'
                );

                if ($visibleOutboundCount > 0) {
                    $callStatusStartIndex = $customerEndIndex + 1;
                    $callStatusEndIndex = $callStatusStartIndex + $visibleOutboundCount - 1;

                    $sheet->mergeCells(
                        $this->excelColumn($callStatusStartIndex) . '1:' .
                        $this->excelColumn($callStatusEndIndex) . '1'
                    );
                }

                $durationColumnIndex = $customerEndIndex + $visibleOutboundCount + 1;
                $durationColumn = $this->excelColumn($durationColumnIndex);
                $sheet->mergeCells("{$durationColumn}1:{$durationColumn}2");

                $sheet->getStyle("A1:{$durationColumn}2")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A1:{$durationColumn}2")
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }

    private function rows(): array
    {
        $visibleOutbounds = $this->visibleOutboundNames();

        return array_map(function ($row) use ($visibleOutbounds) {
            $ticketCounts = $row['ticket_status'] ?? [];
            $statusColumns = [];

            foreach ($visibleOutbounds as $statusName) {
                $statusColumns[] = (string) ($ticketCounts[$statusName] ?? 0);
            }

            return array_merge([
                $row['campaign'] ?? '-',
                $row['session_start'] ?? '',
                $row['session_end'] ?? '',
                (string) ($row['total_agent'] ?? ''),
                (string) ($row['data_size'] ?? 0),
                (string) ($row['data_utilize'] ?? 0),
                (string) ($row['data_unutilize'] ?? 0),
                (string) ($row['attempt'] ?? 0),
                (string) ($row['contacted'] ?? 0),
                (string) $this->uncontactedValue($row),
                (string) ($row['abandoned'] ?? 0),
            ], $statusColumns, [
                $this->durationPdsValue($row),
            ]);
        }, $this->data);
    }

    private function visibleOutboundNames(): array
    {
        if ($this->visibleOutbounds !== null) {
            return $this->visibleOutbounds;
        }

        $visibleOutbounds = [];
        $seen = [];

        foreach ($this->outbounds as $outbound) {
            $statusName = is_array($outbound) ? ($outbound['name'] ?? null) : $outbound;

            if (!$statusName) {
                continue;
            }

            $hasValue = collect($this->data)->contains(function ($row) use ($statusName) {
                return (int) ($row['ticket_status'][$statusName] ?? 0) !== 0;
            });

            if ($hasValue) {
                $norm = mb_strtolower(trim($statusName));
                $seen[$norm] = true;
                $visibleOutbounds[] = $statusName;
            }
        }

        foreach ($this->data as $row) {
            $ticketCounts = $row['ticket_status'] ?? [];
            foreach ($ticketCounts as $statusName => $count) {
                if ((int) $count === 0) {
                    continue;
                }
                $norm = mb_strtolower(trim($statusName));
                if (isset($seen[$norm])) {
                    continue;
                }
                $seen[$norm] = true;
                $visibleOutbounds[] = $statusName;
            }
        }

        return $this->visibleOutbounds = $visibleOutbounds;
    }

    private function uncontactedValue(array $row): int
    {
        $dataUtilize = (int) ($row['data_utilize'] ?? 0);
        $contacted = (int) ($row['contacted'] ?? 0);
        $abandoned = (int) ($row['abandoned'] ?? 0);

        $result = $dataUtilize - $contacted - $abandoned;
        return abs($result);
    }

    private function durationPdsValue(array $row): string
    {
        $sessionStart = $row['session_start'] ?? null;
        $sessionEnd = $row['session_end'] ?? null;

        if (!$sessionStart || !$sessionEnd) {
            return (string) ($row['duration_pds'] ?? '00:00:00');
        }

        try {
            $duration = max(0, Carbon::parse($sessionStart)->diffInSeconds(Carbon::parse($sessionEnd), true));
        } catch (\Throwable) {
            return (string) ($row['duration_pds'] ?? '00:00:00');
        }

        return gmdate('H:i:s', $duration);
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
