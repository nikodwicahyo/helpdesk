<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucfirst($type ?? 'Report') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #2563eb;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .header .period {
            color: #6c757d;
            font-size: 16px;
            margin: 5px 0;
        }
        .summary {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .summary h2 {
            color: #2563eb;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .summary-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .summary-item .value {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            display: block;
            margin-bottom: 5px;
        }
        .summary-item .label {
            color: #6c757d;
            font-size: 14px;
        }
        .data-table {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .data-table h2 {
            color: #2563eb;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }
        th {
            background-color: #2563eb;
            color: white;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            border-bottom: 2px solid #2563eb;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .status-open { color: #ffc107; font-weight: bold; }
        .status-in_progress { color: #17a2b8; font-weight: bold; }
        .status-resolved { color: #28a745; font-weight: bold; }
        .status-closed { color: #6c757d; font-weight: bold; }
        .priority-low { color: #6c757d; }
        .priority-medium { color: #17a2b8; font-weight: bold; }
        .priority-high { color: #dc3545; font-weight: bold; }
        .priority-urgent { color: #e83e8c; font-weight: bold; }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #6c757d;
            font-size: 12px;
        }
        @media print {
            body { margin: 0; }
            .header, .summary, .data-table {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ ucfirst($type ?? 'Report') }} Report</h1>
        <p class="period">
            Period: {{ $reportData['period']['start'] ?? 'N/A' }} to {{ $reportData['period']['end'] ?? 'N/A' }}
        </p>
        <p>
            Generated on: {{ now()->format('F j, Y \a\t g:i A') }}
        </p>
    </div>

    <!-- Report Type Specific Content -->
    @switch($type)
        @case('summary')
            <!-- Executive Summary Report -->
            @if(isset($reportData['executive_summary']))
            <div class="summary">
                <h2>Executive Summary Overview</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['executive_summary']['overview']['sla_compliance_rate'] ?? 0, 1) }}%</span>
                        <span class="label">SLA Compliance</span>
                    </div>
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['executive_summary']['overview']['active_users'] ?? 0) }}</span>
                        <span class="label">Active Users</span>
                    </div>
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['executive_summary']['overview']['resolution_rate'] ?? 0, 1) }}%</span>
                        <span class="label">Resolution Rate</span>
                    </div>
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['executive_summary']['overview']['avg_resolution_time'] ?? 0, 2) }}</span>
                        <span class="label">Avg Resolution Time (hours)</span>
                    </div>
                </div>

                @if(isset($reportData['executive_summary']['top_applications']))
                <div class="data-table">
                    <h2>Top Applications</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Application</th>
                                <th>Tickets Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['executive_summary']['top_applications'] as $app)
                            <tr>
                                <td>{{ $app['aplikasi'] ?? 'Unknown' }}</td>
                                <td>{{ number_format($app['count'] ?? 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if(isset($reportData['executive_summary']['top_teknisi']))
                <div class="data-table">
                    <h2>Top Performing Teknisi</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Teknisi</th>
                                <th>Resolved Tickets</th>
                                <th>Resolution Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['executive_summary']['top_teknisi'] as $teknisi)
                            <tr>
                                <td>{{ $teknisi['name'] ?? 'Unknown' }}</td>
                                <td>{{ number_format($teknisi['resolved_tickets'] ?? 0) }}</td>
                                <td>{{ number_format($teknisi['resolution_rate'] ?? 0, 1) }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @endif
            @break

        @case('performance')
            <!-- Performance Report -->
            @if(isset($reportData['performance']))
            <div class="summary">
                <h2>Teknisi Performance Summary</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['summary']['total_teknisi'] ?? 0) }}</span>
                        <span class="label">Total Teknisi</span>
                    </div>
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['summary']['avg_resolution_time'] ?? 0, 2) }}</span>
                        <span class="label">Avg Resolution Time (hours)</span>
                    </div>
                </div>
            </div>

            <div class="data-table">
                <h2>Teknisi Performance Details</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Teknisi</th>
                            <th>Resolved Tickets</th>
                            <th>Total Assigned</th>
                            <th>Resolution Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['performance'] as $teknisi)
                        <tr>
                            <td>{{ $teknisi['name'] ?? 'Unknown' }}</td>
                            <td>{{ number_format($teknisi['resolved_tickets'] ?? 0) }}</td>
                            <td>{{ number_format($teknisi['total_assigned'] ?? 0) }}</td>
                            <td>{{ number_format($teknisi['resolution_rate'] ?? 0, 1) }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            @break

        @case('sla')
            <!-- SLA Compliance Report -->
            @if(isset($reportData['sla_compliance']))
            <div class="summary">
                <h2>SLA Compliance Overview</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['sla_compliance']['within_sla'] ?? 0) }}</span>
                        <span class="label">Within SLA</span>
                    </div>
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['sla_compliance']['sla_breached'] ?? 0) }}</span>
                        <span class="label">SLA Breached</span>
                    </div>
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['sla_compliance']['compliance_rate'] ?? 0, 1) }}%</span>
                        <span class="label">Compliance Rate</span>
                    </div>
                </div>

                @if(isset($reportData['sla_compliance']['priority_breakdown']))
                <div class="data-table">
                    <h2>SLA by Priority</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Priority</th>
                                <th>Total</th>
                                <th>Within SLA</th>
                                <th>SLA Breached</th>
                                <th>Compliance Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['sla_compliance']['priority_breakdown'] as $priority => $data)
                            <tr>
                                <td>{{ ucfirst($priority) }}</td>
                                <td>{{ number_format($data['total'] ?? 0) }}</td>
                                <td>{{ number_format($data['within_sla'] ?? 0) }}</td>
                                <td>{{ number_format($data['sla_breached'] ?? 0) }}</td>
                                <td>{{ number_format($data['compliance_rate'] ?? 0, 1) }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @endif
            @break

        @case('application')
            <!-- Application Usage Report -->
            @if(isset($reportData['application_breakdown']))
            <div class="summary">
                <h2>Application Usage Overview</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['summary']['total_applications'] ?? 0) }}</span>
                        <span class="label">Total Applications</span>
                    </div>
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['summary']['total_tickets'] ?? 0) }}</span>
                        <span class="label">Total Tickets</span>
                    </div>
                </div>
            </div>

            <div class="data-table">
                <h2>Application Breakdown</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Application</th>
                            <th>Total Tickets</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['application_breakdown'] as $app)
                        <tr>
                            <td>{{ $app['aplikasi'] ?? 'Unknown' }}</td>
                            <td>{{ number_format($app['total_tickets'] ?? 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            @break

        @case('user_activity')
            <!-- User Activity Report -->
            @if(isset($reportData['user_activity']))
            <div class="summary">
                <h2>User Activity Overview</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['user_activity']['total_users_active'] ?? 0) }}</span>
                        <span class="label">Active Users</span>
                    </div>
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['summary']['active_users'] ?? 0) }}</span>
                        <span class="label">Total Active Users</span>
                    </div>
                </div>
            </div>

            <div class="data-table">
                <h2>User Activity Details</h2>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Tickets Created</th>
                            <th>Tickets Resolved</th>
                            <th>Resolution Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['user_activity']['user_activity'] as $userActivity)
                        <tr>
                            <td>{{ $userActivity['user']['nama_lengkap'] ?? 'Unknown' }}</td>
                            <td>{{ number_format($userActivity['tickets_created'] ?? 0) }}</td>
                            <td>{{ number_format($userActivity['tickets_resolved'] ?? 0) }}</td>
                            <td>{{ number_format($userActivity['resolution_rate'] ?? 0, 1) }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            @break

        @default
            <!-- Default/Basic Ticket Report -->
            @if(isset($reportData['summary']))
            <div class="summary">
                <h2>Summary Statistics</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['summary']['total_tickets'] ?? 0) }}</span>
                        <span class="label">Total Tickets</span>
                    </div>
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['summary']['resolved_tickets'] ?? 0) }}</span>
                        <span class="label">Resolved Tickets</span>
                    </div>
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['summary']['in_progress_tickets'] ?? 0) }}</span>
                        <span class="label">In Progress</span>
                    </div>
                    <div class="summary-item">
                        <span class="value">{{ number_format($reportData['summary']['avg_resolution_time'] ?? 0, 2) }}</span>
                        <span class="label">Avg Resolution Time (hours)</span>
                    </div>
                </div>
            </div>
            @endif
            @endswitch

    <!-- Detailed Data Table -->
    @if(isset($reportData['data']) && count($reportData['data']) > 0)
    <div class="data-table">
        <h2>Tickets Data</h2>
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
                @foreach($reportData['data'] as $ticket)
                <tr>
                    <td>{{ $ticket['id'] }}</td>
                    <td>{{ $ticket['ticket_number'] }}</td>
                    <td>{{ Str::limit($ticket['title'], 50) }}</td>
                    <td><span class="status-{{ $ticket['status'] }}">{{ $ticket['status_label'] ?? '' }}</span></td>
                    <td><span class="priority-{{ $ticket['priority'] }}">{{ $ticket['priority_label'] ?? '' }}</span></td>
                    <td>{{ $ticket['user']['nama_lengkap'] ?? ($ticket['user']['name'] ?? 'Unknown') }}</td>
                    <td>{{ $ticket['aplikasi'] ?? 'Unknown' }}</td>
                    <td>{{ $ticket['kategori'] ?? 'Unknown' }}</td>
                    <td>{{ $ticket['teknisi'] ?? 'Unassigned' }}</td>
                    <td>{{ $ticket['created_at'] ? \Carbon\Carbon::parse($ticket['created_at'])->format('Y-m-d H:i') : 'N/A' }}</td>
                    <td>{{ $ticket['resolved_at'] ? \Carbon\Carbon::parse($ticket['resolved_at'])->format('Y-m-d H:i') : 'Not resolved' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Report generated by HelpDesk Kemlu System</p>
        <p>Page {{ $page ?? 1 }} of {{ $totalPages ?? 1 }}</p>
    </div>
</body>
</html>