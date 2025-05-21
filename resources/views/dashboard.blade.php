@extends('layouts.master')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Dashboard</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Menu</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- Today's Order Status Cards -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Today's Orders ({{ now()->format('d M Y') }})</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">New Orders Today</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value" data-target="{{ $todayNewCount }}">{{ $todayNewCount }}</span></h4>
                            <a href="{{ route('orderhistory', ['status' => 'new']) }}" class="text-decoration-underline">View all new orders</a>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-info rounded fs-3">
                                <i class="bx bx-shopping-bag text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Preparing Today</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value" data-target="{{ $todayPreparingCount }}">{{ $todayPreparingCount }}</span></h4>
                            <a href="{{ route('orderhistory', ['status' => 'preparing']) }}" class="text-decoration-underline">View preparing orders</a>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-warning rounded fs-3">
                                <i class="bx bx-time-five text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Ready Today</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value" data-target="{{ $todayReadyCount }}">{{ $todayReadyCount }}</span></h4>
                            <a href="{{ route('orderhistory', ['status' => 'ready']) }}" class="text-decoration-underline">View ready orders</a>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-primary rounded fs-3">
                                <i class="bx bx-package text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Delivered Today</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4"><span class="counter-value" data-target="{{ $todayDeliveredCount }}">{{ $todayDeliveredCount }}</span></h4>
                            <a href="{{ route('orderhistory', ['status' => 'delivered']) }}" class="text-decoration-underline">View delivered orders</a>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-success rounded fs-3">
                                <i class="bx bx-check-circle text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Orders List -->
    @if($todayOrders->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Today's Order List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-nowrap align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col">Order ID</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Products</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Time</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($todayOrders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('orderdetails', $order->id) }}" class="fw-medium">#{{ $order->id }}</a>
                                    </td>
                                    <td>{{ $order->customer->name ?? 'N/A' }}</td>
                                    <td>{{ $order->products->count() }} item(s)</td>
                                    <td>
                                        @if($order->status == 'new')
                                            <span class="badge bg-info">New</span>
                                        @elseif($order->status == 'preparing')
                                            <span class="badge bg-warning">Preparing</span>
                                        @elseif($order->status == 'ready')
                                            <span class="badge bg-primary">Ready</span>
                                        @elseif($order->status == 'delivered')
                                            <span class="badge bg-success">Delivered</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $order->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $order->created_at->format('H:i') }}</td>
                                    <td>
                                        <a href="{{ route('orderdetails', $order->id) }}" class="btn btn-sm btn-soft-primary">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Second Row - Business Overview & Recent Orders -->
    <div class="row">
        <!-- Business Overview -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Monthly Order Trends ({{ $currentYear }})</h4>
                </div>
                <div class="card-body p-0 pb-2">
                    <div class="w-100">
                        <div id="monthlyOrdersChart" data-colors='["--vz-primary", "--vz-success"]' class="apex-charts" dir="ltr"></div>
                    </div>
                </div>
            </div>

            <!-- Business Metrics -->
            <div class="row">
                <div class="col-xl-4 col-md-6">
                    <div class="card card-height-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-soft-primary text-primary rounded-2 fs-2">
                                        <i class="bx bx-shopping-bag"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-3">Total Orders</p>
                                    <h4 class="fs-4 mb-3"><span class="counter-value" data-target="{{ $totalOrders }}">{{ $totalOrders }}</span></h4>
                                    <p class="text-muted mb-0">All time orders</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card card-height-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-soft-info text-info rounded-2 fs-2">
                                        <i class="bx bx-calendar"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-3">This Month</p>
                                    <h4 class="fs-4 mb-3"><span class="counter-value" data-target="{{ $monthlyOrderCount }}">{{ $monthlyOrderCount }}</span></h4>
                                    <p class="text-muted mb-0">Orders since {{ now()->startOfMonth()->format('d M Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card card-height-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-soft-success text-success rounded-2 fs-2">
                                        <i class="bx bx-line-chart"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-3">This Year</p>
                                    <h4 class="fs-4 mb-3"><span class="counter-value" data-target="{{ $yearlyOrderCount }}">{{ $yearlyOrderCount }}</span></h4>
                                    <p class="text-muted mb-0">Orders in {{ now()->year }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Stats -->
            <div class="row">
                <div class="col-xl-6 col-md-6">
                    <div class="card card-height-100">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Customer & Product Stats</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-soft-primary text-primary rounded-2 fs-2">
                                        <i class="bx bx-user-circle"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-1">Customers</p>
                                    <h4 class="fs-4 mb-0"><span class="counter-value" data-target="{{ $customerCount }}">{{ $customerCount }}</span></h4>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-soft-primary text-primary rounded-2 fs-2">
                                        <i class="bx bx-package"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-uppercase fw-medium text-muted mb-1">Products</p>
                                    <h4 class="fs-4 mb-0">
                                        <span class="counter-value" data-target="{{ $productCount }}">{{ $productCount }}</span>
                                        @if($lowStockCount > 0)
                                            <span class="badge bg-danger ms-1">{{ $lowStockCount }} low stock</span>
                                        @endif
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-md-6">
                    <div class="card card-height-100">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Total Orders by Status</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-1">New: <span class="fw-bold text-info">{{ $totalNewCount }}</span></p>
                                    <div class="progress animated-progress custom-progress progress-label">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ ($totalNewCount / ($totalOrders ?: 1)) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-1">Preparing: <span class="fw-bold text-warning">{{ $totalPreparingCount }}</span></p>
                                    <div class="progress animated-progress custom-progress progress-label">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ ($totalPreparingCount / ($totalOrders ?: 1)) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-1">Ready: <span class="fw-bold text-primary">{{ $totalReadyCount }}</span></p>
                                    <div class="progress animated-progress custom-progress progress-label">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ ($totalReadyCount / ($totalOrders ?: 1)) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-1">Delivered: <span class="fw-bold text-success">{{ $totalDeliveredCount }}</span></p>
                                    <div class="progress animated-progress custom-progress progress-label">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ ($totalDeliveredCount / ($totalOrders ?: 1)) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Recent Orders</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('orderhistory') }}" class="btn btn-soft-primary btn-sm">View All Orders</a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-nowrap align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col">Order ID</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('orderdetails', $order->id) }}" class="fw-medium">#{{ $order->id }}</a>
                                    </td>
                                    <td>{{ $order->customer->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($order->status == 'new')
                                            <span class="badge bg-info">New</span>
                                        @elseif($order->status == 'preparing')
                                            <span class="badge bg-warning">Preparing</span>
                                        @elseif($order->status == 'ready')
                                            <span class="badge bg-primary">Ready</span>
                                        @elseif($order->status == 'delivered')
                                            <span class="badge bg-success">Delivered</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $order->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $order->created_at->format('d M Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No recent orders found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Monthly Orders Chart
        var labels = @json($labels);
        var data = @json($data);
        var currentYear = @json($currentYear);
        
        var options = {
            series: [{
                name: 'Orders',
                data: data
            }],
            chart: {
                height: 350,
                type: 'bar',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    dataLabels: {
                        position: 'top',
                    },
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val;
                },
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ["#304758"]
                }
            },
            xaxis: {
                categories: labels,
                position: 'bottom',
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                crosshairs: {
                    fill: {
                        type: 'gradient',
                        gradient: {
                            colorFrom: '#D8E3F0',
                            colorTo: '#BED1E6',
                            stops: [0, 100],
                            opacityFrom: 0.4,
                            opacityTo: 0.5,
                        }
                    }
                },
                tooltip: {
                    enabled: true,
                }
            },
            yaxis: {
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false,
                },
                labels: {
                    show: false,
                }
            },
            title: {
                text: 'Monthly Order Distribution ' + currentYear,
                floating: true,
                offsetY: 330,
                align: 'center',
                style: {
                    color: '#444'
                }
            }
        };

        var chart = new ApexCharts(document.querySelector("#monthlyOrdersChart"), options);
        chart.render();
    });
</script>
@endsection
