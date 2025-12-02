<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Report - {{ $year }}-{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}</title>
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
            border-bottom: 2px solid #DC2626;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #DC2626;
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
            color: #DC2626;
            border-bottom: 1px solid #DC2626;
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
            background-color: #DC2626;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .comparison-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .comparison-row {
            display: table-row;
        }
        .comparison-cell {
            display: table-cell;
            padding: 5px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .comparison-cell.header {
            background-color: #DC2626;
            color: white;
            font-weight: bold;
        }
        .comparison-cell.label {
            text-align: left;
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
        <div class="subtitle">Monthly Report - {{ $reportData['period']['month_name'] }}</div>
    </div>

    <div class="section">
        <h3>Month-over-Month Comparison</h3>
        <div class="comparison-grid">
            <div class="comparison-row">
                <div class="comparison-cell header">Metric</div>
                <div class="comparison-cell header">Current Month</div>
                <div class="comparison-cell header">Previous Month</div>
                <div class="comparison-cell header">Growth (%)</div>
            </div>
            <div class="comparison-row">
                <div class="comparison-cell label">Tickets Created</div>
                <div class="comparison-cell">{{ $reportData['comparisons']['current_month']['tickets_created'] }}</div>
                <div class="comparison-cell">{{ $reportData['comparisons']['previous_month']['tickets_created'] }}</div>
                <div class="comparison-cell">{{ $reportData['comparisons']['growth']['tickets_created'] ?? 0 }}%</div>
            </div>
            <div class="comparison-row">
                <div class="comparison-cell label">Tickets Resolved</div>
                <div class="comparison-cell">{{ $reportData['comparisons']['current_month']['tickets_resolved'] }}</div>
                <div class="comparison-cell">{{ $reportData['comparisons']['previous_month']['tickets_resolved'] }}</div>
                <div class="comparison-cell">{{ $reportData['comparisons']['growth']['tickets_resolved'] ?? 0 }}%</div>
            </div>
            <div class="comparison-row">
                <div class="comparison-cell label">Avg Resolution Time</div>
                <div class="comparison-cell">{{ $reportData['comparisons']['current_month']['avg_resolution_time'] ?? 'N/A' }}</div>
                <div class="comparison-cell">{{ $reportData['comparisons']['previous_month']['avg_resolution_time'] ?? 'N/A' }}</div>
                <div class="comparison-cell">{{ $reportData['comparisons']['growth']['avg_resolution_time'] ?? 0 }}%</div>
            </div>
        </div>
    </div>

    @if(!empty($reportData['application_performance']))
    <div class="section">
        <h3>Application Performance</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Total Tickets</th>
                    <th>Resolved</th>
                    <th>Resolution Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['application_performance'] as $app)
                <tr>
                    <td>{{ $app['name'] }}</td>
                    <td>{{ $app['code'] }}</td>
                    <td>{{ $app['total_tickets'] }}</td>
                    <td>{{ $app['resolved_tickets'] }}</td>
                    <td>{{ $app['resolution_rate'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(!empty($reportData['best_teknisi']))
    <div class="section">
        <h3>Best Teknisi</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Resolved</th>
                    <th>Total Assigned</th>
                    <th>Resolution Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['best_teknisi'] as $teknisi)
                <tr>
                    <td>{{ $teknisi['name'] }}</td>
                    <td>{{ $teknisi['department'] }}</td>
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
        <h3>User Satisfaction</h3>
        <div class="metrics-grid">
            <div class="metrics-row">
                <div class="metrics-cell label">Total Rated</div>
                <div class="metrics-cell value">{{ $reportData['user_satisfaction']['total_rated'] }}</div>
            </div>
            <div class="metrics-row">
                <div class="metrics-cell label">Average Rating</div>
                <div class="metrics-cell value">{{ $reportData['user_satisfaction']['average_rating'] }}/5</div>
            </div>
        </div>

        @if(!empty($reportData['user_satisfaction']['rating_distribution']))
        <h4>Rating Distribution</h4>
        <table>
            <thead>
                <tr>
                    <th>Rating</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['user_satisfaction']['rating_distribution'] as $rating => $percentage)
                <tr>
                    <td>{{ $rating }} Stars</td>
                    <td>{{ $percentage }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div class="footer">
        <p>Report generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        <p>HelpDesk Kemlu - Ministry of Foreign Affairs</p>
    </div>
</body>
</html>