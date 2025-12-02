<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WeeklyReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        $data[] = ['Weekly Report'];
        $data[] = ['Period', $this->reportData['period']['start'] . ' to ' . $this->reportData['period']['end']];
        $data[] = [''];

        // Daily Trends
        $data[] = ['Daily Trends'];
        $data[] = ['Date', 'Tickets Created', 'Tickets Resolved'];
        foreach ($this->reportData['trends'] as $trend) {
            $data[] = [
                $trend['date'],
                $trend['tickets_created'],
                $trend['tickets_resolved']
            ];
        }
        $data[] = [''];

        // Top Applications
        $data[] = ['Top Applications'];
        $data[] = ['Name', 'Code', 'Total Tickets'];
        foreach ($this->reportData['top_applications'] as $app) {
            $data[] = [$app['name'], $app['code'], $app['total_tickets']];
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

        // Escalation Analysis
        $data[] = ['Escalation Analysis'];
        $data[] = ['Total Escalated', $this->reportData['escalation_analysis']['total_escalated']];
        $data[] = [''];
        $data[] = ['Escalation Reasons'];
        $data[] = ['Reason', 'Count'];
        foreach ($this->reportData['escalation_analysis']['escalation_reasons'] as $reason => $count) {
            $data[] = [$reason, $count];
        }
        $data[] = [''];
        $data[] = ['Escalated by Application'];
        $data[] = ['Application', 'Count'];
        foreach ($this->reportData['escalation_analysis']['escalated_by_application'] as $app => $count) {
            $data[] = [$app, $count];
        }
        $data[] = [''];

        // Resolution Efficiency
        $data[] = ['Resolution Efficiency'];
        $data[] = ['Total Resolved', $this->reportData['resolution_efficiency']['total_resolved']];
        $data[] = ['Average Resolution Time (hours)', $this->reportData['resolution_efficiency']['avg_resolution_time_hours']];
        $data[] = ['Fastest Resolution (hours)', $this->reportData['resolution_efficiency']['fastest_resolution']];
        $data[] = ['Slowest Resolution (hours)', $this->reportData['resolution_efficiency']['slowest_resolution']];

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
            'A1' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']]],
            'B1' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']]],
            'C1' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']]],
        ];
    }

    public function title(): string
    {
        return 'Weekly Report - ' . $this->reportData['period']['start'];
    }
}