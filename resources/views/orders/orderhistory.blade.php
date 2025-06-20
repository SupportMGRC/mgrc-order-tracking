@extends('layouts.master')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Order History</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Menu</a></li>
                        <li class="breadcrumb-item active">Order History</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="orderList">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Order History</h5>
                        </div>
                        {{-- <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal"
                                    id="create-btn" data-bs-target="#showModal"><i
                                        class="ri-add-line align-bottom me-1"></i> Create Order</button>
                                @if(Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin')
                                <button type="button" class="btn btn-secondary"><i
                                        class="ri-file-download-line align-bottom me-1"></i> Import</button>
                                <button class="btn btn-soft-danger" id="remove-actions" onClick="deleteMultiple()"><i
                                        class="ri-delete-bin-2-line"></i></button>
                                @endif
                            </div>
                        </div> --}}
                    </div>
                </div>
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form action="{{ route('orderhistory') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-lg-8 col-12">
                                <div class="search-box">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search for order ID, customer, order status..."
                                        value="{{ request('search') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-lg-4 col-12">
                                <div class="d-flex flex-column flex-sm-row gap-2">
                                    <select class="form-select flex-fill" name="date_range" id="dateRangeSelect" 
                                            {{ in_array(request('status'), ['new', 'preparing', 'ready']) ? 'disabled' : '' }}>
                                        <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>
                                            Today
                                        </option>
                                        <option value="weekly" {{ request('date_range') == 'weekly' ? 'selected' : '' }}>
                                            This Week
                                        </option>
                                        <option value="monthly" {{ request('date_range') == 'monthly' ? 'selected' : '' }}>
                                            This Month
                                        </option>
                                        <option value="yearly" {{ request('date_range') == 'yearly' ? 'selected' : '' }}>
                                            This Year
                                        </option>
                                        <option value="all" {{ request('date_range') == 'all' || !request('date_range') ? 'selected' : '' }}>
                                            All Time
                                        </option>
                                    </select>
                                    <button type="submit" class="btn btn-primary flex-shrink-0" 
                                            {{ in_array(request('status'), ['new', 'preparing', 'ready']) ? 'disabled' : '' }}>
                                        <i class="ri-equalizer-fill align-bottom me-1"></i><span class="d-none d-sm-inline"> Filter</span><span class="d-sm-none">Filter</span>
                                    </button>
                                    <!-- Hidden input to preserve the status parameter -->
                                    @if(request('status') && request('status') != 'all')
                                    <input type="hidden" name="status" value="{{ request('status') }}">
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body pt-0">
                    <div class="col-12">
                        <div class="card-header px-0">
                            <div class="row align-items-center">
                                <div class="col-12">
                                    <div class="nav-tabs-wrapper">
                                        <ul class="nav nav-tabs nav-tabs-custom nav-primary gap-1 flex-nowrap overflow-auto" role="tablist" style="white-space: nowrap;">
                                            <li class="nav-item flex-shrink-0">
                                                <a class="nav-link {{ request('status') == 'all' || !request('status') ? 'active' : '' }} py-3 All"
                                                    href="{{ route('orderhistory', ['status' => 'all', 'date_range' => request('date_range', 'all'), 'search' => request('search')]) }}" role="tab">
                                                    <i class="ri-shopping-bag-3-line me-1 align-bottom"></i><span class="d-none d-sm-inline"> All Orders</span><span class="d-sm-none">All</span>
                                                </a>
                                            </li>
                                            <li class="nav-item flex-shrink-0">
                                                <a class="nav-link {{ request('status') == 'new' ? 'active' : '' }} py-3 Pending"
                                                    href="{{ route('orderhistory', ['status' => 'new', 'search' => request('search')]) }}" role="tab">
                                                    <i class="ri-add-circle-line me-1 align-bottom"></i><span class="d-none d-sm-inline"> New</span><span class="d-sm-none">New</span>
                                                    <span class="badge bg-danger align-middle ms-1">{{ $newCount }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item flex-shrink-0">
                                                <a class="nav-link {{ request('status') == 'preparing' ? 'active' : '' }} py-3 Inprogress"
                                                    href="{{ route('orderhistory', ['status' => 'preparing', 'search' => request('search')]) }}"
                                                    role="tab">
                                                    <i class="ri-loader-4-line me-1 align-bottom"></i><span class="d-none d-sm-inline"> Preparing</span><span class="d-sm-none">Prep</span>
                                                    <span
                                                        class="badge bg-warning align-middle ms-1">{{ $preparingCount }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item flex-shrink-0">
                                                <a class="nav-link {{ request('status') == 'ready' ? 'active' : '' }} py-3 Ready"
                                                    href="{{ route('orderhistory', ['status' => 'ready', 'search' => request('search')]) }}" role="tab">
                                                    <i class="ri-checkbox-circle-line me-1 align-bottom"></i><span class="d-none d-sm-inline"> Ready</span><span class="d-sm-none">Ready</span>
                                                    <span class="badge bg-info align-middle ms-1">{{ $readyCount }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item flex-shrink-0">
                                                <a class="nav-link {{ request('status') == 'delivered' ? 'active' : '' }} py-3 Delivered"
                                                    href="{{ route('orderhistory', ['status' => 'delivered', 'date_range' => request('date_range', 'all'), 'search' => request('search')]) }}"
                                                    role="tab">
                                                    <i class="ri-truck-line me-1 align-bottom"></i><span class="d-none d-sm-inline"> Delivered</span><span class="d-sm-none">Deliv</span>
                                                    <span
                                                        class="badge bg-success align-middle ms-1">{{ $deliveredCount }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item flex-shrink-0">
                                                <a class="nav-link {{ request('status') == 'cancel' ? 'active' : '' }} py-3 text-muted"
                                                    href="{{ route('orderhistory', ['status' => 'cancel', 'date_range' => request('date_range', 'all'), 'search' => request('search')]) }}" role="tab">
                                                    <i class="ri-close-circle-line me-1 align-bottom"></i><span class="d-none d-sm-inline"> Canceled</span><span class="d-sm-none">Cancel</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive table-card mb-2">
                        <table class="table table-nowrap align-middle" id="orderTable">
                            <thead class="text-muted table-light">
                                <tr class="text-uppercase">
                                    {{-- <th scope="col" style="width: 25px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAll" value="option">
                                            <label class="form-check-label" for="checkAll"></label>
                                        </div>
                                    </th> --}}
                                    <th class="sort" data-sort="order_id">Order ID</th>
                                    <th class="sort" data-sort="customer">Customer</th>
                                    <th class="sort" data-sort="product">Product</th>
                                    <th class="sort" data-sort="placed_by">Placed By</th>
                                    <th class="sort" data-sort="delivered_by">Delivered By</th>
                                    <th class="sort" data-sort="order_date">Order Date</th>
                                    <th class="sort" data-sort="ready_time">Ready Time</th>
                                    <th class="sort" data-sort="reach_client_time">Reach Client Time</th>
                                    <th class="sort" data-sort="type">Type</th>
                                    {{-- <th scope="col">Delivery Address</th> --}}
                                    <th class="sort" data-sort="status">Status</th>
                                    <th class="sort" data-sort="action">Action</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse($orders as $order)
                                    <tr>
                                        {{-- <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="" id="cardtableCheck{{ $order->id }}">
                                                <label class="form-check-label" for="cardtableCheck{{ $order->id }}"></label>
                                            </div>
                                        </td> --}}
                                        <td class="order_id"><a href="{{ route('orderdetails', $order->id) }}" class="fw-semibold">#{{ $order->id }}</a></td>
                                        <td class="customer">{{ $order->customer->name }}</td>
                                        <td class="product">
                                            @if ($order->products->isNotEmpty())
                                                <ul class="list-unstyled mb-0">
                                                    @foreach($order->products as $product)
                                                        <li>{{ $product->name }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                No products
                                            @endif
                                        </td>
                                        <td class="placed_by">{{ $order->order_placed_by ?? 'N/A' }}</td>
                                        <td class="delivered_by">{{ $order->delivered_by ?? 'N/A' }}</td>
                                        <td class="order_date">
                                            @if($order->order_date)
                                                {{ $order->order_date->format('d M, Y') }}
                                                @if($order->order_time)
                                                    <br><small class="text-muted">{{ $order->order_time->format('h:i A') }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td class="ready_time">
                                            @if($order->item_ready_at)
                                                {{ $order->item_ready_at->format('h:i A') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="reach_client_time">
                                            @if ($order->pickup_delivery_date && $order->pickup_delivery_time)
                                                {{ $order->pickup_delivery_date->format('d M, Y') }}
                                                <br><small class="text-muted">{{ $order->pickup_delivery_time->format('h:i A') }}</small>
                                            @else
                                                <span class="text-muted">Not scheduled</span>
                                            @endif
                                        </td>
                                        <td class="type">
                                            @if($order->delivery_type === 'delivery')
                                                <div class="text-success">
                                                    <i class="ri-truck-line align-bottom me-1"></i> Delivery
                                                </div>
                                            @else
                                                <div class="text-primary">
                                                    <i class="ri-user-location-line align-bottom me-1"></i> Self Collect
                                                </div>
                                            @endif
                                        </td>
                                        {{-- <td>
                                            {{ $order->delivery_address ?? 'N/A' }}
                                        </td> --}}
                                        <td class="status">
                                            @php
                                                $statusClass =
                                                    [
                                                        'new' => 'bg-danger',
                                                        'preparing' => 'bg-warning',
                                                        'ready' => 'bg-info',
                                                        'delivered' => 'bg-success',
                                                        'cancel' => 'bg-secondary',
                                                    ][$order->status] ?? 'bg-warning';
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                                        </td>
                                        <td class="action">
                                            <div class="d-flex flex-column flex-sm-row gap-1">
                                                <a href="{{ route('orderdetails', $order->id) }}" class="btn btn-sm btn-info d-flex align-items-center justify-content-center" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" title="View Details">
                                                    <i class="ri-eye-fill"></i>
                                                </a>
                                                @if(Auth::user()->role === 'superadmin')
                                                <a href="javascript:void(0);" class="btn btn-sm btn-danger d-flex align-items-center justify-content-center remove-item-btn" data-bs-toggle="modal" data-bs-target="#deleteOrder" data-order-id="{{ $order->id }}" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" title="Delete">
                                                    <i class="ri-delete-bin-5-fill"></i>
                                                </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No orders found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="noresult" style="display: none">
                            <div class="text-center">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                    colors="primary:#405189,secondary:#0ab39c" style="width:75px;height:75px">
                                </lord-icon>
                                <h5 class="mt-2">Sorry! No Result Found</h5>
                                <p class="text-muted">We've searched more than 150+ Orders We did
                                    not find any
                                    orders for you search.</p>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center justify-content-sm-end">
                        <div class="pagination-wrap hstack gap-2">
                            {{ $orders->links('vendor.pagination.bootstrap-4') }}
                        </div>
                    </div>

                    <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-light p-3">
                                    <h5 class="modal-title" id="exampleModalLabel">&nbsp;</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                                        id="close-modal"></button>
                                </div>
                                <form class="tablelist-form" autocomplete="off" method="POST"
                                    action="{{ route('orders.store') }}">
                                    @csrf
                                    <div class="modal-body">
                                        <input type="hidden" id="id-field" />

                                        <div class="mb-3">
                                            <label for="customername-field" class="form-label">Customer Name</label>
                                            <select class="form-control" data-trigger name="customer_id"
                                                id="customername-field" required>
                                                <option value="">Select Customer</option>
                                                @foreach (\App\Models\Customer::all() as $customer)
                                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="placed-by-field" class="form-label">Placed By (PIC)</label>
                                            <input type="text" id="placed-by-field" name="order_placed_by" class="form-control"
                                                placeholder="Enter name of person who placed the order" />
                                        </div>

                                        <div class="mb-3">
                                            <label for="productname-field" class="form-label">Product</label>
                                            <select class="form-control" data-trigger name="products[0][id]"
                                                id="productname-field" required>
                                                <option value="">Select Product</option>
                                                @foreach (\App\Models\Product::where('stock', '>', 0)->get() as $product)
                                                    <option value="{{ $product->id }}">{{ $product->name }} (Stock:
                                                        {{ $product->stock }})</option>
                                                @endforeach
                                            </select>
                                            <div class="d-flex align-items-center mt-2">
                                                <label for="products[0][quantity]" class="form-label me-2 mb-0">Quantity:</label>
                                                <input type="number" name="products[0][quantity]" value="1" min="1" class="form-control w-25">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="date-field" class="form-label">Order Date</label>
                                            <input type="date" id="date-field" name="order_date" class="form-control"
                                                data-provider="flatpickr" required data-date-format="Y-m-d"
                                                value="{{ date('Y-m-d') }}" placeholder="Select date" />
                                        </div>

                                        <div class="mb-3">
                                            <label for="time-field" class="form-label">Order Time</label>
                                            <input type="time" id="time-field" name="order_time" class="form-control"
                                                data-provider="flatpickr" data-enable-time="true" data-no-calendar="true"
                                                data-date-format="H:i" placeholder="Select time" />
                                        </div>

                                        <div class="mb-3">
                                            <label for="delivery-time-field" class="form-label">Delivery Time</label>
                                            <input type="datetime-local" id="delivery-time-field" name="delivery_time" class="form-control"
                                                data-provider="flatpickr" data-date-format="Y-m-d H:i"
                                                data-enable-time="true" placeholder="Select delivery date and time" />
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label d-block">Delivery Type <span class="text-danger">*</span></label>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="delivery_type" 
                                                    id="delivery_type_delivery" value="delivery" checked required>
                                                <label class="form-check-label" for="delivery_type_delivery">
                                                    <i class="ri-truck-line me-1"></i>Delivery
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="delivery_type" 
                                                    id="delivery_type_self" value="self_collect" required>
                                                <label class="form-check-label" for="delivery_type_self">
                                                    <i class="ri-user-location-line me-1"></i>Self Collect
                                                </label>
                                            </div>
                                        </div>

                                        <div class="row gy-4 mb-3">
                                            <div class="col-md-6">
                                                <div>
                                                    <label for="batch-number-field" class="form-label">Batch Number</label>
                                                    <input type="text" id="batch-number-field" name="products[0][batch_number]" class="form-control"
                                                        placeholder="Enter batch number" />
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div>
                                                    <label for="patient-name-field" class="form-label">Patient Name</label>
                                                    <input type="text" id="patient-name-field" name="products[0][patient_name]" class="form-control"
                                                        placeholder="Enter patient name" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="remarks-field" class="form-label">Remarks</label>
                                            <textarea id="remarks-field" name="products[0][remarks]" class="form-control"
                                                placeholder="Enter product remarks"></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="order-remarks-field" class="form-label">Order Remarks</label>
                                            <textarea id="order-remarks-field" name="remarks" class="form-control"
                                                placeholder="Enter order remarks"></textarea>
                                        </div>

                                        <div>
                                            <label for="delivered-status" class="form-label">Status</label>
                                            <select class="form-control" data-trigger name="status" required
                                                id="delivered-status">
                                                <option value="">Status</option>
                                                <option value="new" selected>New</option>
                                                <option value="preparing">Preparing</option>
                                                <option value="ready">Ready</option>
                                                <option value="delivered">Delivered</option>
                                                <option value="cancel" {{ request('status') == 'cancel' ? 'selected' : '' }}>Canceled</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <div class="hstack gap-2 justify-content-end">
                                            <button type="button" class="btn btn-light"
                                                data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-success" id="add-btn">Add
                                                Order</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal -->
                    <div class="modal fade flip" id="deleteOrder" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-body p-5 text-center">
                                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                                        colors="primary:#405189,secondary:#f06548"
                                        style="width:90px;height:90px"></lord-icon>
                                    <div class="mt-4 text-center">
                                        <h4>You are about to delete a order ?</h4>
                                        <p class="text-muted fs-15 mb-4">Deleting your order will remove
                                            all of
                                            your information from our database.</p>
                                        <div class="hstack gap-2 justify-content-center remove">
                                            <button class="btn btn-link link-success fw-medium text-decoration-none"
                                                id="deleteRecord-close" data-bs-dismiss="modal"><i
                                                    class="ri-close-line me-1 align-middle"></i>
                                                Close</button>
                                            <form id="deleteOrderForm" method="POST" action="">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" id="delete-record">Yes,
                                                    Delete It</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end modal -->
                </div>
            </div>

        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize List.js for table filtering and sorting
            var orderList = new List('orderList', {
                valueNames: [
                    'order_id',
                    'customer', 
                    'product',
                    'placed_by',
                    'delivered_by',
                    'order_date',
                    'ready_time',
                    'reach_client_time',
                    'type',
                    'status',
                    'action'
                ]
            });

            // Handle delete order
            const deleteButtons = document.querySelectorAll('[data-order-id]');
            const deleteForm = document.getElementById('deleteOrderForm');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order-id');
                    // Use the correct route for deleting orders
                    deleteForm.action = "{{ route('orders.destroy', ':id') }}".replace(':id', orderId);
                });
            });

            // Initialize Flatpickr for date picker in the modal form
            if (typeof flatpickr !== 'undefined') {
                flatpickr("#date-field", {
                    dateFormat: "Y-m-d",
                });
                
                flatpickr("#time-field", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true
                });
                
                flatpickr("#delivery-date-field", {
                    dateFormat: "Y-m-d",
                });
                
                flatpickr("#delivery-time-field", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true
                });
            }
        });
    </script>
@endsection
