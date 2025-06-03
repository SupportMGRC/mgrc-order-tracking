@extends('layouts.master')

@section('content')
<style>
    .product-row:hover {
        background-color: #f5f5f5;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Set Batch Information</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('orderhistory') }}">Orders</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('orderdetails', $order->id) }}">Order #{{ $order->id }}</a></li>
                    <li class="breadcrumb-item active">Batch Information</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <!-- Batch Information Card -->
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Order #{{ $order->id }} - Batch Information</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                            <i class="ri-information-line fs-24"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading">Assign Batch Information</h5>
                            <p class="mb-0">Please assign batch details for each product in this order.</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('orders.batch.update', $order->id) }}" method="POST">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Batch Number</th>
                                    <th scope="col">Patient Name</th>
                                    <th scope="col">Remarks</th>
                                    <th scope="col">QC Document Number</th>
                                    <th scope="col">Prepared By</th>
                                </tr>
                            </thead>
                            <tbody id="product-rows">
                                @php
                                    $canEditBatch = Auth::user()->department === 'Cell Lab' || Auth::user()->role === 'superadmin' || Auth::user()->department === 'Quality';
                                    $canEditQc = Auth::user()->department === 'Quality' || Auth::user()->role === 'superadmin';
                                @endphp

                                @foreach($order->products as $index => $product)
                                <tr class="product-row">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h5 class="fs-15 mb-1">{{ $product->name }}</h5>
                                                <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $product->id }}">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $product->pivot->quantity }}</span>
                                        <input type="hidden" name="products[{{ $index }}][quantity]" value="{{ $product->pivot->quantity }}">
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            @if(!$canEditBatch && $product->pivot->batch_number)
                                                <input type="text" class="form-control batch-number" disabled value="{{ $product->pivot->batch_number }}">
                                                <input type="hidden" name="products[{{ $index }}][batch_number]" value="{{ $product->pivot->batch_number }}">
                                            @else
                                                <input type="text" class="form-control batch-number" id="batch_number_{{ $index }}" 
                                                    name="products[{{ $index }}][batch_number]" 
                                                    value="{{ $product->pivot->batch_number ?? '' }}"
                                                    placeholder="Enter batch number"
                                                    {{ !$canEditBatch ? 'disabled' : '' }}>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control patient-name" id="patient_name_{{ $index }}" 
                                            name="products[{{ $index }}][patient_name]" 
                                            value="{{ $product->pivot->patient_name ?? '' }}"
                                            placeholder="Enter patient name">
                                    </td>
                                    <td>
                                        <textarea class="form-control remarks" id="remarks_{{ $index }}" 
                                            name="products[{{ $index }}][remarks]" 
                                            placeholder="Enter remarks">{{ $product->pivot->remarks ?? '' }}</textarea>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control qc-document" id="qc_document_{{ $index }}" 
                                            name="products[{{ $index }}][qc_document_number]" 
                                            value="{{ $product->pivot->qc_document_number ?? '' }}"
                                            {{ !$canEditQc ? 'disabled' : '' }}
                                            placeholder="Enter QC document number">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control prepared-by" id="prepared_by_{{ $index }}" 
                                            name="products[{{ $index }}][prepared_by]" 
                                            value="{{ $product->pivot->prepared_by ?? '' }}"
                                            placeholder="Enter preparer name">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('orderdetails', $order->id) }}" class="btn btn-light">
                            <i class="ri-close-line align-bottom me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success" id="save-batch-btn">
                            <i class="ri-save-line align-bottom me-1"></i> Save Batch Information
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection 