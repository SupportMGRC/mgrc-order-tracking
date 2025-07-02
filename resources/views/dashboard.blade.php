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
                <div class="card-header">
                    <h5 class="card-title mb-0">Upcoming Deliveries</h5>
                    <p class="text-muted mb-0">Orders scheduled for today and tomorrow</p>
                </div>
                <div class="card-body">
                    <div>
                        <div class="pe-2 me-n1 mb-3" data-simplebar style="height: 400px">
                            <div id="upcoming-delivery-list">
                                @forelse($upcomingDeliveries as $delivery)
                                <div class="border-bottom border-bottom-dashed py-2">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            @if($delivery->status == 'new')
                                                <span class="badge bg-light text-dark">New</span>
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
                </div>
            </div>

            <!-- Overdue Deliveries Card -->
            <div class="card card-h-100 mt-3">
                <div class="card-header">
                    <h5 class="mb-1 text-danger">Overdue Deliveries</h5>
                    <p class="text-muted mb-0">Orders that passed delivery date</p>
                </div>
                <div class="card-body">
                    <div>
                        <div class="pe-2 me-n1 mb-3" data-simplebar style="height: 300px">
                            <div id="overdue-delivery-list">
                                @forelse($overdueDeliveries as $overdue)
                                <div class="border-bottom border-bottom-dashed py-2 ">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            @if($overdue->status == 'new')
                                                <span class="badge bg-light text-dark">New</span>
                                            @elseif($overdue->status == 'preparing')
                                                <span class="badge bg-warning-subtle text-warning">Preparing</span>
                                            @elseif($overdue->status == 'ready')
                                                <span class="badge bg-primary-subtle text-primary">Ready</span>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-1 fs-13">
                                                <a href="{{ route('orderdetails', $overdue->id) }}" class="text-dark">
                                                    Order #{{ $overdue->id }}
                                                </a>
                                                <span class="text-danger fs-11 ms-2">
                                                    <i class="ri-error-warning-line"></i> OVERDUE
                                                </span>
                                            </h6>
                                            <p class="text-muted fs-12 mb-0">{{ $overdue->customer->name ?? 'N/A' }}</p>
                                            <p class="text-danger fs-11 mb-0">
                                                <i class="ri-calendar-line"></i> {{ $overdue->pickup_delivery_date->format('M d, Y') }}
                                                @if($overdue->pickup_delivery_time)
                                                    <br><i class="ri-time-line"></i> {{ $overdue->pickup_delivery_time->format('H:i') }}
                                                @endif
                                                <br><small class="text-muted">{{ $overdue->pickup_delivery_date->diffForHumans() }}</small>
                                            </p>
                                            <p class="text-muted fs-11 mb-0">
                                                <i class="ri-truck-line"></i> {{ ucfirst($overdue->delivery_type) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center text-muted py-4">
                                    <i class="ri-check-double-line fs-48 text-success"></i>
                                    <p class="mt-2 text-success">No overdue deliveries</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- <div class="card">
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
                    </div> --}}
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
                            <span class="badge" style="background-color: #f8f9fa; color: #212529; font-size: 10px;">New</span>
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
<style>
/* Custom Bootstrap Tooltip Styles */
.custom-calendar-tooltip {
    opacity: 1 !important;
}

.custom-calendar-tooltip .tooltip-inner {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    padding: 16px;
    max-width: 280px;
    font-size: 13px;
    line-height: 1.4;
    color: #495057;
}

.custom-calendar-tooltip .tooltip-arrow::before {
    border-left-color: #e9ecef !important;
}

.custom-calendar-tooltip .tooltip-inner .tooltip-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f3f4;
}

.custom-calendar-tooltip .tooltip-inner .order-id {
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.custom-calendar-tooltip .tooltip-inner .status-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.custom-calendar-tooltip .tooltip-inner .tooltip-body {
    color: #6c757d;
}

.custom-calendar-tooltip .tooltip-inner .info-row {
    display: flex;
    align-items: center;
    margin-bottom: 6px;
}

.custom-calendar-tooltip .tooltip-inner .info-row:last-child {
    margin-bottom: 0;
}

.custom-calendar-tooltip .tooltip-inner .info-icon {
    width: 16px;
    height: 16px;
    margin-right: 8px;
    color: #8a92b2;
}

.custom-calendar-tooltip .tooltip-inner .info-text {
    flex: 1;
    font-size: 13px;
}

.custom-calendar-tooltip .tooltip-inner .products-list {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #f1f3f4;
}

.custom-calendar-tooltip .tooltip-inner .product-item {
    font-size: 12px;
    color: #8a92b2;
    margin-bottom: 2px;
    padding-left: 8px;
    position: relative;
}

.custom-calendar-tooltip .tooltip-inner .product-item:before {
    content: "•";
    position: absolute;
    left: 0;
    color: #ced4da;
}
</style>

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

        // Helper function to create tooltip content
        function createTooltipContent(eventInfo) {
            const eventData = eventInfo.event.extendedProps;
            const productsList = eventData.products_list || [];
            
            // Format delivery date and time
            const deliveryDate = eventInfo.event.start;
            const deliveryTime = eventData.delivery_time || '';
            
            let reachClientText = '';
            if (deliveryDate) {
                const dateObj = new Date(deliveryDate);
                const formattedDate = dateObj.toLocaleDateString('en-US', { 
                    month: 'long', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
                reachClientText = formattedDate;
                if (deliveryTime) {
                    reachClientText += ` at ${deliveryTime}`;
                }
            } else {
                reachClientText = 'Not scheduled';
            }
            
            return `
                <div class="tooltip-header">
                    <div class="order-id">Order #${eventInfo.event.id}</div>
                </div>
                <div class="tooltip-body">
                    <div class="info-row">
                        <i class="ri-user-line info-icon"></i>
                        <span class="info-text">${eventData.customer}</span>
                    </div>
                    <div class="info-row">
                        <i class="ri-calendar-check-line info-icon"></i>
                        <span class="info-text">${reachClientText}</span>
                    </div>
                    <div class="info-row">
                        <i class="ri-truck-line info-icon"></i>
                        <span class="info-text">${eventData.delivery_type || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <i class="ri-shopping-bag-line info-icon"></i>
                        <span class="info-text">${eventData.products_count} item(s)</span>
                    </div>
                    ${productsList.length > 0 ? `
                        <div class="products-list">
                            ${productsList.slice(0, 3).map(product => `<div class="product-item">${product}</div>`).join('')}
                            ${productsList.length > 3 ? `<div class="product-item text-muted">+${productsList.length - 3} more items</div>` : ''}
                        </div>
                    ` : ''}
                </div>
            `;
        }

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
                fixedWeekCount: false,
                events: calendarEvents,
                eventClick: function(info) {
                    // Navigate to order details when event is clicked
                    window.location.href = '{{ route("orderdetails", ":id") }}'.replace(':id', info.event.id);
                },
                eventDidMount: function(info) {
                    // Set up Bootstrap tooltip with custom HTML content
                    const tooltipContent = createTooltipContent(info);
                    
                    // Initialize Bootstrap tooltip
                    const tooltip = new bootstrap.Tooltip(info.el, {
                        title: tooltipContent,
                        html: true,
                        placement: 'left',
                        trigger: 'hover',
                        container: 'body',
                        customClass: 'custom-calendar-tooltip'
                    });
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
