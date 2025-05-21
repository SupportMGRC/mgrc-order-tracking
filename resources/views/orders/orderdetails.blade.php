@extends('layouts.master')

@section('content')
<style>
    /* Order Progress Bar Styles */
    .order-progress-bar {
        padding: 20px 10px;
    }
    
    .order-track-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
        flex: 1;
    }
    
    .order-track-icon {
        height: 50px;
        width: 50px;
        border-radius: 50%;
        background-color: #f5f5f5;
        border: 2px solid #e0e0e0;
        color: #9e9e9e;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        z-index: 10;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
    }
    
    .order-track-text {
        margin-top: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #757575;
        transition: all 0.3s ease;
    }
    
    .order-track-step.completed .order-track-icon {
        background-color: #d4edda;
        border-color: #28a745;
        color: #28a745;
        box-shadow: 0 0 10px rgba(40, 167, 69, 0.2);
    }
    
    .order-track-step.active .order-track-icon {
        background-color: #cce5ff;
        border-color: #007bff;
        color: #007bff;
        box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
        transform: scale(1.1);
    }
    
    .order-track-step.completed .order-track-text {
        color: #28a745;
    }
    
    .order-track-step.active .order-track-text {
        color: #007bff;
        font-weight: 600;
    }
    
    .progress {
        position: absolute;
        top: 25px;
        left: 0;
        right: 0;
        height: 5px;
        margin-bottom: 0;
        background-color: #e9ecef;
        z-index: 0;
    }
    
    .track-line {
        height: 2px;
        background-color: #e0e0e0;
        position: absolute;
        top: 25px;
        left: 0;
        right: 0;
        margin-top: 23px;
        z-index: 0;
    }
    
    /* Enhanced progress bar styles */
    .progress-animated .progress-bar {
        transition: width 1.5s ease;
    }
    
    .order-track-step .order-track-icon:hover {
        transform: scale(1.15);
        box-shadow: 0 0 15px rgba(0, 123, 255, 0.4);
    }
    
    .order-track-step.completed .order-track-icon:after {
        content: '✓';
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #28a745;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Order Details</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('orderhistory') }}">Order</a></li>
                    <li class="breadcrumb-item active">Order Details</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Order Status Guide Card -->
<div class="card mb-4">
    <div class="card-header">
        <div class="d-sm-flex align-items-center justify-content-between">
            <h5 class="card-title flex-grow-1 mb-0">Order Status</h5>
            
            <!-- Current Status Badge -->
            <div class="ms-2">
                <span class="badge fs-13 bg-{{ 
                    $order->status == 'new' ? 'info' : 
                    ($order->status == 'preparing' ? 'warning' : 
                    ($order->status == 'ready' ? 'primary' : 'success')) 
                }} px-3 py-2">
                    <i class="ri-{{ 
                        $order->status == 'new' ? 'shopping-bag-3-line' : 
                        ($order->status == 'preparing' ? 'tools-line' : 
                        ($order->status == 'ready' ? 'check-double-line' : 'truck-line')) 
                    }} align-middle me-1"></i>
                    Current Status: {{ ucfirst($order->status) }}
                </span>
            </div>
        </div>
    </div>
    
    <div class="card-body border-bottom">
        <!-- Progress tracker -->
        <div class="order-progress-bar position-relative">
            <div class="track-line"></div>
            <div style="height: 5px;">
                <div class="progress-bar {{ 
                    $order->status == 'new' ? 'bg-info' : 
                    ($order->status == 'preparing' ? 'bg-warning' : 
                    ($order->status == 'ready' ? 'bg-primary' : 'bg-success')) 
                }}" role="progressbar" style="width: {{ 
                    $order->status == 'new' ? '0%' : 
                    ($order->status == 'preparing' ? '33%' : 
                    ($order->status == 'ready' ? '67%' : '100%')) 
                }};" aria-valuenow="{{ 
                    $order->status == 'new' ? '0' : 
                    ($order->status == 'preparing' ? '33' : 
                    ($order->status == 'ready' ? '67' : '100')) 
                }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="position-relative mt-2">
                <div class="d-flex justify-content-between">
                    <div class="order-track-step {{ $order->status == 'new' ? 'active' : 'completed' }}"
                         data-bs-toggle="tooltip" data-bs-placement="top" title="Order received and pending processing">
                        <div class="order-track-icon">
                            <i class="ri-shopping-bag-3-line"></i>
                        </div>
                        <span class="order-track-text">New</span>
                    </div>
                    <div class="order-track-step {{ 
                        $order->status == 'preparing' ? 'active' : 
                        ($order->status == 'ready' || $order->status == 'delivered' ? 'completed' : '') 
                    }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Order is being prepared with batch information">
                        <div class="order-track-icon">
                            <i class="ri-tools-line"></i>
                        </div>
                        <span class="order-track-text">Preparing</span>
                    </div>
                    <div class="order-track-step {{ 
                        $order->status == 'ready' ? 'active' : 
                        ($order->status == 'delivered' ? 'completed' : '') 
                    }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Order is ready for pickup or delivery">
                        <div class="order-track-icon">
                            <i class="ri-check-double-line"></i>
                        </div>
                        <span class="order-track-text">Ready</span>
                    </div>
                    <div class="order-track-step {{ $order->status == 'delivered' ? 'completed' : '' }}"
                         data-bs-toggle="tooltip" data-bs-placement="top" title="Order has been delivered to the customer">
                        <div class="order-track-icon">
                            <i class="ri-truck-line"></i>
                        </div>
                        <span class="order-track-text">Delivered</span>
                    </div>
                </div>
            </div>
        </div>
        
        @php
            // Calculate if any batch info exists
            $anyBatchInfo = false;
            foreach($order->products as $product) {
                if(!empty($product->pivot->batch_number) || !empty($product->pivot->prepared_by) || !empty($product->pivot->qc_document_number)) {
                    $anyBatchInfo = true;
                    break;
                }
            }
        @endphp
        
        @if($order->status != 'delivered')
        <div class="text-center">
            @if($order->status === 'new' && $anyBatchInfo)
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#markPreparingModal">
                <i class="ri-tools-line align-bottom me-1"></i> Mark as Preparing
            </button>
            @elseif($order->status === 'preparing')
            @if(Auth::user()->department === 'Quality' || Auth::user()->department === 'Cell Lab' || Auth::user()->role === 'superadmin')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#markReadyModal">
                <i class="ri-check-double-line align-bottom me-1"></i> Mark as Ready
            </button>
            @endif
            @elseif($order->status === 'ready')
            @if(Auth::user()->department === 'Admin & Human Resource' || Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin')
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#markDeliveredModal">
                <i class="ri-truck-line align-bottom me-1"></i> Mark as Delivered
            </button>
            @endif
            @endif
            
            <button type="button" class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#deleteOrder">
                <i class="ri-delete-bin-line align-bottom me-1"></i> Delete Order
            </button>
        </div>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-xl-9">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Order #{{ $order->id }}</h5>
                    <div class="flex-shrink-0">
                        <a href="{{ route('orders.prf', $order->id) }}" class="btn btn-primary btn-sm me-2">
                            <i class="ri-file-list-3-line align-middle me-1"></i> PRF
                        </a>
                        <a href="{{ route('orders.batch.edit', $order->id) }}" class="btn btn-success btn-sm">
                            <i class="ri-settings-3-line align-middle me-1"></i> Manage All Batches
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @php
                    // Calculate total order quantity
                    $totalOrderQuantity = 0;
                    foreach($order->products as $product) {
                        $totalOrderQuantity += $product->pivot->quantity;
                    }
                    
                    // Check if all products have batch information
                    $allHaveBatchInfo = true;
                    foreach($order->products as $product) {
                        if(empty($product->pivot->batch_number)) {
                            $allHaveBatchInfo = false;
                            break;
                        }
                    }
                    
                    // Get first product batch info for display purposes
                    $firstProduct = $order->products->first();
                    $firstProductBatchNumber = $firstProduct ? $firstProduct->pivot->batch_number : null;
                @endphp

                @if(!$allHaveBatchInfo)
                <div class="alert alert-warning mb-4" role="alert">
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                            <i class="ri-alert-line fs-24"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading">Batch Information Incomplete</h5>
                            <p class="mb-0">Some products in this order don't have batch information assigned. 
                                <a href="{{ route('orders.batch.edit', $order->id) }}" class="btn btn-sm btn-warning ms-3">
                                    <i class="ri-edit-2-line"></i> Assign Batch Information
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <div class="table-responsive table-card">
                    <table class="table table-nowrap align-middle table-borderless mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col">Product Details</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">Batch Number</th>
                                <th scope="col">Patient Name</th>
                                <th scope="col">Remarks</th>
                                <th scope="col">QC Document No.</th>
                                <th scope="col">Prepared By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->products as $product)
                            <tr>
                                <td>
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <h5 class="fs-16"><a href="#" class="link-primary">{{ $product->name }}</a></h5>
                                            @if($product->color)
                                                <p class="text-muted mb-0">Color: <span class="fw-medium">{{ $product->color }}</span></p>
                                            @endif
                                            @if($product->size)
                                                <p class="text-muted mb-0">Size: <span class="fw-medium">{{ $product->size }}</span></p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $product->pivot->quantity }}</td>
                                <td>
                                    @if($product->pivot->batch_number)
                                        <span class="badge bg-primary">{{ $product->pivot->batch_number }}</span>
                                    @else
                                        <span class="badge bg-danger">Not Set</span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->pivot->patient_name)
                                        <span class="fw-medium">{{ $product->pivot->patient_name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->pivot->remarks)
                                        <span class="fw-medium">{{ $product->pivot->remarks }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->pivot->qc_document_number)
                                        <span class="fw-medium">{{ $product->pivot->qc_document_number }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->pivot->prepared_by)
                                        <span class="fw-medium">{{ $product->pivot->prepared_by }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--end card-->

        @if($order->remarks)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-message-2-line align-bottom me-1 text-muted"></i> Remarks</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">{{ $order->remarks }}</p>
            </div>
        </div>
        @endif
        <!--end card-->
    </div>
    <!--end col-->
    <div class="col-xl-3">
        <div class="card">
            <div class="card-header">
                <div class="d-flex">
                    <h5 class="card-title flex-grow-1 mb-0">Customer Details</h5>
                    <div class="flex-shrink-0">
                        <a href="{{ route('customers.show', $order->customer_id) }}" class="link-secondary">View Profile</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 vstack gap-3">
                    <li>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                @if($order->customer->profile_image)
                                    <img src="{{ asset('assets/images/users/' . $order->customer->profile_image) }}" alt="" class="avatar-sm rounded">
                                @else
                                    <div class="avatar-sm">
                                        <span class="avatar-title rounded bg-soft-primary text-primary">
                                            {{ substr($order->customer->name, 0, 1) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="fs-15 mb-1">{{ $order->customer->name }}</h6>
                                <p class="text-muted mb-0">Customer</p>
                            </div>
                        </div>
                    </li>
                    <li><i class="ri-mail-line me-2 align-middle text-muted fs-16"></i>{{ $order->customer->email ?? 'N/A' }}
                    </li>
                    <li><i class="ri-phone-line me-2 align-middle text-muted fs-16"></i>{{ $order->customer->phoneNo ?? 'N/A' }}</li>
                    @if($order->order_placed_by)
                    <li><i class="ri-user-received-2-line me-2 align-middle text-muted fs-16"></i>Placed by: {{ $order->order_placed_by }}</li>
                    @endif
                    @if($order->delivered_by)
                    <li><i class="ri-truck-line me-2 align-middle text-muted fs-16"></i>Delivered by: {{ $order->delivered_by }}</li>
                    @endif
                </ul>
            </div>
        </div>
        <!--end card-->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-map-pin-line align-middle me-1 text-muted"></i> Shipping Address
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled vstack gap-2 mb-0">
                    <li class="fw-medium fs-15">{{ $order->customer->name }}</li>
                    {{-- <li>{{ $order->customer->phone ?? 'N/A' }}</li> --}}
                    <li>{{ $order->customer->shipping_address ?? $order->customer->address ?? 'N/A' }}</li>
                    {{-- <li>{{ $order->customer->shipping_city ?? $order->customer->city ?? 'N/A' }} - {{ $order->customer->shipping_zip_code ?? $order->customer->zip_code ?? 'N/A' }}</li> --}}
                    {{-- <li>{{ $order->customer->shipping_country ?? $order->customer->country ?? 'N/A' }}</li> --}}
                </ul>
            </div>
        </div>
        <!--end card-->
    </div>
    <!--end col-->
</div>
<!--end row-->

<!-- Modal -->
<div class="modal fade flip" id="deleteOrder" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-5 text-center">
                <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                    colors="primary:#405189,secondary:#f06548"
                    style="width:90px;height:90px"></lord-icon>
                <div class="mt-4 text-center">
                    <h4>You are about to delete order #{{ $order->id }}?</h4>
                    <p class="text-muted fs-15 mb-4">Deleting your order will remove
                        all of
                        your information from our database.</p>
                    <div class="hstack gap-2 justify-content-center remove">
                        <button class="btn btn-link link-success fw-medium text-decoration-none"
                            id="deleteRecord-close" data-bs-dismiss="modal"><i
                                class="ri-close-line me-1 align-middle"></i>
                            Close</button>
                        <form id="deleteOrderForm" method="POST" action="{{ route('orders.destroy', $order->id) }}">
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

<!-- Modal to Mark as Preparing -->
<div class="modal fade" id="markPreparingModal" tabindex="-1" aria-labelledby="markPreparingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-soft-warning">
                <h5 class="modal-title" id="markPreparingModalLabel">Mark Order as Preparing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('orders.update.status', $order->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="ri-information-line me-2"></i> Marking as "Preparing" means batch information has been assigned and order preparation is in progress.
                    </div>
                    <p>Are you sure you want to change the status to "Preparing"?</p>
                    <input type="hidden" name="status" value="preparing">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Yes, Mark as Preparing</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal to Mark as Ready -->
<div class="modal fade" id="markReadyModal" tabindex="-1" aria-labelledby="markReadyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-soft-primary">
                <h5 class="modal-title" id="markReadyModalLabel">Mark Order as Ready</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('orders.mark.ready', $order->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="ri-information-line me-2"></i> Marking as "Ready" means the order has been prepared and is ready for delivery or pickup.
                    </div>
                    <p>Are you sure the order is ready for delivery or pickup?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Yes, Mark as Ready</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal to Mark as Delivered -->
<div class="modal fade" id="markDeliveredModal" tabindex="-1" aria-labelledby="markDeliveredModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-soft-success">
                <h5 class="modal-title" id="markDeliveredModalLabel">Mark Order as Delivered</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('orders.update.status', $order->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="ri-information-line me-2"></i> Marking as "Delivered" means the order has been successfully delivered to the customer.
                    </div>
                    <div class="mb-3">
                        <label for="dispatcher" class="form-label">Delivered/Received By</label>
                        <input type="text" class="form-control" id="dispatcher" name="dispatcher" required 
                               placeholder="Enter name of person who delivered or received the order">
                        <small class="text-muted">This records who delivered or received the order, not who placed it.</small>
                    </div>
                    <div class="mb-3">
                        <label for="delivery_datetime" class="form-label">Delivery Date & Time</label>
                        <input type="text" class="form-control flatpickr-input" id="delivery_datetime" name="delivery_datetime" 
                               data-provider="flatpickr" data-date-format="d.m.Y H:i" data-enable-time="true" required>
                    </div>
                    <input type="hidden" name="status" value="delivered">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Delivery</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Flatpickr for delivery datetime picker
        if (typeof flatpickr !== 'undefined') {
            flatpickr("#delivery_datetime", {
                enableTime: true,
                dateFormat: "d.m.Y H:i",
                defaultDate: new Date()
            });
        }
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endsection
