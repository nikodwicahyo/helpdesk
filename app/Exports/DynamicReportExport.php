<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DynamicReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
    {
        $data = [];
        $reportType = $this->reportData['type'] ?? 'custom';

        // Header
        $data[] = [ucfirst($reportType) . ' Report'];
        $data[] = ['Period: ' . ($this->reportData['period']['start'] ?? '') . ' to ' . ($this->reportData['period']['end'] ?? '')];
        $data[] = ['Generated on: ' . now()->format('Y-m-d H:i:s')];
        $data[] = [''];

        // Report Type Specific Content
        switch ($reportType) {
            case 'summary':
                // Executive Summary Report
                $data[] = ['EXECUTIVE SUMMARY OVERVIEW'];
                $overview = $this->reportData['executive_summary']['overview'] ?? [];
                $data[] = ['SLA Compliance Rate', ($overview['sla_compliance_rate'] ?? 0) . '%'];
                $data[] = ['Active Users', $overview['active_users'] ?? 0];
                $data[] = ['Resolution Rate', ($overview['resolution_rate'] ?? 0) . '%'];
                $data[] = ['Average Resolution Time (hours)', number_format($overview['avg_resolution_time'] ?? 0, 2)];
                $data[] = [''];

                // Top Applications
                if (isset($this->reportData['executive_summary']['top_applications'])) {
                    $data[] = ['TOP APPLICATIONS'];
                    $data[] = ['Application', 'Tickets Count'];
                    foreach ($this->reportData['executive_summary']['top_applications'] as $app) {
                        $data[] = [$app['aplikasi'] ?? 'Unknown', $app['count'] ?? 0];
                    }
                    $data[] = [''];
                }

                // Top Teknisi
                if (isset($this->reportData['executive_summary']['top_teknisi'])) {
                    $data[] = ['TOP PERFORMING TEKNISI'];
                    $data[] = ['Teknisi', 'Resolved Tickets', 'Resolution Rate'];
                    foreach ($this->reportData['executive_summary']['top_teknisi'] as $teknisi) {
                        $data[] = [
                            $teknisi['name'] ?? 'Unknown',
                            $teknisi['resolved_tickets'] ?? 0,
                            ($teknisi['resolution_rate'] ?? 0) . '%'
                        ];
                    }
                    $data[] = [''];
                }
                break;

            case 'performance':
                // Performance Report
                $data[] = ['TEKNISI PERFORMANCE SUMMARY'];
                $data[] = ['Total Teknisi', $this->reportData['summary']['total_teknisi'] ?? 0];
                $data[] = ['Average Resolution Time (hours)', number_format($this->reportData['summary']['avg_resolution_time'] ?? 0, 2)];
                $data[] = [''];

                $data[] = ['TEKNISI PERFORMANCE DETAILS'];
                $data[] = ['Teknisi', 'Resolved Tickets', 'Total Assigned', 'Resolution Rate'];
                foreach ($this->reportData['performance'] as $teknisi) {
                    $data[] = [
                        $teknisi['name'] ?? 'Unknown',
                        $teknisi['resolved_tickets'] ?? 0,
                        $teknisi['total_assigned'] ?? 0,
                        ($teknisi['resolution_rate'] ?? 0) . '%'
                    ];
                }
                $data[] = [''];
                break;

            case 'sla':
                // SLA Compliance Report
                $sla = $this->reportData['sla_compliance'] ?? [];
                $data[] = ['SLA COMPLIANCE OVERVIEW'];
                $data[] = ['Within SLA', $sla['within_sla'] ?? 0];
                $data[] = ['SLA Breached', $sla['sla_breached'] ?? 0];
                $data[] = ['Compliance Rate', ($sla['compliance_rate'] ?? 0) . '%'];
                $data[] = [''];

                // SLA by Priority
                if (isset($sla['priority_breakdown'])) {
                    $data[] = ['SLA BY PRIORITY'];
                    $data[] = ['Priority', 'Total', 'Within SLA', 'SLA Breached', 'Compliance Rate'];
                    foreach ($sla['priority_breakdown'] as $priority => $priorityData) {
                        $data[] = [
                            ucfirst($priority),
                            $priorityData['total'] ?? 0,
                            $priorityData['within_sla'] ?? 0,
                            $priorityData['sla_breached'] ?? 0,
                            ($priorityData['compliance_rate'] ?? 0) . '%'
                        ];
                    }
                    $data[] = [''];
                }
                break;

            case 'application':
                // Application Usage Report
                $data[] = ['APPLICATION USAGE OVERVIEW'];
                $data[] = ['Total Applications', $this->reportData['summary']['total_applications'] ?? 0];
                $data[] = ['Total Tickets', $this->reportData['summary']['total_tickets'] ?? 0];
                $data[] = [''];

                $data[] = ['APPLICATION BREAKDOWN'];
                $data[] = ['Application', 'Total Tickets'];
                foreach ($this->reportData['application_breakdown'] as $app) {
                    $data[] = [
                        $app['aplikasi'] ?? 'Unknown',
                        $app['total_tickets'] ?? 0
                    ];
                }
                $data[] = [''];
                break;

            case 'user_activity':
                // User Activity Report
                $data[] = ['USER ACTIVITY OVERVIEW'];
                $data[] = ['Active Users', $this->reportData['user_activity']['total_users_active'] ?? 0];
                $data[] = ['Total Active Users', $this->reportData['summary']['active_users'] ?? 0];
                $data[] = [''];

                $data[] = ['USER ACTIVITY DETAILS'];
                $data[] = ['User', 'Tickets Created', 'Tickets Resolved', 'Resolution Rate'];
                foreach ($this->reportData['user_activity']['user_activity'] as $userActivity) {
                    $data[] = [
                        $userActivity['user']['nama_lengkap'] ?? 'Unknown',
                        $userActivity['tickets_created'] ?? 0,
                        $userActivity['tickets_resolved'] ?? 0,
                        ($userActivity['resolution_rate'] ?? 0) . '%'
                    ];
                }
                $data[] = [''];
                break;

            default:
                // Basic Ticket Report
                $data[] = ['SUMMARY STATISTICS'];
                $data[] = ['Total Tickets', $this->reportData['summary']['total_tickets'] ?? 0];
                $data[] = ['Resolved Tickets', $this->reportData['summary']['resolved_tickets'] ?? 0];
                $data[] = ['In Progress Tickets', $this->reportData['summary']['in_progress_tickets'] ?? 0];
                $data[] = ['Average Resolution Time (hours)', number_format($this->reportData['summary']['avg_resolution_time'] ?? 0, 2)];
                $data[] = [''];

                // Chart Data (if available)
                if (isset($this->reportData['charts'])) {
                    $data[] = ['CHART DATA'];
                    $data[] = ['Status Distribution'];
                    if (isset($this->reportData['charts']['status_distribution']['labels'])) {
                        foreach ($this->reportData['charts']['status_distribution']['labels'] as $index => $label) {
                            $value = $this->reportData['charts']['status_distribution']['datasets'][0]['data'][$index] ?? 0;
                            $data[] = [ucfirst($label), $value];
                        }
                    }
                    $data[] = [''];

                    if (isset($this->reportData['charts']['priority_distribution']['labels'])) {
                        $data[] = ['Priority Distribution'];
                        foreach ($this->reportData['charts']['priority_distribution']['labels'] as $index => $label) {
                            $value = $this->reportData['charts']['priority_distribution']['datasets'][0]['data'][$index] ?? 0;
                            $data[] = [ucfirst($label), $value];
                        }
                    }
                    $data[] = [''];
                }
                break;
        }

        // Add detailed ticket data if available
        if (isset($this->reportData['data']) && count($this->reportData['data']) > 0) {
            $data[] = ['TICKETS DATA'];
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

            foreach ($this->reportData['data'] as $ticket) {
                $data[] = [
                    $ticket['id'],
                    $ticket['ticket_number'],
                    $ticket['title'],
                    $ticket['status_label'],
                    $ticket['priority_label'],
                    $ticket['user']['nama_lengkap'] ?? ($ticket['user']['name'] ?? 'Unknown'),
                    $ticket['aplikasi'] ?? 'Unknown',
                    $ticket['kategori'] ?? 'Unknown',
                    $ticket['teknisi'] ?? 'Unassigned',
                    $ticket['created_at'] ? date('Y-m-d H:i', strtotime($ticket['created_at'])) : '',
                    $ticket['resolved_at'] ? date('Y-m-d H:i', strtotime($ticket['resolved_at'])) : 'Not resolved'
                ];
            }
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            // Title styling (first row)
            1 => ['font' => ['bold' => true, 'size' => 16]],

            // Period styling (second row)
            2 => ['font' => ['bold' => true, 'size' => 12]],

            // Generated date styling (third row)
            3 => ['font' => ['italic' => true, 'size' => 10]],
        ];

        // Get report type to determine dynamic styling
        $reportType = $this->reportData['type'] ?? 'custom';

        // Dynamic header styling based on report type
        $headerRow = 5; // Default starting row for section headers

        switch ($reportType) {
            case 'summary':
                $styles[$headerRow] = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DC2626']]]; // Red
                $styles[$headerRow + 2] = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']]]; // Green
                break;

            case 'performance':
                $styles[$headerRow] = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '7C3AED']]]; // Purple
                break;

            case 'sla':
                $styles[$headerRow] = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EA580C']]]; // Orange
                break;

            case 'application':
                $styles[$headerRow] = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0891B2']]]; // Cyan
                break;

            case 'user_activity':
                $styles[$headerRow] = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'BE185D']]]; // Pink
                break;

            default:
                $styles[$headerRow] = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']]]; // Blue
                break;
        }

        // Add alternating row colors for better readability
        $highestRow = $sheet->getHighestDataRow();
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            if ($row % 2 === 0) {
                $styles[$row] = ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F9FAFB']]];
            }
        }

        return $styles;
    }

    public function title(): string
    {
        $type = ucfirst($this->reportData['type'] ?? 'Custom');
        $period = ($this->reportData['period']['start'] ?? '') . ' to ' . ($this->reportData['period']['end'] ?? '');
        return "{$type} Report - {$period}";
    }
}