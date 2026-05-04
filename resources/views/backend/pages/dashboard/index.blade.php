@extends('backend.layout.app')

@section('content')
<div class="page-shell">
    <nav class="breadcrumb-modern">
        <span>Dashboard</span>
    </nav>

    <div class="page-top">
        <div>
            <h1 class="page-heading">Dashboard</h1>
            <p class="page-subtitle">Overview of marketplace activity</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card stat-card--primary">
                <div class="stat-card__label">Total orders</div>
                <div class="stat-card__value">{{ number_format($stats['total_orders']) }}</div>
                <div class="stat-card__hint">Excluding failed &amp; cancelled</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card stat-card--success">
                <div class="stat-card__label">Total revenue</div>
                <div class="stat-card__value">${{ number_format($stats['total_revenue'], 2) }}</div>
                <div class="stat-card__hint">Completed transactions</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card stat-card--accent">
                <div class="stat-card__label">Donation generated</div>
                <div class="stat-card__value">${{ number_format($stats['total_donation'], 2) }}</div>
                <div class="stat-card__hint">From completed orders</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-card__label">Active sellers</div>
                <div class="stat-card__value">{{ number_format($stats['active_sellers']) }}</div>
                <div class="stat-card__hint">With ≥1 active in-stock listing</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-card__label">Active products</div>
                <div class="stat-card__value">{{ number_format($stats['active_products']) }}</div>
                <div class="stat-card__hint">Listed &amp; stock &gt; 0</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card stat-card--warning">
                <div class="stat-card__label">Pending withdrawals</div>
                <div class="stat-card__value">{{ number_format($stats['pending_withdrawals']) }}</div>
                <div class="stat-card__hint">
                    <a href="{{ route('admin.withdrawals.index') }}">Review queue →</a>
                </div>
            </div>
        </div>
    </div>

    <div class="surface p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h6 text-muted text-uppercase mb-0">Revenue vs donation</h2>
                <p class="small text-muted mb-0">Completed orders by day — business vs charity</p>
            </div>
            <div class="btn-group btn-group-sm" role="group" aria-label="Chart range">
                <a href="{{ route('admin.dashboard.index', ['range' => 7]) }}"
                   class="btn {{ $range === 7 ? 'btn-primary' : 'btn-outline-secondary' }}">7 days</a>
                <a href="{{ route('admin.dashboard.index', ['range' => 30]) }}"
                   class="btn {{ $range === 30 ? 'btn-primary' : 'btn-outline-secondary' }}">30 days</a>
            </div>
        </div>
        <div class="dashboard-revenue-chart-wrap">
            <canvas id="dashboardRevenueDonationChart"></canvas>
        </div>
    </div>

    <div class="surface p-3 mb-4">
        <div class="mb-3">
            <h2 class="h6 text-muted text-uppercase mb-0">Order type mix</h2>
            <p class="small text-muted mb-0">Units ordered (sell line quantities) by catalog — excludes failed &amp; cancelled orders</p>
        </div>
        @if (($stats['order_type_total'] ?? 0) === 0)
            <p class="small text-muted mb-2">No order lines yet — distribution will appear once customers place orders.</p>
        @endif
        <div class="dashboard-order-type-chart-wrap {{ ($stats['order_type_total'] ?? 0) === 0 ? 'd-none' : '' }}">
            <canvas id="dashboardOrderTypeChart"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var labels = @json($stats['chart_labels']);
    var revenue = @json($stats['chart_revenue']);
    var donations = @json($stats['chart_donations']);

    var ctx = document.getElementById('dashboardRevenueDonationChart');
    if (!ctx) return;

    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Revenue ($)',
                    data: revenue,
                    borderColor: 'rgba(89, 69, 69, 1)',
                    backgroundColor: 'rgba(89, 69, 69, 0.06)',
                    pointBackgroundColor: 'rgba(89, 69, 69, 1)',
                    pointRadius: 3,
                    fill: false,
                    lineTension: 0.2
                },
                {
                    label: 'Donation ($)',
                    data: donations,
                    borderColor: 'rgba(180, 83, 9, 1)',
                    backgroundColor: 'rgba(180, 83, 9, 0.06)',
                    pointBackgroundColor: 'rgba(180, 83, 9, 1)',
                    pointRadius: 3,
                    fill: false,
                    lineTension: 0.2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 0 },
            legend: {
                display: true,
                position: 'bottom'
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function (tooltipItem, data) {
                        var ds = data.datasets[tooltipItem.datasetIndex] || {};
                        var label = ds.label || '';
                        var v = tooltipItem.yLabel;
                        return label + ': $' + (typeof v === 'number' ? v.toFixed(2) : v);
                    }
                }
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function (value) {
                            if (value >= 1000) {
                                return (value / 1000).toFixed(1) + 'k';
                            }
                            return '$' + value;
                        }
                    },
                    scaleLabel: {
                        display: true,
                        labelString: 'Amount ($)'
                    },
                    gridLines: { color: 'rgba(0,0,0,0.06)' }
                }],
                xAxes: [{
                    scaleLabel: {
                        display: true,
                        labelString: 'Date'
                    },
                    gridLines: { display: false }
                }]
            }
        }
    });

    var orderTypeLabels = @json($stats['order_type_labels']);
    var orderTypeValues = @json($stats['order_type_values']);
    var orderTypeTotal = @json($stats['order_type_total']);

    var orderCtx = document.getElementById('dashboardOrderTypeChart');
    if (orderCtx && orderTypeTotal > 0) {
        new Chart(orderCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: orderTypeLabels,
                datasets: [{
                    data: orderTypeValues,
                    backgroundColor: [
                        'rgba(120, 150, 100, 0.9)',
                        'rgba(89, 69, 69, 0.9)',
                        'rgba(180, 160, 145, 0.9)',
                        'rgba(180, 83, 9, 0.9)',
                        'rgba(30, 77, 110, 0.9)'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 0 },
                cutoutPercentage: 58,
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltips: {
                    callbacks: {
                        label: function (tooltipItem, data) {
                            var label = data.labels[tooltipItem.index] || '';
                            var v = data.datasets[0].data[tooltipItem.index];
                            var pct = orderTypeTotal ? ((v / orderTypeTotal) * 100).toFixed(1) : '0.0';
                            return label + ': ' + v + ' (' + pct + '%)';
                        }
                    }
                }
            }
        });
    }
})();
</script>
@endpush
