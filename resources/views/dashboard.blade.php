@extends('layouts.master')

@section('content')
    <!-- Password Verification Modal -->
    <div class="modal fade" id="dashboardPasswordModal" tabindex="-1" aria-labelledby="dashboardPasswordModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="dashboardPasswordModalLabel">
                        <i class="ri-lock-line me-2"></i>Dashboard Security
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="ri-shield-check-line text-primary" style="font-size: 48px;"></i>
                    </div>
                    <p class="text-center text-muted mb-4">Please enter your password to access the dashboard</p>
                    <form id="dashboardPasswordForm">
                        @csrf
                        <div class="mb-3">
                            <label for="dashboardPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="dashboardPassword" name="password" required autofocus>
                            <div class="invalid-feedback" id="passwordError"></div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="verifyPasswordBtn">
                                <i class="ri-check-line me-1"></i> Verify Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Content Container -->
    <div id="dashboard-content" class="dashboard-content-container">
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
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="mb-1 fs-13">
                                                    <a href="{{ route('orderdetails', $delivery->id) }}" class="text-dark">
                                                        Order #{{ $delivery->id }}
                                                    </a>
                                                </h6>
                                                @if($delivery->time_sensitive)
                                                    <span class="badge bg-danger">Time Sensitive</span>
                                                @endif
                                            </div>
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
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="mb-1 fs-13">
                                                    <a href="{{ route('orderdetails', $overdue->id) }}" class="text-dark">
                                                        Order #{{ $overdue->id }}
                                                    </a>
                                                    <span class="text-danger fs-11 ms-2">
                                                        <i class="ri-error-warning-line"></i> OVERDUE
                                                    </span>
                                                </h6>
                                                @if($overdue->time_sensitive)
                                                    <span class="badge bg-danger">Time Sensitive</span>
                                                @endif
                                            </div>
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
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h4 class="card-title mb-0">Delivery Schedule Calendar</h4>
                        <div class="d-flex flex-column align-items-end gap-1">
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted me-2">Status Colors:</small>
                                <span class="badge" style="background-color: #f8f9fa; color: #212529; font-size: 10px;">New</span>
                                <span class="badge" style="background-color: #f1b44c; color: white; font-size: 10px;">Preparing</span>
                                <span class="badge" style="background-color: #405189; color: white; font-size: 10px;">Ready</span>
                                <span class="badge" style="background-color: #0ab39c; color: white; font-size: 10px;">Delivered</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 ms-auto">
                                <span class="badge" style="background-color: #dc3545; color: white; font-size: 10px;">Time Sensitive</span>
                            </div>
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
    </div>
    <!-- End Dashboard Content Container -->
    
@endsection

@section('script')
<style>
/* Dashboard Security Styles */
.dashboard-content-container {
    transition: filter 0.3s ease;
    position: relative;
}

.dashboard-content-container.blurred {
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
}

/* Ensure sidebar is ALWAYS functional and clickable - even when dashboard is blurred */
.app-menu,
.app-menu *,
.navbar-menu,
.navbar-menu *,
.navbar-brand-box,
.navbar-brand-box *,
#scrollbar,
#scrollbar *,
.navbar-nav,
.navbar-nav *,
.nav-link,
.nav-link *,
.menu-link,
.menu-link *,
#vertical-hover,
#layout-wrapper > .app-menu,
#layout-wrapper > .app-menu * {
    pointer-events: auto !important;
    filter: none !important;
    user-select: auto !important;
    cursor: pointer !important;
}

/* Ensure topbar/header is also always functional */
.navbar-header,
.navbar-header *,
.topbar,
.topbar *,
.header-item,
.header-item * {
    pointer-events: auto !important;
    filter: none !important;
}

/* Ensure modal and all its elements can always receive clicks */
#dashboardPasswordModal,
#dashboardPasswordModal *,
.modal,
.modal *,
.modal-content,
.modal-content *,
.modal-header,
.modal-header *,
.modal-body,
.modal-body *,
.modal-footer,
.modal-footer * {
    pointer-events: auto !important;
    filter: none !important;
    user-select: auto !important;
}

/* Ensure modal is never blurred - modals are rendered at body level */
#dashboardPasswordModal {
    filter: none !important;
    z-index: 9999 !important;
    pointer-events: auto !important;
}

#dashboardPasswordModal .modal-backdrop {
    pointer-events: auto !important;
    z-index: 9998 !important;
}

/* Ensure modal backdrop doesn't interfere */
#dashboardPasswordModal.modal.show {
    z-index: 9999 !important;
    pointer-events: auto !important;
}

#dashboardPasswordModal .modal-content {
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

#dashboardPasswordModal .modal-header {
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

#dashboardPasswordModal .form-control:focus {
    border-color: #0ab39c;
    box-shadow: 0 0 0 0.2rem rgba(10, 179, 156, 0.25);
}

#dashboardPasswordModal .btn-primary {
    background-color: #0ab39c;
    border-color: #0ab39c;
}

#dashboardPasswordModal .btn-primary:hover {
    background-color: #089981;
    border-color: #089981;
}

/* Custom Bootstrap Tooltip Styles */
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
            const timeSensitiveBadge = eventData.time_sensitive ? '<span class="badge bg-danger ms-2">Time Sensitive</span>' : '';
            
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
                    ${timeSensitiveBadge}
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
            var orderDetailsBaseUrl = @json(url('/orderdetails'));
            
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
                    window.location.href = orderDetailsBaseUrl + '/' + info.event.id;
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

        // Dashboard Security - Password Verification and Idle Tracking
        (function() {
            const IDLE_TIMEOUT = 3 * 60 * 1000; // 3 minutes in milliseconds
            const PASSWORD_MODAL_ID = 'dashboardPasswordModal';
            const CONTENT_CONTAINER_ID = 'dashboard-content';
            
            let idleTimer = null;
            let isUnlocked = false;
            let passwordModal = null;
            
            // Initialize modal
            document.addEventListener('DOMContentLoaded', function() {
                const modalElement = document.getElementById(PASSWORD_MODAL_ID);
                if (modalElement) {
                    passwordModal = new bootstrap.Modal(modalElement, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    
                    // Show modal on page load
                    lockDashboard();
                }
            });
            
            // Lock dashboard (show password modal and blur content)
            function lockDashboard() {
                isUnlocked = false;
                const contentContainer = document.getElementById(CONTENT_CONTAINER_ID);
                
                // Only blur the dashboard content container, not the entire page
                if (contentContainer) {
                    contentContainer.classList.add('blurred');
                }
                
                // Ensure sidebar remains fully functional - explicitly enable pointer events
                const sidebar = document.querySelector('.app-menu');
                if (sidebar) {
                    sidebar.style.pointerEvents = 'auto';
                    sidebar.style.filter = 'none';
                    // Also ensure all sidebar children are clickable
                    const sidebarElements = sidebar.querySelectorAll('*');
                    sidebarElements.forEach(el => {
                        el.style.pointerEvents = 'auto';
                        el.style.filter = 'none';
                    });
                }
                
                if (passwordModal) {
                    passwordModal.show();
                    // Focus password input
                    setTimeout(() => {
                        const passwordInput = document.getElementById('dashboardPassword');
                        if (passwordInput) {
                            passwordInput.focus();
                            passwordInput.value = '';
                        }
                    }, 300);
                }
                
                // Clear idle timer
                resetIdleTimer();
            }
            
            // Unlock dashboard (hide modal and remove blur)
            function unlockDashboard() {
                isUnlocked = true;
                const contentContainer = document.getElementById(CONTENT_CONTAINER_ID);
                
                // Remove blur from dashboard content
                if (contentContainer) {
                    contentContainer.classList.remove('blurred');
                }
                
                if (passwordModal) {
                    passwordModal.hide();
                }
                
                // Reset idle timer
                resetIdleTimer();
            }
            
            // Reset idle timer
            function resetIdleTimer() {
                if (idleTimer) {
                    clearTimeout(idleTimer);
                }
                
                // Only set timer if dashboard is unlocked
                if (isUnlocked) {
                    idleTimer = setTimeout(() => {
                        lockDashboard();
                    }, IDLE_TIMEOUT);
                }
            }
            
            // Track user activity
            const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
            activityEvents.forEach(event => {
                document.addEventListener(event, () => {
                    if (isUnlocked) {
                        resetIdleTimer();
                    }
                }, { passive: true });
            });
            
            // Handle password form submission
            function setupPasswordForm() {
                const passwordForm = document.getElementById('dashboardPasswordForm');
                const passwordInput = document.getElementById('dashboardPassword');
                
                if (passwordForm) {
                    passwordForm.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        
                        const verifyBtn = document.getElementById('verifyPasswordBtn');
                        const errorDiv = document.getElementById('passwordError');
                        const password = passwordInput.value;
                        
                        if (!password) {
                            passwordInput.classList.add('is-invalid');
                            errorDiv.textContent = 'Please enter your password';
                            return;
                        }
                        
                        // Disable button and show loading state
                        verifyBtn.disabled = true;
                        verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying...';
                        passwordInput.classList.remove('is-invalid');
                        errorDiv.textContent = '';
                        
                        try {
                            const response = await fetch('{{ route("dashboard.verify.password") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ password: password })
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                unlockDashboard();
                            } else {
                                passwordInput.classList.add('is-invalid');
                                errorDiv.textContent = data.message || 'Invalid password. Please try again.';
                                passwordInput.value = '';
                                passwordInput.focus();
                            }
                        } catch (error) {
                            console.error('Password verification error:', error);
                            passwordInput.classList.add('is-invalid');
                            errorDiv.textContent = 'An error occurred. Please try again.';
                        } finally {
                            verifyBtn.disabled = false;
                            verifyBtn.innerHTML = '<i class="ri-check-line me-1"></i> Verify Password';
                        }
                    });
                }
                
                // Handle Enter key in password input
                if (passwordInput) {
                    passwordInput.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            if (passwordForm) {
                                passwordForm.dispatchEvent(new Event('submit'));
                            }
                        }
                    });
                }
            }
            
            // Initialize password form when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setupPasswordForm);
            } else {
                setupPasswordForm();
            }
        })();
    </script>
@endsection
