<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Custom Report - {{ $filters['start_date'] ?? 'N/A' }} to {{ $filters['end_date'] ?? 'N/A' }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #7C3AED;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #7C3AED;
            margin: 0;
            font-size: 24px;
        }
        .header .subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h3 {
            color: #7C3AED;
            border-bottom: 1px solid #7C3AED;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #7C3AED;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-row {
            display: table-row;
        }
        .summary-cell {
            display: table-cell;
            padding: 8px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        .summary-cell.label {
            font-weight: bold;
            width: 50%;
        }
        .summary-cell.value {
            text-align: right;
            width: 50%;
        }
        .filters-section {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filters-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #495057;
        }
        .filter-item {
            margin-bottom: 5px;
        }
        .filter-label {
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>HelpDesk Kemlu</h1>
        <div class="subtitle">Custom Report - {{ $filters['start_date'] ?? 'N/A' }} to {{ $filters['end_date'] ?? 'N/A' }}</div>
    </div>

    @if(!empty($filters))
    <div class="filters-section">
        <div class="filters-title">Applied Filters:</div>
        @foreach($filters as $key => $value)
            @if($value && !in_array($key, ['_token', '_method']))
            <div class="filter-item">
                <span class="filter-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                {{ is_array($value) ? implode(', ', $value) : $value }}
            </div>
            @endif
        @endforeach
    </div>
    @endif

    <div class="section">
        <h3>Summary</h3>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell label">Total Tickets</div>
                <div class="summary-cell value">{{ $reportData['summary']['total_tickets'] }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell label">Resolved Tickets</div>
                <div class="summary-cell value">{{ $reportData['summary']['resolved_tickets'] }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell label">Average Resolution Time</div>
                <div class="summary-cell value">{{ $reportData['summary']['avg_resolution_time'] ? $reportData['summary']['avg_resolution_time'] . ' hours' : 'N/A' }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell label">Escalation Rate</div>
                <div class="summary-cell value">{{ $reportData['summary']['escalation_rate'] }}%</div>
            </div>
        </div>
    </div>

    @if(!empty($reportData['tickets']))
    <div class="section">
        <h3>Tickets Data</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ticket Number</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>User</th>
                    <th>Application</th>
                    <th>Category</th>
                    <th>Teknisi</th>
                    <th>Created At</th>
                    <th>Resolved At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['tickets'] as $ticket)
                <tr>
                    <td>{{ $ticket['id'] }}</td>
                    <td>{{ $ticket['ticket_number'] }}</td>
                    <td>{{ $ticket['title'] }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $ticket['status'])) }}</td>
                    <td>{{ ucfirst($ticket['priority']) }}</td>
                    <td>{{ $ticket['user'] }}</td>
                    <td>{{ $ticket['aplikasi'] }}</td>
                    <td>{{ $ticket['kategori'] }}</td>
                    <td>{{ $ticket['teknisi'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($ticket['created_at'])->format('M j, Y') }}</td>
                    <td>{{ isset($ticket['resolved_at']) && $ticket['resolved_at'] !== 'Not resolved' ? \Carbon\Carbon::parse($ticket['resolved_at'])->format('M j, Y') : 'Not resolved' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Report generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        <p>HelpDesk Kemlu - Ministry of Foreign Affairs</p>
    </div>
</body>
</html>