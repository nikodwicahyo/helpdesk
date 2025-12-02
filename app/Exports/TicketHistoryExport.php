<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketHistoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $history;
    protected $user;

    public function __construct($history, $user)
    {
        $this->history = $history;
        $this->user = $user;
    }

    /**
     * Return the collection to export.
     */
    public function collection()
    {
        return $this->history;
    }

    /**
     * Define the headings for the export.
     */
    public function headings(): array
    {
        return [
            'Ticket Number',
            'Ticket Title',
            'Action',
            'Actor',
            'Actor Type',
            'Old Value',
            'New Value',
            'Description',
            'Date & Time',
        ];
    }

    /**
     * Map each row of data.
     */
    public function map($history): array
    {
        return [
            $history->ticket->ticket_number ?? '-',
            $history->ticket->judul ?? '-',
            ucfirst(str_replace('_', ' ', $history->action)),
            $history->actor ? ($history->actor->nama_lengkap ?? $history->actor->name) : 'System',
            ucfirst(str_replace('_', ' ', $history->actor_type)),
            $history->old_value ?? '-',
            $history->new_value ?? '-',
            $history->description ?? '-',
            $history->created_at->format('d M Y H:i:s'),
        ];
    }

    /**
     * Apply styles to the worksheet.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    /**
     * Set the title for the worksheet.
     */
    public function title(): string
    {
        return 'Ticket History';
    }
}
