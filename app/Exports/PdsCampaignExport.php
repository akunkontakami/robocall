<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdsCampaignExport
{
    private array $data;
    private array $outbounds;

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

    public function download(string $filename): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet();
        $writer = new Csv($spreadsheet);
        $writer->setDelimiter("\t");
        $writer->setEnclosure('"');
        $writer->setUseBOM(true);
        $writer->setSheetIndex(0);

        return response()->streamDownload(function () use ($writer, $spreadsheet) {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('PDS Campaign');

        $headingRows = $this->headings();
        foreach ($headingRows as $rowIndex => $headingRow) {
            $sheetRow = $rowIndex + 1;

            foreach ($headingRow as $columnIndex => $heading) {
                $column = Coordinate::stringFromColumnIndex($columnIndex + 1);
                $sheet->setCellValueExplicit(
                    $column . $sheetRow,
                    (string) $heading,
                    DataType::TYPE_STRING
                );
            }
        }

        foreach ($this->rows() as $rowIndex => $row) {
            $sheetRow = $rowIndex + count($headingRows) + 1;

            foreach ($row as $columnIndex => $value) {
                $column = Coordinate::stringFromColumnIndex($columnIndex + 1);
                $sheet->setCellValueExplicit(
                    $column . $sheetRow,
                    (string) $value,
                    DataType::TYPE_STRING
                );
            }
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headingRows[0]));
        $sheet->getStyle("A1:{$lastColumn}2")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A1:{$lastColumn}2")
            ->getFont()
            ->setBold(true);

        return $spreadsheet;
    }

    private function headings(): array
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

        return [
            array_merge(
                $fixedColumns,
                [count($visibleOutbounds) > 0 ? 'Call Status' : ''],
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

        return $visibleOutbounds;
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

        $duration = max(0, Carbon::parse($sessionStart)->diffInSeconds(Carbon::parse($sessionEnd), true));

        return gmdate('H:i:s', $duration);
    }
}
