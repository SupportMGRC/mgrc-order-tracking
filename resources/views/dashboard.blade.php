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
    {{-- <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Today's Orders ({{ now()->format('d M Y') }})</h5>
                </div>
            </div>
        </div>
    </div> --}}

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
    {{-- @if($todayOrders->count() > 0)
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
                                            <span class="badge bg-danger">New</span>
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
    @endif --}}

    <!-- Calendar Section -->
    <div class="row">
        <div class="col-xl-3">
            <div class="card card-h-100">
                <div class="card-body">
                    <div>
                        <h5 class="mb-1">Upcoming Deliveries</h5>
                        <p class="text-muted">Orders scheduled for delivery</p>
                        <div class="pe-2 me-n1 mb-3" data-simplebar style="height: 400px">
                            <div id="upcoming-delivery-list">
                                @forelse($upcomingDeliveries as $delivery)
                                <div class="border-bottom border-bottom-dashed py-2">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            @if($delivery->status == 'new')
                                                <span class="badge bg-danger-subtle text-danger">New</span>
                                            @elseif($delivery->status == 'preparing')
                                                <span class="badge bg-warning-subtle text-warning">Preparing</span>
                                            @elseif($delivery->status == 'ready')
                                                <span class="badge bg-primary-subtle text-primary">Ready</span>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 fs-13">
                                                <a href="{{ route('orderdetails', $delivery->id) }}" class="text-dark">
                                                    Order #{{ $delivery->id }}
                                                </a>
                                            </h6>
                                            <p class="text-muted fs-12 mb-0">{{ $delivery->customer->name ?? 'N/A' }}</p>
                                            <p class="text-muted fs-11 mb-0">
                                                <i class="ri-calendar-line"></i> {{ $delivery->pickup_delivery_date->format('M d, Y') }}
                                                @if($delivery->pickup_delivery_time)
                                                    <br><i class="ri-time-line"></i> {{ $delivery->pickup_delivery_time->format('H:i') }}
                                                @endif
                                            </p>
                                            <p class="text-muted fs-11 mb-0">
                                                <i class="ri-truck-line"></i> {{ ucfirst($delivery->delivery_type) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center text-muted py-4">
                                    <i class="ri-calendar-line fs-48 text-muted"></i>
                                    <p class="mt-2">No upcoming deliveries</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body bg-soft-info">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="ri-calendar-line text-info fs-22"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="fs-16">Delivery Calendar</h6>
                                    <p class="text-muted mb-0">Track upcoming deliveries and order schedules. Click on events to view order details.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-9">
            <div class="card card-h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">Delivery Schedule Calendar</h4>
                    <div class="d-flex align-items-center gap-3">
                        <small class="text-muted me-2">Status Colors:</small>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background-color: #f06548; color: white; font-size: 10px;">New</span>
                            <span class="badge" style="background-color: #f1b44c; color: white; font-size: 10px;">Preparing</span>
                            <span class="badge" style="background-color: #405189; color: white; font-size: 10px;">Ready</span>
                            <span class="badge" style="background-color: #0ab39c; color: white; font-size: 10px;">Delivered</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="delivery-calendar"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Metrics -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Monthly Order Trends</h4>
                </div>
                <div class="card-body">
                    <div id="monthlyTrendsChart" class="apex-charts" dir="ltr" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
    
@endsection

@section('script')
<script>
        // Monthly Trends Chart
        var monthlyTrendsOptions = {
            series: [{
                name: 'Orders',
                data: @json($data)
            }],
            chart: {
                height: 300,
                type: 'area',
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#0ab39c'],
            xaxis: {
                categories: @json($labels)
            },
            tooltip: {
                x: {
                    format: 'MMM'
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.6,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            }
        };

        var monthlyTrendsChart = new ApexCharts(document.querySelector("#monthlyTrendsChart"), monthlyTrendsOptions);
        monthlyTrendsChart.render();

        // Delivery Calendar
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('delivery-calendar');
            var calendarEvents = @json($calendarEvents);
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                timeZone: 'UTC',
                themeSystem: 'bootstrap',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,listWeek'
                },
                weekNumbers: true,
                dayMaxEvents: true,
                events: calendarEvents,
                eventClick: function(info) {
                    // Navigate to order details when event is clicked
                    window.location.href = '{{ route("orderdetails", ":id") }}'.replace(':id', info.event.id);
                },
                eventDidMount: function(info) {
                    // Add custom styling and tooltips
                    info.el.setAttribute('title', 
                        'Order #' + info.event.id + '\n' +
                        'Customer: ' + info.event.extendedProps.customer + '\n' +
                        'Status: ' + info.event.extendedProps.status.charAt(0).toUpperCase() + info.event.extendedProps.status.slice(1) + '\n' +
                        'Products: ' + info.event.extendedProps.products_count + ' item(s)' + '\n' +
                        'Type: ' + (info.event.extendedProps.delivery_type || 'N/A')
                    );
                },
                eventContent: function(arg) {
                    // Custom event content
                    return {
                        html: '<div class="fc-event-main-frame">' +
                              '<div class="fc-event-title-container">' +
                              '<div class="fc-event-title fc-sticky">' + arg.event.title + '</div>' +
                              '</div>' +
                              '</div>'
                    };
                }
            });
            
            calendar.render();
        });
</script>
@endsection
