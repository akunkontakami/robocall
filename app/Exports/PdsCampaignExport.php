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
        $fixedColumns = [
            'PDS Name',
            'SessionStart',
            'SessionEnd',
            'Agent Ready',
            'Data Size',
            'Data Utilize',
            'Data Unutilize',
            'Attempt',
            'Contacted',
            'Uncontacted',
            'Abandon',
        ];

        if (count($visibleOutbounds) === 0) {
            return [
                array_merge($fixedColumns, ['Duration PDS']),
                array_fill(0, count($fixedColumns) + 1, ''),
            ];
        }

        return [
            array_merge(
                $fixedColumns,
                ['Call Status'],
                array_fill(0, max(count($visibleOutbounds) - 1, 0), ''),
                ['Duration PDS']
            ),
            array_merge(
                array_fill(0, count($fixedColumns), ''),
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

                $fixedColumnCount = 11;
                $visibleOutbounds = $this->visibleOutboundNames();
                $visibleOutboundCount = count($visibleOutbounds);

                for ($columnIndex = 1; $columnIndex <= $fixedColumnCount; $columnIndex++) {
                    $column = $this->excelColumn($columnIndex);
                    $sheet->mergeCells("{$column}1:{$column}2");
                }

                if ($visibleOutboundCount > 0) {
                    $callStatusStartIndex = $fixedColumnCount + 1;
                    $callStatusEndIndex = $callStatusStartIndex + $visibleOutboundCount - 1;

                    $sheet->mergeCells(
                        $this->excelColumn($callStatusStartIndex) . '1:' .
                        $this->excelColumn($callStatusEndIndex) . '1'
                    );
                }

                $durationColumnIndex = $fixedColumnCount + $visibleOutboundCount + 1;
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
                (string) $this->contactedValue($row),
                (string) ($row['uncontacted'] ?? 0),
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

        foreach ($this->outbounds as $outbound) {
            $statusName = is_array($outbound) ? ($outbound['name'] ?? null) : $outbound;

            if (!$statusName) {
                continue;
            }

            $hasValue = collect($this->data)->contains(function ($row) use ($statusName) {
                return (int) ($row['ticket_status'][$statusName] ?? 0) !== 0;
            });

            if ($hasValue) {
                $visibleOutbounds[] = $statusName;
            }
        }

        return $this->visibleOutbounds = $visibleOutbounds;
    }

    private function contactedValue(array $row): int
    {
        return collect(self::CONTACTED_STATUSES)->sum(function ($status) use ($row) {
            return (int) ($row['ticket_status'][$status] ?? 0);
        });
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
