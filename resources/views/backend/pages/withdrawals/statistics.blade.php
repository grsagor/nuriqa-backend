@extends('backend.layout.master')

@section('title', 'Withdrawals Statistics')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Withdrawals Statistics</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.withdrawals.index') }}">Withdrawals</a></li>
        <li class="breadcrumb-item active">Statistics</li>
    </ol>

    <!-- Overview Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    Total Withdrawals
                    <div class="h3 mb-0">{{ $stats['total_withdrawals'] }}</div>
                    <small>Total Requests</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    Total Amount
                    <div class="h3 mb-0">${{ number_format($stats['total_amount'], 2) }}</div>
                    <small>All Time</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    Pending Amount
                    <div class="h3 mb-0">${{ number_format($stats['pending_amount'], 2) }}</div>
                    <small>{{ $stats['pending_count'] }} requests</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    Completed Amount
                    <div class="h3 mb-0">${{ number_format($stats['completed_amount'], 2) }}</div>
                    <small>{{ $stats['completed_count'] }} requests</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Breakdown -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Status Breakdown
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-warning">Pending</div>
                                <div class="h2">{{ $stats['pending_count'] }}</div>
                                <div class="h5">${{ number_format($stats['pending_amount'], 2) }}</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-warning" style="width: {{ $stats['total_amount'] > 0 ? ($stats['pending_amount'] / $stats['total_amount']) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-info">Approved</div>
                                <div class="h2">{{ $stats['completed_count'] }}</div>
                                <div class="h5">${{ number_format($stats['completed_amount'], 2) }}</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-info" style="width: {{ $stats['total_amount'] > 0 ? ($stats['completed_amount'] / $stats['total_amount']) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-danger">Rejected</div>
                                <div class="h2">{{ $stats['rejected_count'] }}</div>
                                <div class="h5">${{ number_format($stats['rejected_amount'], 2) }}</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-danger" style="width: {{ $stats['total_amount'] > 0 ? ($stats['rejected_amount'] / $stats['total_amount']) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h4 text-secondary">Success Rate</div>
                                <div class="h2">{{ $stats['total_withdrawals'] > 0 ? round(($stats['completed_count'] / $stats['total_withdrawals']) * 100, 1) : 0 }}%</div>
                                <div class="h5">{{ $stats['completed_count'] }} / {{ $stats['total_withdrawals'] }}</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-success" style="width: {{ $stats['total_withdrawals'] > 0 ? ($stats['completed_count'] / $stats['total_withdrawals']) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trends Chart -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Monthly Trends (Last 12 Months)
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Data Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Monthly Breakdown
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="monthlyTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Number of Withdrawals</th>
                            <th>Total Amount</th>
                            <th>Average Amount</th>
                            <th>Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monthlyStats as $stat)
                            <tr>
                                <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $stat->month)->format('F Y') }}</td>
                                <td>{{ $stat->count }}</td>
                                <td class="fw-bold">${{ number_format($stat->total, 2) }}</td>
                                <td>${{ number_format($stat->total / $stat->count, 2) }}</td>
                                <td>
                                    @php
                                        $prevStat = $monthlyStats->where('month', \Carbon\Carbon::createFromFormat('Y-m', $stat->month)->subMonth()->format('Y-m'))->first();
                                        $trend = $prevStat ? (($stat->total - $prevStat->total) / $prevStat->total) * 100 : 0;
                                    @endphp
                                    @if($trend > 0)
                                        <span class="text-success">
                                            <i class="fas fa-arrow-up"></i> {{ number_format($trend, 1) }}%
                                        </span>
                                    @elseif($trend < 0)
                                        <span class="text-danger">
                                            <i class="fas fa-arrow-down"></i> {{ number_format(abs($trend), 1) }}%
                                        </span>
                                    @else
                                        <span class="text-muted">
                                            <i class="fas fa-minus"></i> No change
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-4">
        <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Withdrawals
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Chart
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyData = @json($monthlyStats);
    
    const labels = monthlyData.map(item => {
        const date = new Date(item.month + '-01');
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    });
    
    const counts = monthlyData.map(item => item.count);
    const amounts = monthlyData.map(item => item.total);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.reverse(),
            datasets: [
                {
                    label: 'Number of Withdrawals',
                    data: counts.reverse(),
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y-count',
                    order: 2
                },
                {
                    label: 'Total Amount ($)',
                    data: amounts.reverse(),
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    type: 'line',
                    yAxisID: 'y-amount',
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                'y-count': {
                    type: 'linear',
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Number of Withdrawals'
                    }
                },
                'y-amount': {
                    type: 'linear',
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Total Amount ($)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        }
    });

    // Data Table
    $('#monthlyTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 12,
        paging: false
    });
});
</script>
@endsection