@extends('layouts.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
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

    .spinning {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Simple Mobile Responsive Styles */
    @media (max-width: 768px) {
        /* Progress bar for mobile */
        .order-track-icon {
            height: 40px;
            width: 40px;
            font-size: 16px;
        }
        
        .order-track-text {
            font-size: 12px;
            text-align: center;
        }
        
        .order-progress-bar {
            padding: 15px 5px;
        }
        
        /* Cards and content */
        .card {
            margin-bottom: 15px;
        }
        
        .card-body {
            padding: 15px;
        }
        
        /* Buttons */
        .btn-sm {
            padding: 8px 12px;
            font-size: 12px;
        }
        
        .btn {
            margin-bottom: 8px;
            width: 100%;
        }
        
        /* Table responsive */
        .table-responsive {
            font-size: 14px;
        }
        
        .table td, .table th {
            padding: 8px 4px;
            font-size: 13px;
            word-wrap: break-word;
        }
        
        /* Page title */
        .page-title-box h4 {
            font-size: 18px;
        }
        
        .breadcrumb {
            font-size: 12px;
        }
        
        /* Badges */
        .badge {
            font-size: 11px;
            padding: 4px 8px;
        }
        
        /* Customer details */
        .avatar-sm {
            width: 32px;
            height: 32px;
        }
        
        /* Photo upload section */
        .form-control {
            font-size: 16px; /* Prevents zoom on iOS */
        }
        
        /* Action buttons container */
        .d-flex.gap-2 {
            flex-direction: column;
        }
        
        .d-flex.gap-2 .btn {
            margin-bottom: 5px;
        }
        
        /* Header actions */
        .card-header .d-flex {
            flex-direction: column;
            align-items: stretch !important;
        }
        
        .card-header .flex-shrink-0 {
            margin-top: 10px;
        }
        
        .card-header .flex-shrink-0 .btn {
            margin-bottom: 5px;
            margin-right: 0 !important;
        }
        
        /* Status badges in header */
        .d-sm-flex.align-items-center.justify-content-between {
            flex-direction: column;
            align-items: stretch !important;
        }
        
        .ms-2.d-flex.align-items-center.gap-3 {
            margin-top: 10px !important;
            margin-left: 0 !important;
            justify-content: center;
            gap: 10px !important;
        }
        
        /* Image display */
        img.img-fluid {
            max-width: 100%;
            height: auto;
        }
        
        /* Modal improvements */
        .modal-dialog {
            margin: 10px;
        }
        
        /* Form sections */
        .mb-3 {
            margin-bottom: 15px !important;
        }
        
        /* Text sizing */
        .fs-15, .fs-16 {
            font-size: 14px !important;
        }
        
        /* List items */
        .list-unstyled li {
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        /* Delivery section for mobile */
        .order-track-step:last-child .mt-2 {
            margin-top: 10px !important;
        }
        
        .order-track-step:last-child .text-center {
            font-size: 11px;
        }
    }
    
    /* Extra small mobile devices */
    @media (max-width: 576px) {
        .order-track-text {
            font-size: 10px;
        }
        
        .order-track-icon {
            height: 35px;
            width: 35px;
            font-size: 14px;
        }
        
        .table td, .table th {
            padding: 6px 2px;
            font-size: 12px;
        }
        
        .card-body {
            padding: 10px;
        }
        
        .btn-sm {
            padding: 6px 10px;
            font-size: 11px;
        }
        
        .badge.fs-13 {
            font-size: 10px !important;
            padding: 3px 6px;
        }
    }

    /* High-resolution mobile screens */
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
        .mobile-file-input {
            border-width: 1px;
        }
    }

    /* File input styling improvements */
    input[type="file"] {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    input[type="file"]:hover {
        border-color: #007bff;
        background-color: #f8f9fa;
    }

    /* Image preview container styling */
    #image-preview, 
    #image-preview-2, 
    #edit-image-preview {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 10px;
        background-color: #f8f9fa;
        text-align: center;
    }

    /* Upload progress styling */
    #upload-progress, 
    #upload-progress-2 {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background-color: #f8f9fa;
    }

    /* Table mobile optimizations */
    @media (max-width: 768px) {
        .table-mobile .badge {
            font-size: 10px;
            padding: 2px 6px;
        }
        
        .table-mobile .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }
        
        .table-mobile td .d-md-none {
            margin-top: 2px;
            padding: 2px 0;
        }
        
        .fs-14 {
            font-size: 13px !important;
        }
    }

    /* General mobile improvements */
    @media (max-width: 768px) {
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .row {
            margin-left: -5px;
            margin-right: -5px;
        }
        
        .row > * {
            padding-left: 5px;
            padding-right: 5px;
        }
        
        .alert {
            margin-bottom: 10px;
            padding: 10px;
            font-size: 14px;
        }
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Order Details</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('orderhistory') }}">Order</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('orderhistory') }}">Order History</a></li>
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
            
            <div class="ms-2 d-flex align-items-center gap-3">
                <!-- Item Ready Time Badge -->
                <span class="badge fs-13 bg-light text-dark px-3 py-2">
                    <i class="ri-time-line align-middle me-1"></i>
                    Ready Time: {{ $order->item_ready_at ? $order->item_ready_at->format('h:i A') : 'N/A' }}
                </span>

                <!-- Current Status Badge -->
                <span class="badge fs-13 bg-{{ 
                    $order->status == 'new' ? 'light text-dark' : 
                    ($order->status == 'preparing' ? 'warning' : 
                    ($order->status == 'ready' ? 'primary' : 
                    ($order->status == 'delivered' ? 'success' : ($order->status == 'cancel' ? 'danger' : 'secondary')))) 
                }} px-3 py-2">
                    <i class="ri-{{ 
                        $order->status == 'new' ? 'shopping-bag-3-line' : 
                        ($order->status == 'preparing' ? 'tools-line' : 
                        ($order->status == 'ready' ? 'check-double-line' : 
                        ($order->status == 'delivered' ? 'truck-line' : 'close-circle-line'))) 
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
                    $order->status == 'new' ? 'bg-light' : 
                    ($order->status == 'preparing' ? 'bg-warning' : 
                    ($order->status == 'ready' ? 'bg-primary' : 
                    ($order->status == 'delivered' ? 'bg-success' : ($order->status == 'cancel' ? 'bg-danger' : 'bg-secondary')))) 
                }}" role="progressbar" style="width: {{ 
                    $order->status == 'new' ? '0%' : 
                    ($order->status == 'preparing' ? '33%' : 
                    ($order->status == 'ready' ? '67%' : 
                    ($order->status == 'delivered' ? '100%' : ($order->status == 'cancel' ? '100%' : '100%')))) 
                }};" aria-valuenow="{{ 
                    $order->status == 'new' ? '0' : 
                    ($order->status == 'preparing' ? '33' : 
                    ($order->status == 'ready' ? '67' : 
                    ($order->status == 'delivered' ? '100' : ($order->status == 'cancel' ? '100' : '100')))) 
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
                            <div class="mt-2 text-center">
                                <div class="text-{{ $order->delivery_type === 'delivery' ? 'success' : 'primary' }} fw-medium">
                                    <i class="ri-{{ $order->delivery_type === 'delivery' ? 'truck-line' : 'user-location-line' }} align-bottom me-1"></i>
                                    {{ $order->delivery_type === 'delivery' ? 'Delivery' : 'Self Collect' }}
                                </div>
                                <small class="text-muted d-block mt-1">
                                    {{ $order->pickup_delivery_date ? $order->pickup_delivery_date->format('d M, Y') : '' }}
                                    <br>
                                    {{ $order->pickup_delivery_time ? $order->pickup_delivery_time->format('h:i A') : '' }}
                                </small>
                            </div>
                    </div>
                    <div class="order-track-step {{ $order->status == 'cancel' ? 'active' : '' }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Order has been canceled">
                        <div class="order-track-icon">
                            <i class="ri-close-circle-line"></i>
                        </div>
                        <span class="order-track-text">Canceled</span>
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
        
        @if($order->status != 'delivered' && $order->status != 'cancel')
        <div class="text-center">
            @if($order->status === 'new')
                @if(Auth::user()->department === 'Quality' || Auth::user()->department === 'Cell Lab' || Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin')
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#markPreparingModal">
                    <i class="ri-tools-line align-bottom me-1"></i> Mark as Preparing
                </button>
                @endif
            @elseif($order->status === 'ready')
            @if(Auth::user()->department === 'Admin & Human Resource' || Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin')
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#markDeliveredModal">
                <i class="ri-truck-line align-bottom me-1"></i> Mark as Delivered
            </button>
            @endif
            @endif
            @if(
                Auth::user()->role === 'admin' || 
                Auth::user()->role === 'superadmin' || 
                Auth::user()->id == $order->order_placed_by ||
                Auth::user()->name == $order->order_placed_by
            )
            <button type="button" class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                <i class="ri-close-circle-line align-bottom me-1"></i> Cancel Order
            </button>
            @endif
        </div>
        @endif
    </div>
</div>

@if($order->status === 'preparing')
    @php
        // Check if all products are ready
        $allProductsReady = true;
        $totalProducts = count($order->products);
        foreach($order->products as $product) {
            if($product->pivot->status !== 'ready') {
                $allProductsReady = false;
                break;
            }
        }
    @endphp

    @if($allProductsReady && $totalProducts > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ri-image-line align-middle me-1 text-muted"></i> Order Photo</h5>
        </div>
        <div class="card-body">
            @if($order->order_photo)
                <div class="mb-3">
                    <img src="{{ asset('storage/order_photos/' . $order->order_photo) }}" alt="Order Photo" class="img-fluid rounded shadow-sm" style="max-width: 300px; cursor:pointer;" data-bs-toggle="modal" data-bs-target="#viewOrderPhotoModal">
                </div>
                <div class="d-flex gap-2 mb-2">
                    <!-- Edit button shows upload form -->
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#editPhotoForm">Edit</button>
                    <!-- Delete button shows modal -->
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deletePhotoModal">Delete</button>
                </div>
                <div class="collapse" id="editPhotoForm">
                    <form action="{{ route('orders.upload.photo', $order->id) }}" method="POST" enctype="multipart/form-data" id="editPhotoUploadForm">
                        @csrf
                        <div class="mb-3">
                            <label for="order_photo_edit" class="form-label">Replace Photo <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="file" 
                                       class="form-control" 
                                       id="order_photo_edit" 
                                       name="order_photo" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/heic,image/heif" 
                                       required>
                                <div class="form-text text-muted">
                                    <small>Supports: JPEG, PNG, GIF, WebP, HEIC. Max size: 50MB</small>
                                </div>
                            </div>
                            
                            <!-- Image Preview for Edit -->
                            <div id="edit-image-preview" class="mt-3" style="display: none;">
                                <img id="edit-preview-img" src="" alt="Preview" class="img-fluid rounded shadow-sm" style="max-width: 200px; max-height: 200px;">
                                <div class="mt-2">
                                    <small class="text-muted">New photo preview</small>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ri-upload-cloud-2-line align-middle me-1"></i> Upload New Photo
                        </button>
                    </form>
                </div>
                <!-- Modal for viewing order photo -->
                <div class="modal fade" id="viewOrderPhotoModal" tabindex="-1" aria-labelledby="viewOrderPhotoModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="viewOrderPhotoModalLabel">Order Photo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img src="{{ asset('storage/order_photos/' . $order->order_photo) }}" alt="Order Photo" class="img-fluid rounded shadow">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modal for deleting order photo -->
                <div class="modal fade" id="deletePhotoModal" tabindex="-1" aria-labelledby="deletePhotoModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-soft-danger">
                                <h5 class="modal-title" id="deletePhotoModalLabel">Delete Order Photo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('orders.delete.photo', $order->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="modal-body">
                                    <p>Are you sure you want to delete this photo? This action cannot be undone.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Delete Photo</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-info mb-3">
                    <i class="ri-information-line me-2"></i> All products are ready! Please upload a photo of the completed order.
                </div>
                <form action="{{ route('orders.upload.photo', $order->id) }}" method="POST" enctype="multipart/form-data" id="photoUploadForm">
                    @csrf
                    <div class="mb-3">
                        <label for="order_photo" class="form-label">Upload Photo <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="file" 
                                   class="form-control" 
                                   id="order_photo" 
                                   name="order_photo" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/heic,image/heif" 
                                   required>
                            <div id="file-size-info" class="form-text text-muted">
                                <small>Supports: JPEG, PNG, GIF, WebP, HEIC. Max size: 50MB</small>
                            </div>
                        </div>
                        
                        <!-- Image Preview -->
                        <div id="image-preview" class="mt-3" style="display: none;">
                            <img id="preview-img" src="" alt="Preview" class="img-fluid rounded shadow-sm" style="max-width: 300px; max-height: 300px;">
                            <div class="mt-2">
                                <small class="text-muted">Preview - Image will be uploaded when you click submit</small>
                            </div>
                        </div>
                        
                        <!-- Upload Progress -->
                        <div id="upload-progress" class="mt-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">Uploading photo...</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="upload-btn">
                        <i class="ri-upload-cloud-2-line align-middle me-1"></i> Upload Photo
                    </button>
                </form>
            @endif
        </div>
    </div>
    @endif
@endif

@if($order->status === 'ready')
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ri-image-line align-middle me-1 text-muted"></i> Order Photo</h5>
    </div>
    <div class="card-body">
        @if($order->order_photo)
            <div class="mb-3">
                <img src="{{ asset('storage/order_photos/' . $order->order_photo) }}" alt="Order Photo" class="img-fluid rounded shadow-sm" style="max-width: 300px; cursor:pointer;" data-bs-toggle="modal" data-bs-target="#viewOrderPhotoModal">
            </div>
            <div class="d-flex gap-2 mb-2">
                <!-- Edit button shows upload form -->
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#editPhotoForm">Edit</button>
                <!-- Delete button shows modal -->
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deletePhotoModal">Delete</button>
            </div>
            <div class="collapse" id="editPhotoForm">
                <form action="{{ route('orders.upload.photo', $order->id) }}" method="POST" enctype="multipart/form-data" id="editPhotoUploadForm">
                    @csrf
                    <div class="mb-3">
                        <label for="order_photo_edit" class="form-label">Replace Photo <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="file" 
                                   class="form-control" 
                                   id="order_photo_edit" 
                                   name="order_photo" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/heic,image/heif" 
                                   required>
                            <div class="form-text text-muted">
                                <small>Supports: JPEG, PNG, GIF, WebP, HEIC. Max size: 50MB</small>
                            </div>
                        </div>
                        
                        <!-- Image Preview for Edit -->
                        <div id="edit-image-preview" class="mt-3" style="display: none;">
                            <img id="edit-preview-img" src="" alt="Preview" class="img-fluid rounded shadow-sm" style="max-width: 200px; max-height: 200px;">
                            <div class="mt-2">
                                <small class="text-muted">New photo preview</small>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="ri-upload-cloud-2-line align-middle me-1"></i> Upload New Photo
                    </button>
                </form>
            </div>
            <!-- Modal for viewing order photo -->
            <div class="modal fade" id="viewOrderPhotoModal" tabindex="-1" aria-labelledby="viewOrderPhotoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewOrderPhotoModalLabel">Order Photo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="{{ asset('storage/order_photos/' . $order->order_photo) }}" alt="Order Photo" class="img-fluid rounded shadow">
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal for deleting order photo -->
            <div class="modal fade" id="deletePhotoModal" tabindex="-1" aria-labelledby="deletePhotoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-soft-danger">
                            <h5 class="modal-title" id="deletePhotoModalLabel">Delete Order Photo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('orders.delete.photo', $order->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <div class="modal-body">
                                <p>Are you sure you want to delete this photo? This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Delete Photo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <form action="{{ route('orders.upload.photo', $order->id) }}" method="POST" enctype="multipart/form-data" id="photoUploadForm2">
                @csrf
                <div class="mb-3">
                    <label for="order_photo_ready" class="form-label">Upload Photo <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="file" 
                               class="form-control" 
                               id="order_photo_ready" 
                               name="order_photo" 
                               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/heic,image/heif" 
                               required>
                        <div id="file-size-info-2" class="form-text text-muted">
                            <small>Supports: JPEG, PNG, GIF, WebP, HEIC. Max size: 50MB</small>
                        </div>
                    </div>
                    
                    <!-- Image Preview -->
                    <div id="image-preview-2" class="mt-3" style="display: none;">
                        <img id="preview-img-2" src="" alt="Preview" class="img-fluid rounded shadow-sm" style="max-width: 300px; max-height: 300px;">
                        <div class="mt-2">
                            <small class="text-muted">Preview - Image will be uploaded when you click submit</small>
                        </div>
                    </div>
                    
                    <!-- Upload Progress -->
                    <div id="upload-progress-2" class="mt-3" style="display: none;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">Uploading photo...</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" id="upload-btn-2">
                    <i class="ri-upload-cloud-2-line align-middle me-1"></i> Upload Photo
                </button>
            </form>
        @endif
    </div>
</div>
@endif

@if($order->status === 'delivered' || $order->status === 'cancel')
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ri-image-line align-middle me-1 text-muted"></i> Order Photo</h5>
    </div>
    <div class="card-body">
        @if($order->order_photo)
            <div class="mb-3">
                <img src="{{ asset('storage/order_photos/' . $order->order_photo) }}" alt="Order Photo" class="img-fluid rounded shadow-sm" style="max-width: 300px; cursor:pointer;" data-bs-toggle="modal" data-bs-target="#viewOrderPhotoModal">
            </div>
            
            @if(Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin')
            <div class="d-flex gap-2 mb-2">
                <!-- Edit button shows upload form -->
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#editPhotoFormDelivered">Edit</button>
                <!-- Delete button shows modal -->
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deletePhotoModalDelivered">Delete</button>
            </div>
            <div class="collapse" id="editPhotoFormDelivered">
                <form action="{{ route('orders.upload.photo', $order->id) }}" method="POST" enctype="multipart/form-data" id="editPhotoUploadFormDelivered">
                    @csrf
                    <div class="mb-3">
                        <label for="order_photo_edit_delivered" class="form-label">Replace Photo <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="file" 
                                   class="form-control" 
                                   id="order_photo_edit_delivered" 
                                   name="order_photo" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/heic,image/heif" 
                                   required>
                            <div class="form-text text-muted">
                                <small>Supports: JPEG, PNG, GIF, WebP, HEIC. Max size: 50MB</small>
                            </div>
                        </div>
                        
                        <!-- Image Preview for Edit -->
                        <div id="edit-image-preview-delivered" class="mt-3" style="display: none;">
                            <img id="edit-preview-img-delivered" src="" alt="Preview" class="img-fluid rounded shadow-sm" style="max-width: 200px; max-height: 200px;">
                            <div class="mt-2">
                                <small class="text-muted">New photo preview</small>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="ri-upload-cloud-2-line align-middle me-1"></i> Upload New Photo
                    </button>
                </form>
            </div>
            
            <!-- Modal for deleting order photo (delivered) -->
            <div class="modal fade" id="deletePhotoModalDelivered" tabindex="-1" aria-labelledby="deletePhotoModalDeliveredLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-soft-danger">
                            <h5 class="modal-title" id="deletePhotoModalDeliveredLabel">Delete Order Photo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('orders.delete.photo', $order->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <div class="modal-body">
                                <p>Are you sure you want to delete this photo? This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Delete Photo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        @else
            @if(Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin')
            <form action="{{ route('orders.upload.photo', $order->id) }}" method="POST" enctype="multipart/form-data" id="photoUploadFormDelivered">
                @csrf
                <div class="mb-3">
                    <label for="order_photo_delivered" class="form-label">Upload Photo <span class="text-danger">*</span></label>
                    <div class="position-relative">
                        <input type="file" 
                               class="form-control" 
                               id="order_photo_delivered" 
                               name="order_photo" 
                               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/heic,image/heif" 
                               required>
                        <div id="file-size-info-delivered" class="form-text text-muted">
                            <small>Supports: JPEG, PNG, GIF, WebP, HEIC. Max size: 50MB</small>
                        </div>
                    </div>
                    
                    <!-- Image Preview -->
                    <div id="image-preview-delivered" class="mt-3" style="display: none;">
                        <img id="preview-img-delivered" src="" alt="Preview" class="img-fluid rounded shadow-sm" style="max-width: 300px; max-height: 300px;">
                        <div class="mt-2">
                            <small class="text-muted">Preview - Image will be uploaded when you click submit</small>
                        </div>
                    </div>
                    
                    <!-- Upload Progress -->
                    <div id="upload-progress-delivered" class="mt-3" style="display: none;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">Uploading photo...</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" id="upload-btn-delivered">
                    <i class="ri-upload-cloud-2-line align-middle me-1"></i> Upload Photo
                </button>
            </form>
            @else
            <div class="alert alert-info">
                <i class="ri-information-line me-2"></i> No photo available for this order.
            </div>
            @endif
        @endif
        
        @if($order->order_photo)
        <!-- Modal for viewing order photo -->
        <div class="modal fade" id="viewOrderPhotoModal" tabindex="-1" aria-labelledby="viewOrderPhotoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewOrderPhotoModalLabel">Order Photo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="{{ asset('storage/order_photos/' . $order->order_photo) }}" alt="Order Photo" class="img-fluid rounded shadow">
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endif

<div class="row">
    <div class="col-xl-9 col-lg-8 col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center flex-wrap">
                    <h5 class="card-title flex-grow-1 mb-0">Order #{{ $order->id }}</h5>
                    <div class="flex-shrink-0 mt-2 mt-md-0">
                        <a href="{{ route('orders.prf', $order->id) }}" class="btn btn-primary btn-sm me-2 mb-2">
                            <i class="ri-file-list-3-line align-middle me-1"></i> PRF
                        </a>
                        <button type="button" class="btn btn-info btn-sm me-2 mb-2" data-bs-toggle="modal" data-bs-target="#updateDeliveryDateTimeModal">
                            <i class="ri-calendar-2-line align-middle me-1"></i> Update Schedule
                        </button>
                        <a href="{{ route('orders.batch.edit', $order->id) }}" class="btn btn-success btn-sm mb-2">
                            <i class="ri-settings-3-line align-middle me-1"></i> Manage Batches
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

                <div class="table-responsive table-card">
                    <table class="table table-nowrap align-middle table-borderless mb-0 table-mobile">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col" class="d-none d-md-table-cell">Quantity</th>
                                <th scope="col">Batch</th>
                                <th scope="col" class="d-none d-lg-table-cell">Patient</th>
                                <th scope="col" class="d-none d-lg-table-cell">Remarks</th>
                                <th scope="col" class="d-none d-md-table-cell">QC Doc</th>
                                <th scope="col" class="d-none d-lg-table-cell">Prepared By</th>
                                @if($order->status === 'preparing' && (Auth::user()->department === 'Quality' || Auth::user()->department === 'Cell Lab' || Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin'))
                                <th scope="col">Status</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->products as $product)
                            <tr>
                                <td>
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <h5 class="fs-14 mb-1"><a href="#" class="link-primary">{{ $product->name }}</a></h5>
                                            <div class="d-md-none">
                                                <small class="text-muted">
                                                    Quantity: {{ $product->pivot->quantity }}
                                                    @if($product->pivot->patient_name)
                                                    | Patient: {{ $product->pivot->patient_name }}
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $product->pivot->quantity }}</td>
                                <td>
                                    @if($product->pivot->batch_number)
                                        <span class="badge bg-primary">{{ $product->pivot->batch_number }}</span>
                                    @else
                                        <span class="badge bg-danger">Not Set</span>
                                    @endif
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    @if($product->pivot->patient_name)
                                        <span class="fw-medium">{{ $product->pivot->patient_name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    @if($product->pivot->remarks)
                                        <span class="fw-medium">{{ $product->pivot->remarks }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="d-none d-md-table-cell">
                                    @if($product->pivot->qc_document_number)
                                        <span class="fw-medium">{{ $product->pivot->qc_document_number }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    @if($product->pivot->prepared_by)
                                        <span class="fw-medium">{{ $product->pivot->prepared_by }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                @if($order->status === 'preparing' && (Auth::user()->department === 'Quality' || Auth::user()->department === 'Cell Lab' || Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin'))
                                <td>
                                    @if($product->pivot->status === 'ready')
                                    <button type="button" class="btn btn-sm btn-soft-success" data-bs-toggle="modal" data-bs-target="#markProductNotReadyModal{{ $product->pivot->id }}">
                                        <i class="ri-check-line align-middle d-md-none"></i>
                                        <span class="d-none d-md-inline">Ready</span>
                                    </button>
                                    @else
                                    <button type="button" class="btn btn-sm btn-soft-danger" data-bs-toggle="modal" data-bs-target="#markProductReadyModal{{ $product->pivot->id }}">
                                        <i class="ri-time-line align-middle d-md-none"></i>
                                        <span class="d-none d-md-inline">Not Ready</span>
                                    </button>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($order->status === 'preparing' && (Auth::user()->department === 'Quality' || Auth::user()->department === 'Cell Lab' || Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin'))
                <div class="text-center mt-4">
                    @php
                        // Check if all products are ready
                        $allProductsReady = true;
                        foreach($order->products as $product) {
                            if($product->pivot->status !== 'ready') {
                                $allProductsReady = false;
                                break;
                            }
                        }
                    @endphp
                    
                    @if($allProductsReady && count($order->products) > 0 && (Auth::user()->department === 'Quality' || Auth::user()->department === 'Cell Lab' || Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin'))
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#markOrderReadyModal">
                        <i class="ri-check-double-line align-bottom me-1"></i> Mark Order as Ready
                    </button>
                    @elseif(!$allProductsReady || count($order->products) == 0)
                    <div class="alert alert-info">
                        <i class="ri-information-line me-2"></i> Mark all products as ready to proceed with order status change.
                    </div>
                    @endif
                </div>
                @endif
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
    <div class="col-xl-3 col-lg-4 col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap">
                    <h5 class="card-title flex-grow-1 mb-0">Customer Details</h5>
                    <div class="flex-shrink-0 mt-2 mt-md-0">
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
        <!--end card-->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-map-pin-user-line align-middle me-1 text-muted"></i> Delivery Address</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled vstack gap-2 mb-0">
                    <li class="fw-medium fs-15">{{ $order->delivery_address ?? 'N/A' }}</li>
                </ul>
            </div>
        </div>
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
<div class="modal fade" id="markOrderReadyModal" tabindex="-1" aria-labelledby="markOrderReadyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-soft-primary">
                <h5 class="modal-title" id="markOrderReadyModalLabel">Mark Order as Ready</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('orders.mark.ready', $order->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="ri-information-line me-2"></i> Marking as "Ready" means the order has been prepared and is ready for delivery or pickup.
                    </div>
                    <p>All products are ready. Do you want to change the order status to "Ready"?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Yes, Mark as Ready</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Product Ready Modal -->
@foreach($order->products as $product)
<div class="modal fade" id="markProductReadyModal{{ $product->pivot->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-soft-success">
                <h5 class="modal-title">Mark Product as Ready</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('orders.product.ready', ['order' => $order->id, 'product' => $product->id]) }}" method="POST" class="product-ready-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="pivot_id" value="{{ $product->pivot->id }}">
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar-md mx-auto">
                            <div class="avatar-title bg-light text-success display-6 rounded-circle">
                                <i class="ri-checkbox-circle-line"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-center">Are you sure you want to mark <strong>{{ $product->name }}</strong> as ready?</p>
                    <input type="hidden" name="status" value="ready">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Yes, Mark as Ready</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="markProductNotReadyModal{{ $product->pivot->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-soft-danger">
                <h5 class="modal-title">Mark Product as Not Ready</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('orders.product.ready', ['order' => $order->id, 'product' => $product->id]) }}" method="POST" class="product-ready-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="pivot_id" value="{{ $product->pivot->id }}">
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar-md mx-auto">
                            <div class="avatar-title bg-light text-danger display-6 rounded-circle">
                                <i class="ri-close-circle-line"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-center">Are you sure you want to mark <strong>{{ $product->name }}</strong> as not ready?</p>
                    <input type="hidden" name="status" value="not_ready">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Mark as Not Ready</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

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
                        <label class="form-label d-block">Delivery Type <span class="text-danger">*</span></label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="delivery_type" id="delivery_type_delivery" 
                                value="delivery" {{ $order->delivery_type === 'delivery' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="delivery_type_delivery">
                                <i class="ri-truck-line me-1"></i>Delivery
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="delivery_type" id="delivery_type_self" 
                                value="self_collect" {{ $order->delivery_type === 'self_collect' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="delivery_type_self">
                                <i class="ri-user-location-line me-1"></i>Self Collect
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="delivery_datetime" class="form-label">Delivery Date & Time</label>
                        <input type="text" class="form-control flatpickr-input" id="delivery_datetime" name="delivery_datetime" 
                               data-provider="flatpickr" data-date-format="d.m.Y h:i K" data-enable-time="true" required>
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

<!-- Modal to Cancel Order -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-soft-secondary">
                <h5 class="modal-title" id="cancelOrderModalLabel">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('orders.update.status', $order->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        <i class="ri-information-line me-2"></i> Are you sure you want to cancel this order? This action cannot be undone.
                    </div>
                    <input type="hidden" name="status" value="cancel">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No, Keep Order</button>
                    <button type="submit" class="btn btn-secondary">Yes, Cancel Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Updating Delivery Date/Time -->
<div class="modal fade" id="updateDeliveryDateTimeModal" tabindex="-1" aria-labelledby="updateDeliveryDateTimeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-soft-info">
                <h5 class="modal-title" id="updateDeliveryDateTimeModalLabel">Update Delivery Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('orders.delivery.datetime.update', $order->id) }}" method="POST" id="deliveryDateTimeForm">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="ri-information-line me-2"></i> Update the delivery date, reach client time, and ready time for this order.
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label for="pickup_delivery_date" class="form-label">{{ $order->delivery_type === 'delivery' ? 'Delivery' : 'Self Collect' }} Date <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('pickup_delivery_date') is-invalid @enderror" 
                                    id="pickup_delivery_date" name="pickup_delivery_date" 
                                    data-provider="flatpickr" data-date-format="Y-m-d" 
                                    data-mindate="today"
                                    value="{{ $order->pickup_delivery_date ? $order->pickup_delivery_date->format('Y-m-d') : '' }}" required>
                                @error('pickup_delivery_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">Please select a date</div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="item_ready_time_display" class="form-label">Ready Time <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('item_ready_time') is-invalid @enderror" 
                                    id="item_ready_time_display" 
                                    data-provider="timepickr" 
                                    placeholder="Select ready time"
                                    value="{{ $order->item_ready_at ? \Carbon\Carbon::parse($order->item_ready_at)->format('h:i A') : '' }}" required>
                                <input type="hidden" name="item_ready_time" id="item_ready_time" 
                                    value="{{ $order->item_ready_at ? \Carbon\Carbon::parse($order->item_ready_at)->format('H:i') : '' }}">
                                @error('item_ready_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">Please select a ready time</div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="pickup_delivery_time_display" class="form-label">{{ $order->delivery_type === 'delivery' ? 'Reach Client' : 'Self Collect' }} Time <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('pickup_delivery_time') is-invalid @enderror" 
                                    id="pickup_delivery_time_display" 
                                    data-provider="timepickr" 
                                    placeholder="Select time"
                                    value="{{ \Carbon\Carbon::parse($order->pickup_delivery_time)->format('h:i A') }}" required>
                                <input type="hidden" name="pickup_delivery_time" id="pickup_delivery_time" 
                                    value="{{ \Carbon\Carbon::parse($order->pickup_delivery_time)->format('H:i') }}">
                                @error('pickup_delivery_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">Please select a time</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line align-bottom me-1"></i> Update Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize date and time pickers
        if (typeof flatpickr !== 'undefined') {
                                        flatpickr("#pickup_delivery_date", {
                dateFormat: "Y-m-d",
                minDate: "today",
                allowInput: true,
                enableTime: false,
                time_24hr: false
            });
            
            // Initialize time picker with AM/PM format for pickup/delivery time
            const timePicker = flatpickr("#pickup_delivery_time_display", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i K",
                time_24hr: false,
                minuteIncrement: 15,
                allowInput: true,
                onChange: function(selectedDates, dateStr, instance) {
                    // Convert 12-hour format to 24-hour format for the hidden input
                    const date = selectedDates[0];
                    if (date) {
                        const hours = date.getHours().toString().padStart(2, '0');
                        const minutes = date.getMinutes().toString().padStart(2, '0');
                        document.getElementById('pickup_delivery_time').value = `${hours}:${minutes}`;
                    }
                }
            });
            
            // Initialize time picker with AM/PM format for ready time
            const readyTimePicker = flatpickr("#item_ready_time_display", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i K",
                time_24hr: false,
                minuteIncrement: 15,
                allowInput: true,
                onChange: function(selectedDates, dateStr, instance) {
                    // Convert 12-hour format to 24-hour format for the hidden input
                    const date = selectedDates[0];
                    if (date) {
                        const hours = date.getHours().toString().padStart(2, '0');
                        const minutes = date.getMinutes().toString().padStart(2, '0');
                        document.getElementById('item_ready_time').value = `${hours}:${minutes}`;
                    }
                }
            });
            
            // Initialize delivery_datetime picker for Mark as Delivered modal
            flatpickr("#delivery_datetime", {
                enableTime: true,
                dateFormat: "d.m.Y h:i K",
                time_24hr: false,
                minuteIncrement: 15,
                allowInput: true
            });
        }

        // Form validation
        document.getElementById('deliveryDateTimeForm').addEventListener('submit', function(e) {
            const timeDisplay = document.getElementById('pickup_delivery_time_display');
            const timeHidden = document.getElementById('pickup_delivery_time');
            const readyTimeDisplay = document.getElementById('item_ready_time_display');
            const readyTimeHidden = document.getElementById('item_ready_time');
            
            let isValid = true;
            
            if (!timeDisplay.value) {
                timeDisplay.classList.add('is-invalid');
                isValid = false;
            } else {
                timeDisplay.classList.remove('is-invalid');
            }
            
            if (!readyTimeDisplay.value) {
                readyTimeDisplay.classList.add('is-invalid');
                isValid = false;
            } else {
                readyTimeDisplay.classList.remove('is-invalid');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });

        // Handle product ready form submissions
        document.querySelectorAll('.product-ready-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<i class="ri-loader-2-line spinning"></i> Processing...';
                }
            });
        });

        // Handle mark order ready form submission
        const markOrderReadyForm = document.querySelector('#markOrderReadyModal form');
        if (markOrderReadyForm) {
            markOrderReadyForm.addEventListener('submit', function(e) {
                const button = markOrderReadyForm.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<i class="ri-loader-2-line spinning"></i> Processing...';
                }
            });
        }

        // Enhanced Photo Upload Handling for Mobile
        function setupPhotoUpload(fileInputId, previewId, progressId, formId) {
            const fileInput = document.getElementById(fileInputId);
            const preview = document.getElementById(previewId);
            const previewImg = document.getElementById(previewId.replace('preview', 'preview-img'));
            const progress = document.getElementById(progressId);
            const form = document.getElementById(formId);

            if (!fileInput) return;

            // File selection handler
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) {
                    if (preview) preview.style.display = 'none';
                    return;
                }

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, PNG, GIF, WebP, or HEIC).');
                    fileInput.value = '';
                    if (preview) preview.style.display = 'none';
                    return;
                }

                // Validate file size (50MB = 52428800 bytes)
                const maxSize = 52428800; // 50MB
                if (file.size > maxSize) {
                    alert('File size must be less than 50MB. Please choose a smaller image or compress it.');
                    fileInput.value = '';
                    if (preview) preview.style.display = 'none';
                    return;
                }

                // Show file size info
                const sizeInfo = document.querySelector(`#${fileInputId} ~ .form-text small`);
                if (sizeInfo) {
                    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                    sizeInfo.innerHTML = `Selected: ${file.name} (${sizeMB} MB) - Ready to upload`;
                    sizeInfo.style.color = '#28a745';
                }

                // Create image preview
                if (preview && previewImg) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Form submission handler
            if (form) {
                form.addEventListener('submit', function(e) {
                    const file = fileInput.files[0];
                    if (!file) {
                        alert('Please select an image to upload.');
                        e.preventDefault();
                        return;
                    }

                    // Show progress and disable button
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="ri-loader-2-line spinning"></i> Uploading...';
                    }

                    if (progress) {
                        progress.style.display = 'block';
                        const progressBar = progress.querySelector('.progress-bar');
                        if (progressBar) {
                            progressBar.style.width = '20%';
                        }
                    }

                    // Simulate progress (since we can't track actual upload progress with standard form submission)
                    let progressValue = 20;
                    const progressInterval = setInterval(() => {
                        progressValue += Math.random() * 30;
                        if (progressValue >= 90) {
                            progressValue = 90;
                            clearInterval(progressInterval);
                        }
                        if (progress) {
                            const progressBar = progress.querySelector('.progress-bar');
                            if (progressBar) {
                                progressBar.style.width = progressValue + '%';
                            }
                        }
                    }, 500);

                    // Clean up on form submission complete
                    setTimeout(() => {
                        clearInterval(progressInterval);
                    }, 10000);
                });
            }
        }

        // Setup all photo upload forms
        setupPhotoUpload('order_photo', 'image-preview', 'upload-progress', 'photoUploadForm');
        setupPhotoUpload('order_photo_ready', 'image-preview-2', 'upload-progress-2', 'photoUploadForm2');
        setupPhotoUpload('order_photo_edit', 'edit-image-preview', null, 'editPhotoUploadForm');
        setupPhotoUpload('order_photo_delivered', 'image-preview-delivered', 'upload-progress-delivered', 'photoUploadFormDelivered');
        setupPhotoUpload('order_photo_edit_delivered', 'edit-image-preview-delivered', null, 'editPhotoUploadFormDelivered');

        // Mobile-specific optimizations
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            // Add mobile-specific classes for better styling
            document.querySelectorAll('input[type="file"]').forEach(input => {
                input.classList.add('mobile-file-input');
            });

            // Improve mobile file input experience
            document.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('click', function() {
                    // Add visual feedback for mobile
                    this.style.borderColor = '#007bff';
                });
            });
        }
    });
</script>
@endsection
