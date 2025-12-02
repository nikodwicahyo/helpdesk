<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Weekly Report - {{ $startDate }}</title>
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
            border-bottom: 2px solid #059669;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #059669;
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
            color: #059669;
            border-bottom: 1px solid #059669;
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
            background-color: #059669;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .trends-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .trend-row {
            display: table-row;
        }
        .trend-cell {
            display: table-cell;
            padding: 5px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .trend-cell.header {
            background-color: #059669;
            color: white;
            font-weight: bold;
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
        <div class="subtitle">Weekly Report - {{ \Carbon\Carbon::parse($startDate)->format('M j, Y') }} to {{ \Carbon\Carbon::parse($startDate)->endOfWeek()->format('M j, Y') }}</div>
    </div>

    <div class="section">
        <h3>Daily Trends</h3>
        <div class="trends-grid">
            <div class="trend-row">
                <div class="trend-cell header">Date</div>
                <div class="trend-cell header">Tickets Created</div>
                <div class="trend-cell header">Tickets Resolved</div>
            </div>
            @foreach($reportData['trends'] as $trend)
            <div class="trend-row">
                <div class="trend-cell">{{ \Carbon\Carbon::parse($trend['date'])->format('M j') }}</div>
                <div class="trend-cell">{{ $trend['tickets_created'] }}</div>
                <div class="trend-cell">{{ $trend['tickets_resolved'] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="section">
        <h3>Top Applications</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Total Tickets</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['top_applications'] as $app)
                <tr>
                    <td>{{ $app['name'] }}</td>
                    <td>{{ $app['code'] }}</td>
                    <td>{{ $app['total_tickets'] }}</td>
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
        <h3>Escalation Analysis</h3>
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="metrics-cell label">Total Escalated</div>
                <div class="metrics-cell value">{{ $reportData['escalation_analysis']['total_escalated'] }}</div>
            </div>
        </div>

        @if(!empty($reportData['escalation_analysis']['escalation_reasons']))
        <h4>Escalation Reasons</h4>
        <table>
            <thead>
                <tr>
                    <th>Reason</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['escalation_analysis']['escalation_reasons'] as $reason => $count)
                <tr>
                    <td>{{ $reason }}</td>
                    <td>{{ $count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div class="section">
        <h3>Resolution Efficiency</h3>
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="metrics-cell label">Total Resolved</div>
                <div class="metrics-cell value">{{ $reportData['resolution_efficiency']['total_resolved'] }}</div>
            </div>
            <div class="metrics-row">
                <div class="metrics-cell label">Average Resolution Time</div>
                <div class="metrics-cell value">{{ $reportData['resolution_efficiency']['avg_resolution_time_hours'] }} hours</div>
            </div>
            <div class="metrics-row">
                <div class="metrics-cell label">Fastest Resolution</div>
                <div class="metrics-cell value">{{ $reportData['resolution_efficiency']['fastest_resolution'] }} hours</div>
            </div>
            <div class="metrics-row">
                <div class="metrics-cell label">Slowest Resolution</div>
                <div class="metrics-cell value">{{ $reportData['resolution_efficiency']['slowest_resolution'] }} hours</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Report generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        <p>HelpDesk Kemlu - Ministry of Foreign Affairs</p>
    </div>
</body>
</html>