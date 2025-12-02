<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Aplikasi - HelpDesk Kemlu</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #1f2937;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #4F46E5;
        }

        .header h1 {
            font-size: 20pt;
            color: #1e293b;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .header h2 {
            font-size: 14pt;
            color: #4F46E5;
            margin-bottom: 10px;
        }

        .metadata {
            background-color: #f1f5f9;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 9pt;
        }

        .metadata table {
            width: 100%;
        }

        .metadata td {
            padding: 3px 0;
        }

        .metadata td:first-child {
            font-weight: bold;
            width: 150px;
            color: #475569;
        }

        .filters {
            background-color: #fef3c7;
            padding: 10px 15px;
            border-left: 4px solid #f59e0b;
            margin-bottom: 20px;
            font-size: 9pt;
        }

        .filters strong {
            color: #92400e;
        }

        .summary {
            background-color: #dbeafe;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .summary h3 {
            font-size: 12pt;
            color: #1e40af;
            margin-bottom: 10px;
        }

        .summary-stats {
            display: table;
            width: 100%;
            margin-top: 10px;
        }

        .summary-stat {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
        }

        .summary-stat .label {
            font-size: 9pt;
            color: #64748b;
            display: block;
        }

        .summary-stat .value {
            font-size: 18pt;
            font-weight: bold;
            color: #1e293b;
            display: block;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        table.data-table thead {
            background-color: #4F46E5;
            color: white;
        }

        table.data-table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            border: 1px solid #4F46E5;
        }

        table.data-table td {
            padding: 8px;
            border: 1px solid #e2e8f0;
            font-size: 9pt;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        table.data-table tbody tr:hover {
            background-color: #f1f5f9;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-active {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-maintenance {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-development {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-deprecated {
            background-color: #f3f4f6;
            color: #374151;
        }

        .criticality-high {
            color: #dc2626;
            font-weight: bold;
        }

        .criticality-medium {
            color: #f59e0b;
            font-weight: bold;
        }

        .criticality-low {
            color: #10b981;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            font-size: 8pt;
            color: #64748b;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }

        .page-number:after {
            content: counter(page);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #64748b;
            font-style: italic;
        }

        .logo {
            max-width: 80px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>KEMENTERIAN LUAR NEGERI</h1>
        <h2>Laporan Aplikasi HelpDesk</h2>
    </div>

    {{-- Metadata --}}
    <div class="metadata">
        <table>
            <tr>
                <td>Dibuat oleh</td>
                <td>: {{ $admin->name ?? 'Admin Aplikasi' }} ({{ $admin->nip ?? '-' }})</td>
            </tr>
            <tr>
                <td>Tanggal & Waktu</td>
                <td>: {{ $generated_at }}</td>
            </tr>
            <tr>
                <td>Total Aplikasi</td>
                <td>: {{ count($applications) }} aplikasi</td>
            </tr>
            @if(isset($filters['status']))
            <tr>
                <td>Filter Status</td>
                <td>: {{ ucfirst($filters['status']) }}</td>
            </tr>
            @endif
            @if(isset($filters['search']) && $filters['search'])
            <tr>
                <td>Pencarian</td>
                <td>: "{{ $filters['search'] }}"</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Filters Info --}}
    @if(isset($filters['status']) || (isset($filters['search']) && $filters['search']))
    <div class="filters">
        <strong>⚠️ Catatan:</strong> Laporan ini menampilkan data yang sudah difilter.
        @if(isset($filters['status']))
            Status: <strong>{{ ucfirst($filters['status']) }}</strong>.
        @endif
        @if(isset($filters['search']) && $filters['search'])
            Pencarian: <strong>"{{ $filters['search'] }}"</strong>.
        @endif
    </div>
    @endif

    {{-- Summary Statistics --}}
    <div class="summary">
        <h3>Ringkasan Data</h3>
        <div class="summary-stats">
            <div class="summary-stat">
                <span class="label">Total Aplikasi</span>
                <span class="value">{{ count($applications) }}</span>
            </div>
            <div class="summary-stat">
                <span class="label">Total Kategori</span>
                <span class="value">{{ $applications->sum('total_categories') }}</span>
            </div>
            <div class="summary-stat">
                <span class="label">Total Tiket</span>
                <span class="value">{{ $applications->sum('total_tickets') }}</span>
            </div>
            <div class="summary-stat">
                <span class="label">Dalam Maintenance</span>
                <span class="value">{{ $applications->where('is_maintenance_mode', true)->count() }}</span>
            </div>
        </div>
    </div>

    {{-- Applications Data Table --}}
    @if(count($applications) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 25%;">Nama Aplikasi</th>
                <th style="width: 10%;">Kode</th>
                <th style="width: 10%;">Versi</th>
                <th style="width: 12%;">Status</th>
                <th style="width: 10%;">Kritikalitas</th>
                <th style="width: 8%;">Kategori</th>
                <th style="width: 8%;">Tiket</th>
                <th style="width: 12%;">Teknisi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($applications as $index => $app)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td><strong>{{ $app->name }}</strong></td>
                <td><code>{{ $app->code }}</code></td>
                <td>{{ $app->version ?? $app->current_version ?? '-' }}</td>
                <td>
                    @if($app->is_maintenance_mode)
                        <span class="badge badge-maintenance">Maintenance</span>
                    @else
                        @switch($app->status)
                            @case('active')
                                <span class="badge badge-active">Active</span>
                                @break
                            @case('inactive')
                                <span class="badge badge-inactive">Inactive</span>
                                @break
                            @case('development')
                                <span class="badge badge-development">Development</span>
                                @break
                            @case('deprecated')
                                <span class="badge badge-deprecated">Deprecated</span>
                                @break
                            @default
                                <span class="badge">{{ ucfirst($app->status) }}</span>
                        @endswitch
                    @endif
                </td>
                <td>
                    @switch($app->criticality)
                        @case('high')
                            <span class="criticality-high">● HIGH</span>
                            @break
                        @case('medium')
                            <span class="criticality-medium">● MEDIUM</span>
                            @break
                        @case('low')
                            <span class="criticality-low">● LOW</span>
                            @break
                        @default
                            -
                    @endswitch
                </td>
                <td style="text-align: center;">{{ $app->total_categories ?? $app->category_count ?? 0 }}</td>
                <td style="text-align: center;">{{ $app->total_tickets ?? $app->ticket_count ?? 0 }}</td>
                <td style="text-align: center;">{{ $app->assigned_teknisi_count ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        <p><strong>Tidak ada data aplikasi yang sesuai dengan filter.</strong></p>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p>
            Dokumen ini dibuat secara otomatis oleh sistem HelpDesk Kemlu<br>
            Dicetak pada {{ $generated_at }} | Halaman <span class="page-number"></span>
        </p>
    </div>
</body>
</html>
