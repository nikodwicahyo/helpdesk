<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $data = [];

        // Summary section
        $data[] = ['Daily Report Summary'];
        $data[] = ['Date', $this->reportData['date']];
        $data[] = ['Total Tickets', $this->reportData['summary']['total_tickets']];
        $data[] = ['Resolved Tickets', $this->reportData['summary']['resolved_tickets']];
        $data[] = ['Average Resolution Time (hours)', $this->reportData['summary']['avg_resolution_time']];
        $data[] = ['Escalation Rate (%)', $this->reportData['summary']['escalation_rate']];
        $data[] = [''];

        // Status Distribution
        $data[] = ['Status Distribution'];
        $data[] = ['Status', 'Count'];
        foreach ($this->reportData['status_distribution'] as $status => $count) {
            $data[] = [$status, $count];
        }
        $data[] = [''];

        // Priority Distribution
        $data[] = ['Priority Distribution'];
        $data[] = ['Priority', 'Count'];
        foreach ($this->reportData['priority_distribution'] as $priority => $count) {
            $data[] = [$priority, $count];
        }
        $data[] = [''];

        // Application Breakdown
        $data[] = ['Application Breakdown'];
        $data[] = ['Application', 'Count'];
        foreach ($this->reportData['application_breakdown'] as $app) {
            $data[] = [$app['aplikasi'], $app['count']];
        }
        $data[] = [''];

        // Category Breakdown
        $data[] = ['Category Breakdown'];
        $data[] = ['Category', 'Count'];
        foreach ($this->reportData['category_breakdown'] as $cat) {
            $data[] = [$cat['category'], $cat['count']];
        }
        $data[] = [''];

        // Teknisi Performance
        $data[] = ['Teknisi Performance'];
        $data[] = ['Name', 'Resolved Tickets', 'Total Assigned', 'Resolution Rate (%)'];
        foreach ($this->reportData['teknisi_performance'] as $teknisi) {
            $data[] = [
                $teknisi['name'],
                $teknisi['resolved_tickets'],
                $teknisi['total_assigned'],
                $teknisi['resolution_rate']
            ];
        }
        $data[] = [''];

        // SLA Compliance
        $data[] = ['SLA Compliance'];
        $data[] = ['Total Resolved', $this->reportData['sla_compliance']['total_resolved']];
        $data[] = ['Within SLA', $this->reportData['sla_compliance']['within_sla']];
        $data[] = ['SLA Breached', $this->reportData['sla_compliance']['sla_breached']];
        $data[] = ['Compliance Rate (%)', $this->reportData['sla_compliance']['compliance_rate']];

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
            // Header styling
            'A1' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']]],
            'B1' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']]],
        ];
    }

    public function title(): string
    {
        return 'Daily Report - ' . $this->reportData['date'];
    }
}