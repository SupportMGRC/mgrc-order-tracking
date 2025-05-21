@extends('layouts.master')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Product Request Form</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('orderhistory') }}">Order</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('orderdetails', $order->id) }}">Order Details</a></li>
                    <li class="breadcrumb-item active">Print PRF</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <div class="prf-container">
                    <div class="text-end mb-4">
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="ri-printer-line align-bottom me-1"></i> Print
                        </button>
                    </div>
                    
                    <div class="prf-document" id="printSection">
                        <!-- PRF Header -->
                        <div class="prf-header">
                            <table class="w-100">
                                <tr>
                                    <td width="15%">
                                        <img src="{{ asset('assets/images/mgrc/MGRC-logo-only.png') }}" alt="MGRC Logo" class="img-fluid" style="max-height: 55px;">
                                    </td>
                                    <td width="55%" class="text-center">
                                        <h5 class="m-0 fw-bold">Malaysian Genomics Research<br>Centre</h5>
                                    </td>
                                    <td width="30%" class="align-top">
                                        <div class="text-center border border-dark p-1 mb-1">
                                            <span class="small-text fw-bold">CONTROLLED COPY</span>
                                        </div>
                                        <div class="d-flex small-text mb-1">
                                            <span class="me-1">No:</span>
                                            <span class="flex-grow-1 border-bottom border-dark">{{ $order->id ?? 'N/A' }}</span>
                                        </div>
                                        <div class="d-flex small-text">
                                            <span class="me-1">Date:</span>
                                            <span class="flex-grow-1 border-bottom border-dark">{{ $order->order_date ? $order->order_date->format('d/m/Y') : 'N/A' }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <div class="text-center border border-dark mt-2 mb-4">
                                <h6 class="my-1 fw-bold">PRODUCT REQUEST FORM</h6>
                            </div>
                        </div>
                        
                        <!-- Customer Details Section -->
                        <div class="prf-section mb-4">
                            <div class="fw-bold small-text">A: Customer Details</div>
                            
                            <table class="w-100 mb-3">
                                <tr>
                                    <td width="20%" class="small-text">Customer Name</td>
                                    <td width="80%" colspan="3" class="small-text border-bottom border-dark">{{ $order->customer->name ?? 'N/A' }}</td>
                                </tr>
                            </table>
                            
                            <table class="w-100 mb-3">
                                <tr>
                                    <td width="20%" class="small-text">Email</td>
                                    <td width="30%" class="small-text border-bottom border-dark">{{ $order->customer->email ?? 'N/A' }}</td>
                                    <td width="20%" class="small-text ps-2">Phone Number</td>
                                    <td width="30%" class="small-text border-bottom border-dark">{{ $order->customer->phone ?? $order->customer->phoneNo ?? 'N/A' }}</td>
                                </tr>
                            </table>
                            
                            <table class="w-100 mb-3">
                                <tr>
                                    <td width="20%" class="small-text">Address</td>
                                    <td width="80%" class="small-text border-bottom border-dark">{{ $order->customer->address ?? 'N/A' }}</td>
                                </tr>
                            </table>
                            
                            <table class="w-100 mb-3">
                                <tr>
                                    <td width="20%" class="small-text">Order Date</td>
                                    <td width="30%" class="small-text border-bottom border-dark">{{ $order->order_date ? $order->order_date->format('d/m/Y') : 'N/A' }}</td>
                                    <td width="10%" class="small-text text-center">Time</td>
                                    <td width="40%" class="small-text border-bottom border-dark">{{ $order->order_time ? $order->order_time->format('H:i') : 'N/A' }}</td>
                                </tr>
                            </table>
                            
                            <table class="w-100 mb-3">
                                <tr>
                                    <td width="20%" class="small-text">Delivery Date</td>
                                    <td width="30%" class="small-text border-bottom border-dark">{{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : 'N/A' }}</td>
                                    <td width="10%" class="small-text text-center">Time</td>
                                    <td width="40%" class="small-text border-bottom border-dark">{{ $order->delivery_time ? date('H:i', strtotime($order->delivery_time)) : 'N/A' }}</td>
                                </tr>
                            </table>
                            
                            <table class="w-100 mb-4">
                                <tr>
                                    <td width="20%" class="small-text">Notes</td>
                                    <td width="80%" class="small-text border-bottom border-dark">{{ $order->remarks ?? 'N/A' }}</td>
                                </tr>
                            </table>
                            
                            <!-- Product List Table -->
                            <div class="mb-2">
                                <div class="small-text fw-bold mb-2">Products Requested:</div>
                                <table class="w-100 table-bordered mb-3">
                                    <thead>
                                        <tr>
                                            <th width="10%" class="small-text py-1">No</th>
                                            <th width="65%" class="small-text py-1">Product Name</th>
                                            <th width="25%" class="small-text py-1">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->products as $index => $product)
                                        <tr>
                                            <td class="text-center small-text py-1">{{ $index + 1 }}</td>
                                            <td class="small-text py-1">{{ $product->name }}</td>
                                            <td class="text-center small-text py-1">{{ $product->pivot->quantity }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <table class="w-100">
                                <tr>
                                    <td width="20%" class="small-text">Requested by</td>
                                    <td width="30%" class="small-text border-bottom border-dark">{{ $order->order_placed_by ?? $order->user->name ?? 'N/A' }}</td>
                                    <td width="50%"></td>
                                </tr>
                                <tr>
                                    <td width="20%" class="small-text">Date</td>
                                    <td width="30%" class="small-text border-bottom border-dark">{{ $order->order_date ? $order->order_date->format('d/m/Y') : 'N/A' }}</td>
                                    <td width="50%"></td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Deliverables Section -->
                        <div class="prf-section mb-4">
                            <div class="fw-bold small-text mb-2">B: Deliverables Details (For Cell Lab usage only)</div>
                            
                            <!-- Batch Information Table -->
                            <table class="w-100 table-bordered mb-3">
                                <thead>
                                    <tr>
                                        <th width="8%" class="small-text py-1">No</th>
                                        <th width="22%" class="small-text py-1">Product Name</th>
                                        <th width="15%" class="small-text py-1">Batch Number</th>
                                        <th width="15%" class="small-text py-1">Patient Name</th>
                                        <th width="15%" class="small-text py-1">Remarks</th>
                                        <th width="15%" class="small-text py-1">QC Document No</th>
                                        <th width="10%" class="small-text py-1">Prepared By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->products as $index => $product)
                                    <tr>
                                        <td class="text-center small-text py-1">{{ $index + 1 }}</td>
                                        <td class="small-text py-1">{{ $product->name }}</td>
                                        <td class="text-center small-text py-1">{{ $product->pivot->batch_number ?? '-' }}</td>
                                        <td class="text-center small-text py-1">{{ $product->pivot->patient_name ?? '-' }}</td>
                                        <td class="text-center small-text py-1">{{ $product->pivot->remarks ?? '-' }}</td>
                                        <td class="text-center small-text py-1">{{ $product->pivot->qc_document_number ?? '-' }}</td>
                                        <td class="text-center small-text py-1">{{ $product->pivot->prepared_by ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Product Collection Section -->
                        <div class="prf-section">
                            <div class="fw-bold small-text mb-4">C: Product Collection at Lab</div>
                            
                            <table class="w-100 mb-3">
                                <tr>
                                    <td width="20%" class="small-text">Collected by</td>
                                    <td width="80%" class="small-text border-bottom border-dark"></td>
                                </tr>
                            </table>
                            
                            <table class="w-100 mb-3">
                                <tr>
                                    <td width="20%" class="small-text">Sign</td>
                                    <td width="80%" class="small-text border-bottom border-dark" style="height: 25px;"></td>
                                </tr>
                            </table>
                            
                            <table class="w-100 mb-5">
                                <tr>
                                    <td width="20%" class="small-text">Date</td>
                                    <td width="30%" class="small-text border-bottom border-dark"></td>
                                    <td width="10%" class="small-text text-center">Time</td>
                                    <td width="40%" class="small-text border-bottom border-dark"></td>
                                </tr>
                            </table>
                            
                            <table class="w-100 doc-footer mt-5">
                                <tr class="border-top border-dark">
                                    <td width="25%" class="xsmall-text py-1">Document No.</td>
                                    <td width="25%" class="xsmall-text py-1">LSDHCL/SRL/F03</td>
                                    <td width="25%" class="xsmall-text py-1">Revision: 0</td>
                                    <td width="25%" class="xsmall-text py-1">Date: 16/08/2023</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        @page {
            size: A4 portrait;
            margin: 1cm;
        }
        
        body * {
            visibility: hidden;
        }
        
        #printSection, #printSection * {
            visibility: visible;
        }
        
        #printSection {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .card-body {
            padding: 0 !important;
        }
        
        .btn {
            display: none;
        }
        
        .prf-document {
            padding: 0 !important;
            border: none !important;
        }
    }
    
    .prf-document {
        font-family: Arial, sans-serif;
        padding: 1.5em;
        background-color: #fff;
        height: auto;
        width: 21cm;
        max-width: 21cm;
        margin: 0 auto;
        box-sizing: border-box;
    }
    
    /* Content styles */
    .prf-header {
        margin-bottom: 8px;
    }
    
    .prf-section {
        margin-bottom: 10px;
    }
    
    .small-text {
        font-size: 0.8rem;
        line-height: 1.4;
    }
    
    .xsmall-text {
        font-size: 0.75rem;
        line-height: 1.2;
    }
    
    /* Table styles */
    .w-100 {
        width: 100%;
    }
    
    .table-bordered {
        border-collapse: collapse;
    }
    
    .table-bordered,
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #000;
    }
    
    th, td {
        padding: 2px 4px;
    }
    
    .border-bottom {
        border-bottom-width: 1px !important;
        border-bottom-style: solid !important;
    }
    
    .border-dark {
        border-color: #000 !important;
    }
    
    .py-1 {
        padding-top: 4px;
        padding-bottom: 4px;
    }
    
    .mb-1 {
        margin-bottom: 0.25rem !important;
    }
    
    .mb-2 {
        margin-bottom: 0.5rem !important;
    }
    
    .mb-3 {
        margin-bottom: 0.75rem !important;
    }
    
    .mt-2 {
        margin-top: 0.5rem !important;
    }
    
    .ps-2 {
        padding-left: 0.5rem !important;
    }
    
    .doc-footer {
        font-size: 0.7rem;
        color: #333;
    }
</style>

@endsection