<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UserTicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $tickets;
    protected $user;

    public function __construct($tickets, $user)
    {
        $this->tickets = $tickets;
        $this->user = $user;
    }

    /**
     * Return the collection to export.
     */
    public function collection()
    {
        return $this->tickets;
    }

    /**
     * Define the headings for the export.
     */
    public function headings(): array
    {
        return [
            'Ticket Number',
            'Title',
            'Status',
            'Priority',
            'Application',
            'Category',
            'Assigned To',
            'Location',
            'Created At',
            'Updated At',
            'Resolved At',
            'Closed At',
            'Rating',
        ];
    }

    /**
     * Map each row of data.
     */
    public function map($ticket): array
    {
        return [
            $ticket->ticket_number,
            $ticket->judul,
            ucfirst(str_replace('_', ' ', $ticket->status)),
            ucfirst($ticket->prioritas),
            $ticket->aplikasi->nama_aplikasi ?? '-',
            $ticket->kategoriMasalah->nama_kategori ?? '-',
            $ticket->assignedTeknisi ? $ticket->assignedTeknisi->nama_lengkap : 'Unassigned',
            $ticket->lokasi ?? '-',
            $ticket->created_at->format('d M Y H:i'),
            $ticket->updated_at->format('d M Y H:i'),
            $ticket->resolved_at ? $ticket->resolved_at->format('d M Y H:i') : '-',
            $ticket->closed_at ? $ticket->closed_at->format('d M Y H:i') : '-',
            $ticket->rating ? $ticket->rating . '/5' : 'Not rated',
        ];
    }

    /**
     * Apply styles to the worksheet.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Set the title for the worksheet.
     */
    public function title(): string
    {
        return 'My Tickets';
    }
}