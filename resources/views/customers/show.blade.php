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
</style>

<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Customer Details</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                    <li class="breadcrumb-item active">Details</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

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

<div class="row">
    <div class="col-xl-9">
        <!-- Customer Information Card -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Customer Information</h5>
                    <div class="flex-shrink-0">
                        <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-primary">
                            <i class="ri-pencil-fill me-1 align-bottom"></i> Edit Customer
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3 text-center mb-4 mb-lg-0">
                        <div class="profile-user position-relative d-inline-block mx-auto mb-4">
                            @if($customer->profile_image)
                                <img src="{{ asset('assets/images/users/' . $customer->profile_image) }}" class="rounded-circle avatar-xl img-thumbnail user-profile-image" alt="user-profile-image">
                            @else
                                <div class="avatar-xl">
                                    <span class="avatar-title rounded-circle bg-soft-primary text-primary fs-24">
                                        {{ substr($customer->name, 0, 1) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <h5 class="fs-16 mb-1">{{ $customer->name }}</h5>
                        <p class="text-muted mb-3">Customer</p>
                        
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="mailto:{{ $customer->email }}" class="btn btn-primary btn-sm">
                                <i class="ri-mail-line me-1 align-bottom"></i> Email
                            </a>
                            <a href="tel:{{ $customer->phoneNo }}" class="btn btn-success btn-sm">
                                <i class="ri-phone-line me-1 align-bottom"></i> Call
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email</label>
                                    <p class="mb-0 fs-15">{{ $customer->email ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <p class="mb-0 fs-15">{{ $customer->phoneNo ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Gender</label>
                                    <p class="mb-0 fs-15">{{ ucfirst($customer->gender) ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Birthdate</label>
                                    <p class="mb-0 fs-15">{{ $customer->birthdate ? date('F d, Y', strtotime($customer->birthdate)) : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Person in Charge</label>
                                    <p class="mb-0 fs-15">
                                        @if($customer->userID)
                                            <a href="{{ route('users.show', $customer->userID) }}" class="text-primary">
                                                {{ $customer->user->username }} (ID: {{ $customer->userID }})
                                            </a>
                                        @else
                                            <span class="text-muted">Not Assigned</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Address</label>
                                    <p class="mb-0 fs-15">{{ $customer->address ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Section -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Order History</h5>
                    <div class="flex-shrink-0">
                        @if(Auth::user()->department == 'Medical Affairs' || Auth::user()->department == 'Business Development' || Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin')
                        <a href="{{ route('neworder') }}?customer_id={{ $customer->id }}" class="btn btn-success">
                            <i class="ri-add-line me-1 align-bottom"></i> New Order
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle table-borderless mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col">Order ID</th>
                                <th scope="col">Date</th>
                                <th scope="col">Status</th>
                                <th scope="col">Total</th>
                                <th scope="col">Products</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('orders.show', $order->id) }}" class="fw-medium link-primary">#{{ $order->id }}</a>
                                </td>
                                <td>{{ $order->created_at->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $order->status_color }} fs-12">
                                        <i class="ri-{{ 
                                            $order->status == 'new' ? 'shopping-bag-3-line' : 
                                            ($order->status == 'preparing' ? 'tools-line' : 
                                            ($order->status == 'ready' ? 'check-double-line' : 'truck-line')) 
                                        }} align-middle me-1"></i>
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td>₱{{ number_format($order->total, 2) }}</td>
                                <td>
                                    <span class="badge bg-soft-primary text-primary">
                                        {{ $order->products->count() }} items
                                    </span>
                                </td>
                                <td>
                                    <div class="hstack gap-2">
                                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-soft-info" data-bs-toggle="tooltip" data-bs-placement="top" title="View Details">
                                            <i class="ri-eye-fill"></i>
                                        </a>
                                        @if($order->status != 'delivered')
                                        <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-sm btn-soft-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Order">
                                            <i class="ri-pencil-fill"></i>
                                        </a>
                                        @endif
                                        <a href="{{ route('orders.prf', $order->id) }}" class="btn btn-sm btn-soft-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="View PRF">
                                            <i class="ri-file-list-3-line"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="py-4">
                                        <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                                        <h5 class="mt-4">No Orders Found</h5>
                                        <p class="text-muted mb-4">This customer hasn't placed any orders yet.</p>
                                        @if(Auth::user()->department == 'Medical Affairs' || Auth::user()->department == 'Business Development' || Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin')
                                        <a href="{{ route('neworder') }}?customer_id={{ $customer->id }}" class="btn btn-success">
                                            <i class="ri-add-line me-1 align-bottom"></i> Create First Order
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3">
        <!-- Order Statistics Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Order Statistics</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-primary rounded-circle">
                                    <i class="ri-shopping-bag-line"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="fs-14 mb-1">Total Orders</h6>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary fs-12">{{ $customer->orders->count() }}</span>
                                <span class="text-muted ms-2">orders</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-warning rounded-circle">
                                    <i class="ri-tools-line"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="fs-14 mb-1">Active Orders</h6>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-warning fs-12">{{ $customer->orders->whereNotIn('status', ['delivered'])->count() }}</span>
                                <span class="text-muted ms-2">in progress</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-success rounded-circle">
                                    <i class="ri-truck-line"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="fs-14 mb-1">Delivered Orders</h6>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success fs-12">{{ $customer->orders->where('status', 'delivered')->count() }}</span>
                                <span class="text-muted ms-2">completed</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-info rounded-circle">
                                    <i class="ri-calendar-check-line"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="fs-14 mb-1">Last Order</h6>
                            <div class="d-flex align-items-center">
                                @php
                                    $lastOrder = $customer->orders->sortByDesc('created_at')->first();
                                @endphp
                                @if($lastOrder)
                                    <span class="badge bg-info fs-12">{{ $lastOrder->created_at->format('M d, Y') }}</span>
                                    <span class="text-muted ms-2">{{ $lastOrder->status }}</span>
                                @else
                                    <span class="text-muted">No orders yet</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endsection 