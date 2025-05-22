@extends('layouts.master')

@section('content')
<style>
    .new-product {
        background-color: #f0fff4 !important;
        transition: background-color 0.3s;
    }
    .product-row:hover {
        background-color: #f5f5f5;
    }
    .product-select {
        min-width: 180px;
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
                            <p class="mb-0">
                                @if(Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin')
                                    As an administrator, you can also modify products and quantities.
                                @else
                                    Please assign batch details for each product in this order.
                                @endif
                            </p>
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
                                    <th scope="col">Ordered Quantity</th>
                                    <th scope="col">Batch Number</th>
                                    <th scope="col">Patient Name</th>
                                    <th scope="col">Remarks</th>
                                    <th scope="col">QC Document Number</th>
                                    <th scope="col">Prepared By</th>
                                    @if(Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin')
                                    <th scope="col">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody id="product-rows">
                                @php
                                    $canEditBatch = Auth::user()->department === 'Cell Lab' || Auth::user()->role === 'superadmin';
                                    $canEditQc = Auth::user()->department === 'Quality' || Auth::user()->role === 'superadmin';
                                    $canEditAll = Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin';
                                @endphp

                                @foreach($order->products as $index => $product)
                                <tr class="product-row">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($canEditAll)
                                                <select class="form-select product-select" name="products[{{ $index }}][product_id]">
                                                    @foreach($products as $productOption)
                                                        <option value="{{ $productOption->id }}" {{ $product->id == $productOption->id ? 'selected' : '' }}>
                                                            {{ $productOption->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <div class="flex-grow-1">
                                                    <h5 class="fs-15 mb-1">{{ $product->name }}</h5>
                                                    <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $product->id }}">
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($canEditAll)
                                            <input type="number" class="form-control quantity-input" name="products[{{ $index }}][quantity]" value="{{ $product->pivot->quantity }}" min="1">
                                        @else
                                            <span class="fw-medium">{{ $product->pivot->quantity }}</span>
                                            <input type="hidden" name="products[{{ $index }}][quantity]" value="{{ $product->pivot->quantity }}">
                                        @endif
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
                                                    {{ !$canEditBatch ? 'disabled' : '' }}>
                                            @endif
                                        </div>
                                        @error('products.' . $index . '.batch_number')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text" class="form-control patient-name" id="patient_name_{{ $index }}" 
                                            name="products[{{ $index }}][patient_name]" 
                                            value="{{ $product->pivot->patient_name ?? '' }}"
                                            placeholder="Enter patient name">
                                        @error('products.' . $index . '.patient_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <textarea class="form-control remarks" id="remarks_{{ $index }}" 
                                            name="products[{{ $index }}][remarks]" 
                                            placeholder="Enter remarks">{{ $product->pivot->remarks ?? '' }}</textarea>
                                        @error('products.' . $index . '.remarks')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            @if(!$canEditQc && $product->pivot->qc_document_number)
                                                <input type="text" class="form-control qc-document" disabled value="{{ $product->pivot->qc_document_number }}">
                                                <input type="hidden" name="products[{{ $index }}][qc_document_number]" value="{{ $product->pivot->qc_document_number }}">
                                            @else
                                                <input type="text" class="form-control qc-document" id="qc_document_{{ $index }}" 
                                                    name="products[{{ $index }}][qc_document_number]" 
                                                    value="{{ $product->pivot->qc_document_number ?? '' }}"
                                                    {{ !$canEditQc ? 'disabled' : '' }}
                                                    placeholder="Enter QC doc number">
                                            @endif
                                        </div>
                                        @error('products.' . $index . '.qc_document_number')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text" class="form-control prepared-by" id="prepared_by_{{ $index }}" 
                                            name="products[{{ $index }}][prepared_by]" 
                                            value="{{ $product->pivot->prepared_by ?? '' }}"
                                            placeholder="Enter preparer's name">
                                        @error('products.' . $index . '.prepared_by')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    @if($canEditAll)
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger remove-product"><i class="ri-delete-bin-line"></i></button>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                            @if($canEditAll)
                            <tfoot>
                                <tr>
                                    <td colspan="8">
                                        <!-- Add Product button removed -->
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
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

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin')
        
        // Remove product row - set up event listeners for existing buttons
        document.querySelectorAll('.remove-product').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                row.remove();
                // Reindex the remaining rows
                reindexRows();
            });
        });
        
        // Reindex form field names after removing a row
        function reindexRows() {
            const rows = document.querySelectorAll('.product-row');
            rows.forEach((row, index) => {
                // Update all the field names with the new index
                row.querySelectorAll('[name^="products["]').forEach(field => {
                    const name = field.getAttribute('name');
                    const newName = name.replace(/products\[\d+\]/, `products[${index}]`);
                    field.setAttribute('name', newName);
                });
                
                // Update IDs if they contain indices
                row.querySelectorAll('[id]').forEach(field => {
                    const id = field.getAttribute('id');
                    if (id && (id.includes('_number_') || id.includes('patient_name_') || id.includes('remarks_') || id.includes('qc_document_') || id.includes('prepared_by_'))) {
                        const newId = id.replace(/_\d+$/, `_${index}`);
                        field.setAttribute('id', newId);
                    }
                });
            });
        }

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            const rows = document.querySelectorAll('.product-row');
            let hasErrors = false;
            let errorMessage = '';
            
            rows.forEach((row, index) => {
                const quantityInput = row.querySelector('.quantity-input');
                if (quantityInput && (quantityInput.value === '' || parseInt(quantityInput.value) < 1)) {
                    hasErrors = true;
                    errorMessage = 'Please enter a valid quantity (minimum 1) for all products.';
                    quantityInput.classList.add('is-invalid');
                }
            });
            
            if (hasErrors) {
                event.preventDefault();
                alert(errorMessage);
            }
        });
        @endif
    });
</script>
@endsection 