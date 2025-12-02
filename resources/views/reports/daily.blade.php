<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Report - {{ $date }}</title>
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
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #4F46E5;
            margin: 0;
            font-size: 24px;
        }
        .header .subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
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
        .section {
            margin-bottom: 20px;
        }
        .section h3 {
            color: #4F46E5;
            border-bottom: 1px solid #4F46E5;
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
            background-color: #4F46E5;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .metrics-grid {
            display: table;
            width: 100%;
        }
        .metrics-row {
            display: table-row;
        }
        .metrics-cell {
            display: table-cell;
            padding: 5px;
            border: 1px solid #ddd;
        }
        .metrics-cell.label {
            font-weight: bold;
            width: 70%;
            background-color: #f0f0f0;
        }
        .metrics-cell.value {
            width: 30%;
            text-align: center;
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
        <div class="subtitle">Daily Report - {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}</div>
    </div>

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

    <div class="section">
        <h3>Status Distribution</h3>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['status_distribution'] as $status => $count)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
                    <td>{{ $count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Priority Distribution</h3>
        <table>
            <thead>
                <tr>
                    <th>Priority</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['priority_distribution'] as $priority => $count)
                <tr>
                    <td>{{ ucfirst($priority) }}</td>
                    <td>{{ $count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(!empty($reportData['teknisi_performance']))
    <div class="section">
        <h3>Teknisi Performance</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Resolved</th>
                    <th>Total Assigned</th>
                    <th>Resolution Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['teknisi_performance'] as $teknisi)
                <tr>
                    <td>{{ $teknisi['name'] }}</td>
                    <td>{{ $teknisi['resolved_tickets'] }}</td>
                    <td>{{ $teknisi['total_assigned'] }}</td>
                    <td>{{ $teknisi['resolution_rate'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="section">
        <h3>SLA Compliance</h3>
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="metrics-cell label">Total Resolved</div>
                <div class="metrics-cell value">{{ $reportData['sla_compliance']['total_resolved'] }}</div>
            </div>
            <div class="metrics-row">
                <div class="metrics-cell label">Within SLA</div>
                <div class="metrics-cell value">{{ $reportData['sla_compliance']['within_sla'] }}</div>
            </div>
            <div class="metrics-row">
                <div class="metrics-cell label">SLA Breached</div>
                <div class="metrics-cell value">{{ $reportData['sla_compliance']['sla_breached'] }}</div>
            </div>
            <div class="metrics-row">
                <div class="metrics-cell label">Compliance Rate</div>
                <div class="metrics-cell value">{{ $reportData['sla_compliance']['compliance_rate'] }}%</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Report generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        <p>HelpDesk Kemlu - Ministry of Foreign Affairs</p>
    </div>
</body>
</html>