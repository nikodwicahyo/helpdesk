<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        $data[] = ['Monthly Report'];
        $data[] = ['Period', $this->reportData['period']['month_name']];
        $data[] = [''];

        // Month-over-Month Comparison
        $data[] = ['Month-over-Month Comparison'];
        $data[] = ['Metric', 'Current Month', 'Previous Month', 'Growth (%)'];
        $current = $this->reportData['comparisons']['current_month'];
        $previous = $this->reportData['comparisons']['previous_month'];
        $growth = $this->reportData['comparisons']['growth'];

        $data[] = [
            'Tickets Created',
            $current['tickets_created'],
            $previous['tickets_created'],
            $growth['tickets_created'] ?? 0
        ];
        $data[] = [
            'Tickets Resolved',
            $current['tickets_resolved'],
            $previous['tickets_resolved'],
            $growth['tickets_resolved'] ?? 0
        ];
        $data[] = [
            'Avg Resolution Time',
            $current['avg_resolution_time'],
            $previous['avg_resolution_time'],
            $growth['avg_resolution_time'] ?? 0
        ];
        $data[] = [''];

        // Application Performance
        $data[] = ['Application Performance'];
        $data[] = ['Name', 'Code', 'Total Tickets', 'Resolved Tickets', 'Resolution Rate (%)'];
        foreach ($this->reportData['application_performance'] as $app) {
            $data[] = [
                $app['name'],
                $app['code'],
                $app['total_tickets'],
                $app['resolved_tickets'],
                $app['resolution_rate']
            ];
        }
        $data[] = [''];

        // Category Analysis
        $data[] = ['Category Analysis'];
        $data[] = ['Category', 'Total Tickets', 'Resolved Tickets', 'Resolution Rate (%)'];
        foreach ($this->reportData['category_analysis'] as $category) {
            $data[] = [
                $category['name'],
                $category['total_tickets'],
                $category['resolved_tickets'],
                $category['resolution_rate']
            ];
        }
        $data[] = [''];

        // Best Teknisi
        $data[] = ['Best Teknisi'];
        $data[] = ['Name', 'Department', 'Resolved Tickets', 'Total Assigned', 'Resolution Rate (%)'];
        foreach ($this->reportData['best_teknisi'] as $teknisi) {
            $data[] = [
                $teknisi['name'],
                $teknisi['department'],
                $teknisi['resolved_tickets'],
                $teknisi['total_assigned'],
                $teknisi['resolution_rate']
            ];
        }
        $data[] = [''];

        // User Satisfaction
        $data[] = ['User Satisfaction'];
        $data[] = ['Total Rated', $this->reportData['user_satisfaction']['total_rated']];
        $data[] = ['Average Rating', $this->reportData['user_satisfaction']['average_rating']];
        $data[] = [''];
        $data[] = ['Rating Distribution'];
        $data[] = ['Rating', 'Percentage'];
        foreach ($this->reportData['user_satisfaction']['rating_distribution'] as $rating => $percentage) {
            $data[] = [$rating, $percentage . '%'];
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
            // Header styling
            'A1' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DC2626']]],
            'B1' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DC2626']]],
            'C1' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DC2626']]],
            'D1' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DC2626']]],
        ];
    }

    public function title(): string
    {
        return 'Monthly Report - ' . $this->reportData['period']['month_name'];
    }
}