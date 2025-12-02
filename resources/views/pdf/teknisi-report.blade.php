<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teknisi Performance Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #4f46e5;
        }
        .header h1 {
            font-size: 20px;
            color: #4f46e5;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 12px;
            color: #666;
        }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            font-size: 13px;
            color: #4f46e5;
            margin-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .metrics-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .metric-card {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
        }
        .metric-label {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h3 {
            font-size: 14px;
            color: #333;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #e2e8f0;
        }
        th {
            background: #f1f5f9;
            font-weight: bold;
            font-size: 10px;
            color: #475569;
        }
        td {
            font-size: 10px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef9c3; color: #854d0e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .achievement {
            display: inline-block;
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 4px;
            padding: 8px 12px;
            margin: 5px;
            text-align: center;
        }
        .achievement-icon {
            font-size: 16px;
        }
        .achievement-title {
            font-size: 10px;
            font-weight: bold;
            margin-top: 3px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Teknisi Performance Report</h1>
        <div class="subtitle">HelpDesk Kemlu - Kementerian Luar Negeri</div>
    </div>

    <div class="info-box">
        <h3>Teknisi Information</h3>
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span class="info-value">{{ $teknisi->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">NIP:</span>
            <span class="info-value">{{ $teknisi->nip }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Department:</span>
            <span class="info-value">{{ $teknisi->department ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Report Period:</span>
            <span class="info-value">{{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}</span>
        </div>
    </div>

    <div class="section">
        <h3>Performance Summary</h3>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value">{{ $performanceData['total_resolved'] ?? 0 }}</div>
                <div class="metric-label">Tickets Resolved</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ isset($performanceData['avg_resolution_time_hours']) ? number_format($performanceData['avg_resolution_time_hours'], 1) . 'h' : 'N/A' }}</div>
                <div class="metric-label">Avg Resolution Time</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ $performanceData['satisfaction_rate'] ?? 0 }}%</div>
                <div class="metric-label">Satisfaction Rate</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">{{ $performanceData['productivity_score'] ?? 0 }}</div>
                <div class="metric-label">Productivity Score</div>
            </div>
        </div>
    </div>

    @if(isset($ticketStats['by_status']) && count($ticketStats['by_status']) > 0)
    <div class="section">
        <h3>Tickets by Status</h3>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ticketStats['by_status'] as $status => $count)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
                    <td>{{ $count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(isset($ticketStats['by_priority']) && count($ticketStats['by_priority']) > 0)
    <div class="section">
        <h3>Tickets by Priority</h3>
        <table>
            <thead>
                <tr>
                    <th>Priority</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ticketStats['by_priority'] as $priority => $count)
                <tr>
                    <td>
                        @if($priority === 'urgent')
                            <span class="badge badge-danger">Urgent</span>
                        @elseif($priority === 'high')
                            <span class="badge badge-warning">High</span>
                        @elseif($priority === 'medium')
                            <span class="badge badge-info">Medium</span>
                        @else
                            <span class="badge badge-success">Low</span>
                        @endif
                    </td>
                    <td>{{ $count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(isset($achievements) && count($achievements) > 0)
    <div class="section">
        <h3>Achievements</h3>
        @foreach($achievements as $achievement)
        <div class="achievement">
            <div class="achievement-icon">🏆</div>
            <div class="achievement-title">{{ $achievement['title'] }}</div>
            <div style="font-size: 9px; color: #666;">{{ $achievement['description'] }}</div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ $generatedAt->format('d M Y, H:i') }} | HelpDesk Kemlu - Kementerian Luar Negeri</p>
        <p>This report is confidential and intended for internal use only.</p>
    </div>
</body>
</html>
