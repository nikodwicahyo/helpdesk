<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $data = [];

        // Header
        $data[] = ['Custom Report'];
        $data[] = ['Period', $this->reportData['period']['start'] . ' to ' . $this->reportData['period']['end']];
        $data[] = [''];

        // Applied Filters
        if (!empty($this->reportData['filters'])) {
            $data[] = ['Applied Filters'];
            foreach ($this->reportData['filters'] as $key => $value) {
                if ($value) {
                    $data[] = [ucfirst(str_replace('_', ' ', $key)), $value];
                }
            }
            $data[] = [''];
        }

        // Summary
        $data[] = ['Summary'];
        $data[] = ['Total Tickets', $this->reportData['summary']['total_tickets']];
        $data[] = ['Resolved Tickets', $this->reportData['summary']['resolved_tickets']];
        $data[] = ['Average Resolution Time (hours)', $this->reportData['summary']['avg_resolution_time']];
        $data[] = ['Escalation Rate (%)', $this->reportData['summary']['escalation_rate']];
        $data[] = [''];

        // Tickets Data
        $data[] = ['Tickets Data'];
        $data[] = [
            'ID',
            'Ticket Number',
            'Title',
            'Status',
            'Priority',
            'User',
            'Application',
            'Category',
            'Teknisi',
            'Created At',
            'Resolved At'
        ];

        foreach ($this->reportData['tickets'] as $ticket) {
            $data[] = [
                $ticket['id'],
                $ticket['ticket_number'],
                $ticket['title'],
                $ticket['status'],
                $ticket['priority'],
                $ticket['user'],
                $ticket['aplikasi'],
                $ticket['kategori'],
                $ticket['teknisi'],
                $ticket['created_at'],
                $ticket['resolved_at'] ?? 'Not resolved'
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Title styling
            1 => ['font' => ['bold' => true, 'size' => 16]],
            // Header styling for tickets table
            12 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '7C3AED']]],
        ];
    }

    public function title(): string
    {
        return 'Custom Report - ' . $this->reportData['period']['start'] . ' to ' . $this->reportData['period']['end'];
    }
}