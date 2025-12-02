<?php

namespace Database\Seeders;

use App\Models\Report;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reports = [
            [
                'report_type' => 'daily_tickets',
                'title' => 'Laporan Tiket Harian - ' . Carbon::now()->format('d M Y'),
                'description' => 'Ringkasan tiket yang masuk, diproses, dan diselesaikan pada tanggal ' . Carbon::now()->format('d F Y'),
                'report_date' => Carbon::now()->format('Y-m-d'),
                'period_type' => 'daily',
                'period_start' => Carbon::now()->format('Y-m-d'),
                'period_end' => Carbon::now()->format('Y-m-d'),
                'generated_by_nip' => '198001012005011001',
                'generated_by_type' => 'admin_helpdesk',
                'data' => [
                    'summary' => [
                        'total_tickets' => 15,
                        'new_tickets' => 8,
                        'in_progress' => 4,
                        'resolved' => 2,
                        'closed' => 1,
                        'cancelled' => 0,
                    ],
                    'by_priority' => [
                        'urgent' => 2,
                        'high' => 5,
                        'medium' => 6,
                        'low' => 2,
                    ],
                    'by_application' => [
                        'SIKEP' => 3,
                        'E-OFFICE' => 2,
                        'SIAN' => 2,
                        'PDD' => 2,
                        'SIKEU' => 2,
                        'SIKON' => 1,
                        'SIMONAS' => 1,
                        'E-LEARN' => 1,
                        'HELPDESK' => 1,
                    ],
                    'by_category' => [
                        'Login & Authentication' => 3,
                        'Workflow Approval Stuck' => 2,
                        'Upload Dokumen Gagal' => 2,
                        'Website Tidak Bisa Diakses' => 2,
                        'Anggaran Tidak Balance' => 2,
                        'Data WNI Tidak Ditemukan' => 1,
                        'QR Code Tidak Bisa Scan' => 1,
                        'Video Tidak Bisa Diputar' => 1,
                        'Tidak Bisa Buat Tiket' => 1,
                    ],
                    'technician_performance' => [
                        'Andi Wijaya' => ['tickets' => 3, 'avg_resolution_time' => 45, 'rating' => 4.8],
                        'Citra Kirana' => ['tickets' => 2, 'avg_resolution_time' => 60, 'rating' => 4.7],
                        'Budi Santoso' => ['tickets' => 2, 'avg_resolution_time' => 75, 'rating' => 4.6],
                        'Doni Ramadhan' => ['tickets' => 2, 'avg_resolution_time' => 30, 'rating' => 4.5],
                        'Eva Sari Dewi' => ['tickets' => 2, 'avg_resolution_time' => 50, 'rating' => 4.3],
                    ],
                ],
                'filters' => [
                    'date_range' => Carbon::now()->format('Y-m-d'),
                    'include_closed' => true,
                ],
                'parameters' => [
                    'group_by' => 'application',
                    'include_charts' => true,
                    'format' => 'detailed',
                ],
                'status' => 'completed',
                'record_count' => 15,
                'execution_time_seconds' => 2.5,
                'file_path' => '/storage/reports/daily_tickets_' . Carbon::now()->format('Y_m_d') . '.pdf',
                'file_format' => 'pdf',
                'is_scheduled' => true,
                'schedule_frequency' => 'daily',
                'schedule_time' => '06:00:00',
                'recipients' => [
                    'ahmad.wijaya@kemlu.go.id',
                    'hendro.wicaksono@kemlu.go.id',
                ],
                'visibility' => 'internal',
                'allowed_roles' => ['admin_helpdesk', 'admin_aplikasi'],
            ],
            [
                'report_type' => 'monthly_performance',
                'title' => 'Laporan Kinerja Helpdesk - Oktober 2025',
                'description' => 'Laporan kinerja bulanan helpdesk meliputi metrik SLA, kepuasan pengguna, dan analisis tren',
                'report_date' => Carbon::now()->format('Y-m-d'),
                'period_type' => 'monthly',
                'period_start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'period_end' => Carbon::now()->endOfMonth()->format('Y-m-d'),
                'generated_by_nip' => '198001012005011001',
                'generated_by_type' => 'admin_helpdesk',
                'data' => [
                    'sla_metrics' => [
                        'urgent_tickets_sla' => 95.5,
                        'high_tickets_sla' => 92.3,
                        'medium_tickets_sla' => 88.7,
                        'low_tickets_sla' => 85.2,
                        'overall_sla' => 89.8,
                    ],
                    'user_satisfaction' => [
                        'average_rating' => 4.6,
                        'total_ratings' => 127,
                        'positive_feedback' => 89,
                        'negative_feedback' => 8,
                        'neutral_feedback' => 30,
                    ],
                    'trends' => [
                        'ticket_volume_trend' => '+12%',
                        'resolution_time_trend' => '-8%',
                        'user_satisfaction_trend' => '+5%',
                    ],
                    'top_issues' => [
                        'Login & Authentication' => 23,
                        'Upload Dokumen Gagal' => 18,
                        'Workflow Approval Stuck' => 15,
                        'Website Tidak Bisa Diakses' => 12,
                        'Performance Lambat' => 10,
                    ],
                    'application_issues' => [
                        'SIKEP' => 28,
                        'E-OFFICE' => 22,
                        'SIAN' => 19,
                        'PDD' => 15,
                        'SIKEU' => 12,
                    ],
                ],
                'filters' => [
                    'month' => Carbon::now()->format('m'),
                    'year' => Carbon::now()->format('Y'),
                    'include_all_status' => true,
                ],
                'parameters' => [
                    'include_sla_analysis' => true,
                    'include_trend_analysis' => true,
                    'include_charts' => true,
                    'format' => 'comprehensive',
                ],
                'status' => 'completed',
                'record_count' => 245,
                'execution_time_seconds' => 8.7,
                'file_path' => '/storage/reports/monthly_performance_oct_2025.pdf',
                'file_format' => 'pdf',
                'is_scheduled' => true,
                'schedule_frequency' => 'monthly',
                'schedule_time' => '07:00:00',
                'recipients' => [
                    'hendro.wicaksono@kemlu.go.id',
                    'agus.setiawan.admin@kemlu.go.id',
                ],
                'visibility' => 'internal',
                'allowed_roles' => ['admin_helpdesk', 'admin_aplikasi'],
            ],
            [
                'report_type' => 'user_satisfaction',
                'title' => 'Laporan Kepuasan Pengguna - Kuartal III 2025',
                'description' => 'Analisis kepuasan pengguna berdasarkan rating dan feedback tiket yang diselesaikan',
                'report_date' => Carbon::now()->format('Y-m-d'),
                'period_type' => 'quarterly',
                'period_start' => Carbon::now()->startOfQuarter()->format('Y-m-d'),
                'period_end' => Carbon::now()->endOfQuarter()->format('Y-m-d'),
                'generated_by_nip' => '198001012005011001',
                'generated_by_type' => 'admin_helpdesk',
                'data' => [
                    'overall_satisfaction' => [
                        'average_rating' => 4.4,
                        'total_responses' => 156,
                        'satisfaction_rate' => 87.2,
                    ],
                    'rating_distribution' => [
                        '5_stars' => 89,
                        '4_stars' => 45,
                        '3_stars' => 15,
                        '2_stars' => 5,
                        '1_star' => 2,
                    ],
                    'feedback_analysis' => [
                        'positive_keywords' => ['cepat', 'helpful', 'professional', 'solusi', 'terima kasih'],
                        'negative_keywords' => ['lambat', 'complicated', 'tidak jelas', 'error', 'bug'],
                    ],
                    'department_satisfaction' => [
                        'Direktorat Asia Pasifik' => 4.6,
                        'Direktorat Eropa dan Amerika' => 4.5,
                        'Direktorat Hukum dan Perjanjian Internasional' => 4.3,
                        'Direktorat Informasi dan Media' => 4.4,
                        'Pusat Pendidikan dan Pelatihan' => 4.7,
                    ],
                    'technician_ratings' => [
                        'Andi Wijaya' => 4.8,
                        'Citra Kirana' => 4.7,
                        'Budi Santoso' => 4.6,
                        'Doni Ramadhan' => 4.5,
                        'Eva Sari Dewi' => 4.3,
                        'Fajar Nugroho' => 4.9,
                        'Gita Permata' => 4.6,
                        'Hadi Saputra' => 4.4,
                        'Indah Lestari' => 4.2,
                        'Joko Susilo' => 4.7,
                    ],
                ],
                'filters' => [
                    'quarter' => Carbon::now()->quarter,
                    'year' => Carbon::now()->format('Y'),
                    'min_rating' => 1,
                    'max_rating' => 5,
                ],
                'parameters' => [
                    'include_feedback_text' => true,
                    'include_department_breakdown' => true,
                    'include_technician_ratings' => true,
                    'sentiment_analysis' => true,
                ],
                'status' => 'completed',
                'record_count' => 156,
                'execution_time_seconds' => 5.2,
                'file_path' => '/storage/reports/user_satisfaction_q3_2025.pdf',
                'file_format' => 'pdf',
                'is_scheduled' => true,
                'schedule_frequency' => 'quarterly',
                'schedule_time' => '08:00:00',
                'recipients' => [
                    'hendro.wicaksono@kemlu.go.id',
                ],
                'visibility' => 'internal',
                'allowed_roles' => ['admin_helpdesk', 'admin_aplikasi'],
            ],
            [
                'report_type' => 'application_issues',
                'title' => 'Laporan Masalah Aplikasi - Oktober 2025',
                'description' => 'Analisis masalah yang terjadi pada setiap aplikasi dalam bulan Oktober 2025',
                'report_date' => Carbon::now()->format('Y-m-d'),
                'period_type' => 'monthly',
                'period_start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'period_end' => Carbon::now()->endOfMonth()->format('Y-m-d'),
                'generated_by_nip' => '197501012000011001',
                'generated_by_type' => 'admin_aplikasi',
                'data' => [
                    'application_issues' => [
                        'SIKEP' => [
                            'total_tickets' => 28,
                            'resolved' => 25,
                            'avg_resolution_time' => 45,
                            'top_issues' => [
                                'Login & Authentication' => 12,
                                'Data Pegawai Tidak Muncul' => 8,
                                'Error Saat Input Data' => 5,
                                'Performance Lambat' => 3,
                            ],
                        ],
                        'E-OFFICE' => [
                            'total_tickets' => 22,
                            'resolved' => 19,
                            'avg_resolution_time' => 60,
                            'top_issues' => [
                                'Workflow Approval Stuck' => 10,
                                'Template Surat Tidak Muncul' => 7,
                                'Notifikasi Email Tidak Dikirim' => 5,
                            ],
                        ],
                        'SIAN' => [
                            'total_tickets' => 19,
                            'resolved' => 17,
                            'avg_resolution_time' => 35,
                            'top_issues' => [
                                'Upload Dokumen Gagal' => 8,
                                'Pencarian Dokumen Error' => 6,
                                'Akses Dokumen Rahasia' => 5,
                            ],
                        ],
                        'PDD' => [
                            'total_tickets' => 15,
                            'resolved' => 12,
                            'avg_resolution_time' => 25,
                            'top_issues' => [
                                'Website Tidak Bisa Diakses' => 8,
                                'Konten Tidak Update' => 7,
                            ],
                        ],
                        'SIKEU' => [
                            'total_tickets' => 12,
                            'resolved' => 11,
                            'avg_resolution_time' => 75,
                            'top_issues' => [
                                'Anggaran Tidak Balance' => 6,
                                'Import Data SPAN Gagal' => 6,
                            ],
                        ],
                    ],
                    'critical_issues' => [
                        'Website Down (PDD)' => [
                            'count' => 8,
                            'impact' => 'high',
                            'resolution_time' => 25,
                        ],
                        'Upload File Rahasia (SIAN)' => [
                            'count' => 5,
                            'impact' => 'critical',
                            'resolution_time' => 35,
                        ],
                        'Workflow Stuck (E-OFFICE)' => [
                            'count' => 10,
                            'impact' => 'high',
                            'resolution_time' => 60,
                        ],
                    ],
                ],
                'filters' => [
                    'month' => Carbon::now()->format('m'),
                    'year' => Carbon::now()->format('Y'),
                    'include_all_applications' => true,
                ],
                'parameters' => [
                    'group_by_application' => true,
                    'include_critical_issues' => true,
                    'include_resolution_metrics' => true,
                    'format' => 'detailed',
                ],
                'status' => 'completed',
                'record_count' => 96,
                'execution_time_seconds' => 6.8,
                'file_path' => '/storage/reports/application_issues_oct_2025.pdf',
                'file_format' => 'pdf',
                'is_scheduled' => true,
                'schedule_frequency' => 'monthly',
                'schedule_time' => '06:30:00',
                'recipients' => [
                    'hendro.wicaksono@kemlu.go.id',
                    'agus.setiawan.admin@kemlu.go.id',
                    'dewi.sartika.admin@kemlu.go.id',
                ],
                'visibility' => 'internal',
                'allowed_roles' => ['admin_aplikasi', 'admin_helpdesk'],
            ],
        ];

        foreach ($reports as $report) {
            // Use a combination of fields that make each report unique
            $uniqueFields = [
                'report_type' => $report['report_type'],
                'title' => $report['title'],
                'report_date' => $report['report_date'],
            ];

            Report::firstOrCreate(
                $uniqueFields,
                $report
            );
        }
    }
}