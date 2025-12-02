<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KnowledgeBaseExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $articles;

    public function __construct($articles)
    {
        $this->articles = $articles;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->articles;
    }

    /**
     * Map the data for each article
     */
    public function map($article): array
    {
        return [
            $article->id,
            $article->title,
            strip_tags($article->summary ?? substr($article->content, 0, 200)),
            $article->status,
            $article->author ? $article->author->name : 'N/A',
            $article->aplikasi ? $article->aplikasi->name : 'N/A',
            $article->kategoriMasalah ? $article->kategoriMasalah->name : 'N/A',
            implode(', ', $article->tags ?? []),
            $article->view_count ?? 0,
            $article->helpful_count ?? 0,
            $article->is_featured ? 'Yes' : 'No',
            $article->created_at ? $article->created_at->format('Y-m-d H:i') : '',
            $article->updated_at ? $article->updated_at->format('Y-m-d H:i') : '',
        ];
    }

    /**
     * Define the headings for the Excel sheet
     */
    public function headings(): array
    {
        return [
            'ID',
            'Title',
            'Summary',
            'Status',
            'Author',
            'Application',
            'Category',
            'Tags',
            'Views',
            'Helpful Count',
            'Featured',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,  // ID
            'B' => 40, // Title
            'C' => 50, // Summary
            'D' => 12, // Status
            'E' => 20, // Author
            'F' => 25, // Application
            'G' => 25, // Category
            'H' => 30, // Tags
            'I' => 10, // Views
            'J' => 15, // Helpful Count
            'K' => 10, // Featured
            'L' => 18, // Created At
            'M' => 18, // Updated At
        ];
    }
}
