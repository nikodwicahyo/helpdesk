<?php

namespace App\Exports;

use App\Models\Teknisi;
use App\Models\Ticket;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeknisiReportExport implements WithMultipleSheets
{
    protected Teknisi $teknisi;
    protected Carbon $startDate;
    protected Carbon $endDate;

    public function __construct(Teknisi $teknisi, Carbon $startDate, Carbon $endDate)
    {
        $this->teknisi = $teknisi;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        return [
            new TeknisiPerformanceSummarySheet($this->teknisi, $this->startDate, $this->endDate),
            new TeknisiTicketsSheet($this->teknisi, $this->startDate, $this->endDate),
        ];
    }
}

class TeknisiPerformanceSummarySheet implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected Teknisi $teknisi;
    protected Carbon $startDate;
    protected Carbon $endDate;

    public function __construct(Teknisi $teknisi, Carbon $startDate, Carbon $endDate)
    {
        $this->teknisi = $teknisi;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Performance Summary';
    }

    public function collection()
    {
        $totalAssigned = $this->teknisi->assignedTickets()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();

        $resolvedTickets = $this->teknisi->resolvedTickets()
            ->whereBetween('resolved_at', [$this->startDate, $this->endDate])
            ->count();

        $avgResolutionTime = $this->teknisi->resolvedTickets()
            ->whereBetween('resolved_at', [$this->startDate, $this->endDate])
            ->whereNotNull('resolution_time_minutes')
            ->avg('resolution_time_minutes');

        $avgRating = $this->teknisi->resolvedTickets()
            ->whereBetween('resolved_at', [$this->startDate, $this->endDate])
            ->whereNotNull('user_rating')
            ->avg('user_rating');

        $resolutionRate = $totalAssigned > 0 ? round(($resolvedTickets / $totalAssigned) * 100, 1) : 0;

        $ticketsByPriority = $this->teknisi->assignedTickets()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $ticketsByStatus = $this->teknisi->assignedTickets()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return collect([
            ['Metric', 'Value'],
            ['Teknisi Name', $this->teknisi->name],
            ['NIP', $this->teknisi->nip],
            ['Report Period', $this->startDate->format('d M Y') . ' - ' . $this->endDate->format('d M Y')],
            ['', ''],
            ['PERFORMANCE METRICS', ''],
            ['Total Tickets Assigned', $totalAssigned],
            ['Tickets Resolved', $resolvedTickets],
            ['Resolution Rate', $resolutionRate . '%'],
            ['Avg Resolution Time (hours)', $avgResolutionTime ? round($avgResolutionTime / 60, 1) : 'N/A'],
            ['Avg Customer Rating', $avgRating ? round($avgRating, 1) . '/5' : 'N/A'],
            ['', ''],
            ['TICKETS BY PRIORITY', ''],
            ['Urgent', $ticketsByPriority['urgent'] ?? 0],
            ['High', $ticketsByPriority['high'] ?? 0],
            ['Medium', $ticketsByPriority['medium'] ?? 0],
            ['Low', $ticketsByPriority['low'] ?? 0],
            ['', ''],
            ['TICKETS BY STATUS', ''],
            ['Open', $ticketsByStatus['open'] ?? 0],
            ['In Progress', $ticketsByStatus['in_progress'] ?? 0],
            ['Waiting Response', $ticketsByStatus['waiting_response'] ?? 0],
            ['Resolved', $ticketsByStatus['resolved'] ?? 0],
            ['Closed', $ticketsByStatus['closed'] ?? 0],
        ]);
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            6 => ['font' => ['bold' => true]],
            13 => ['font' => ['bold' => true]],
            19 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 25,
        ];
    }
}

class TeknisiTicketsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected Teknisi $teknisi;
    protected Carbon $startDate;
    protected Carbon $endDate;

    public function __construct(Teknisi $teknisi, Carbon $startDate, Carbon $endDate)
    {
        $this->teknisi = $teknisi;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return 'Tickets Detail';
    }

    public function collection()
    {
        return $this->teknisi->assignedTickets()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->with(['user', 'aplikasi', 'kategoriMasalah'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function map($ticket): array
    {
        return [
            $ticket->ticket_number,
            $ticket->title,
            $ticket->user ? $ticket->user->name : 'N/A',
            $ticket->aplikasi ? $ticket->aplikasi->name : 'N/A',
            $ticket->kategoriMasalah ? $ticket->kategoriMasalah->name : 'N/A',
            ucfirst($ticket->priority),
            ucfirst(str_replace('_', ' ', $ticket->status)),
            $ticket->created_at ? $ticket->created_at->format('Y-m-d H:i') : '',
            $ticket->resolved_at ? $ticket->resolved_at->format('Y-m-d H:i') : '',
            $ticket->resolution_time_minutes ? round($ticket->resolution_time_minutes / 60, 1) . 'h' : '',
            $ticket->user_rating ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Ticket #',
            'Title',
            'Requester',
            'Application',
            'Category',
            'Priority',
            'Status',
            'Created At',
            'Resolved At',
            'Resolution Time',
            'Rating',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 35,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 12,
            'G' => 15,
            'H' => 18,
            'I' => 18,
            'J' => 15,
            'K' => 10,
        ];
    }
}
