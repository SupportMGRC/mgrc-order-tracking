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
    <div class="col-lg-7 col-md-10 col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="prf-container">
                    <div class="text-end mb-4">
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="ri-printer-line align-bottom me-1"></i> Print
                        </button>
                    </div>
                    
                    <div class="prf-document" id="printSection" style="position: relative;">
                        <!-- PRF Header -->
                        <div class="prf-header d-flex" style="height: 70px; border: 1px solid black; position: relative;">
                            <!-- Left Section (Logo + Text) -->
                            <div class="d-flex p-1" style="width: 155px; border-right: 1px solid black;">
                                <img src="{{ asset('assets/images/mgrc/logo_title_mgrc.png') }}" alt="MGRC Logo" class="img-fluid" style="height: 100%;">
                            </div>
                            <!-- Right Section (Title) -->
                            <div class="flex-grow-1 d-flex align-items-center justify-content-center pe-5">
                                <h5 class="fw-bold m-0">PRODUCT REQUEST FORM</h5>
                            </div>
                        </div>
                        <!-- Controlled Copy Stamp Overlay (always on top) -->
                        <img src="{{ asset('assets/images/mgrc/controlledcopy.png') }}" alt="Controlled Copy" class="controlled-copy-stamp">

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
                                    <td width="10%" class="small-text text-center">Phone <br>Number</td>
                                    <td width="40%" class="small-text border-bottom border-dark">{{ $order->customer->phone ?? $order->customer->phoneNo ?? 'N/A' }}</td>
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
                                    <td width="10%" class="small-text text-center">Order<br>Time</td>
                                    <td width="40%" class="small-text border-bottom border-dark">{{ $order->order_time ? $order->order_time->format('h:i A') : 'N/A' }}</td>
                                </tr>
                            </table>
                            
                            <table class="w-100 mb-3">
                                <tr>
                                    <td width="20%" class="small-text">Delivery Type</td>
                                    <td width="80%" class="small-text">
                                        <span class="me-4">
                                            <input type="checkbox" disabled {{ $order->delivery_type === 'delivery' ? 'checked' : '' }}>
                                            <span class="ms-1">Delivery</span>
                                        </span>
                                        <span>
                                            <input type="checkbox" disabled {{ $order->delivery_type === 'self_collect' ? 'checked' : '' }}>
                                            <span class="ms-1">Self Collect</span>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                            
                            <table class="w-100 mb-3">
                                <tr>
                                    <td width="20%" class="small-text">Delivery Address</td>
                                    <td width="80%" class="small-text border-bottom border-dark">{{ $order->delivery_address ?? 'N/A' }}</td>
                                </tr>
                            </table>
                            
                            <table class="w-100 mb-3">
                                <tr>
                                    <td width="20%" class="small-text">Delivery Date</td>
                                    <td width="30%" class="small-text border-bottom border-dark">{{ $order->pickup_delivery_date ? $order->pickup_delivery_date->format('d/m/Y') : 'N/A' }}</td>
                                    <td width="10%" class="small-text text-center">Delivery<br>Time</td>
                                    <td width="40%" class="small-text border-bottom border-dark">
                                        {{ $order->pickup_delivery_time ? $order->pickup_delivery_time->format('h:i A') : 'N/A' }}
                                    </td>
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
                                <div class="table-responsive">
                                    <table class="w-100 table-bordered mb-3">
                                        <thead>
                                            <tr>
                                                <th width="10%" class="small-text py-1 text-center">No</th>
                                                <th width="65%" class="small-text py-1">Product Name</th>
                                                <th width="25%" class="small-text py-1 text-center">Quantity</th>
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
                                            @if(count($order->products) == 0)
                                            <tr>
                                                <td colspan="3" class="text-center small-text py-1">N/A</td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <table class="w-100 mb-3">
                                <tr>
                                    <td width="20%" class="small-text">Requested by</td>
                                    <td width="30%" class="small-text border-bottom border-dark">
                                        {{ $order->order_placed_by ?? $order->user->name ?? 'N/A' }}
                                    </td>
                                    <td width="10%" class="small-text text-center">Ready<br>Time</td>
                                    <td width="40%" class="small-text border-bottom border-dark">
                                        {{ $order->item_ready_at ? $order->item_ready_at->format('h:i A') : 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td width="20%" class="small-text">Date</td>
                                    <td width="30%" class="small-text border-bottom border-dark">
                                        {{ $order->order_date ? $order->order_date->format('d/m/Y') : 'N/A' }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Deliverables Section -->
                        <div class="prf-section mb-4">
                            <div class="fw-bold small-text mb-2">B: Deliverables Details (For Cell Lab usage only)</div>
                            
                            <!-- Batch Information Table -->
                            <div class="table-responsive">
                                <table class="w-100 table-bordered mb-3">
                                    <thead>
                                        <tr>
                                            <th width="10%" class="small-text py-1 text-center">No</th>
                                            <th width="30%" class="small-text py-1">Product Name</th>
                                            <th width="20%" class="small-text py-1 text-center">Batch Number</th>
                                            <th width="20%" class="small-text py-1 text-center">QC Document No</th>
                                            {{-- <th width="20%" class="small-text py-1 text-center">Remarks</th> --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->products as $index => $product)
                                        <tr>
                                            <td class="text-center small-text py-1">{{ $index + 1 }}</td>
                                            <td class="small-text py-1">{{ $product->name }}</td>
                                            <td class="text-center small-text py-1">{{ $product->pivot->batch_number ?? 'N/A' }}</td>
                                            <td class="text-center small-text py-1">{{ $product->pivot->qc_document_number ?? 'N/A' }}</td>
                                            {{-- <td class="text-center small-text py-1">{{ $product->pivot->remarks ?? 'N/A' }}</td> --}}
                                        </tr>
                                        @endforeach
                                        @if(count($order->products) == 0)
                                        <tr>
                                            <td colspan="5" class="text-center small-text py-1">N/A</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
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
                            
                            <table class="w-100 mb-0">
                                <tr>
                                    <td width="20%" class="small-text">Date</td>
                                    <td width="30%" class="small-text border-bottom border-dark"></td>
                                    <td width="10%" class="small-text text-center">Time</td>
                                    <td width="40%" class="small-text border-bottom border-dark"></td>
                                </tr>
                            </table>
                            
                            <table class="w-100 doc-footer mt-4 mb-0">
                                <tr class="border-top border-dark">
                                    <td width="25%" class="xsmall-text pt-1 pb-0">Document No : LSD/CL/SRL/F03</td>
                                    {{-- <td width="25%" class="xsmall-text py-1">: LSD/CL/SRL/F03</td> --}}
                                </tr>
                                <tr>
                                    <td width="25%" class="xsmall-text py-0">Revision : 1</td>
                                    {{-- <td width="25%" class="xsmall-text py-1">: 0</td> --}}
                                </tr>
                                <tr>
                                    <td width="25%" class="xsmall-text py-0">Date : 03/06/2025</td>
                                    {{-- <td width="25%" class="xsmall-text py-1">: 16/06/2023</td> --}}
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
        
        .controlled-copy-stamp {
            opacity: 1 !important;
            display: block !important;
        }
        
        .prf-header img {
            filter: grayscale(100%) contrast(200%);
        }
    }
    
    .prf-document {
        font-family: Arial, sans-serif;
        padding: 1.5em;
        background-color: #fff;
        height: auto;
        width: 100%;
        max-width: 21cm;
        margin: 0 auto;
        box-sizing: border-box;
    }
    
    /* Content styles */
    .prf-header {
        margin-bottom: 8px;
    }
    
    /* .prf-section {
        margin-bottom: 10px;
    } */
    
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
        table-layout: fixed;
    }
    
    .table-bordered,
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #000;
        word-wrap: break-word;
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
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .prf-document {
            padding: 0.75em;
            width: 100%;
        }
        
        .small-text {
            font-size: 0.75rem;
        }
        
        .xsmall-text {
            font-size: 0.7rem;
        }
        
        h4 {
            font-size: 1.2rem;
        }
        
        h5 {
            font-size: 1rem;
        }
        
        h6 {
            font-size: 0.9rem;
        }
        
        .row.justify-content-center .col-lg-7 {
            padding-left: 5px;
            padding-right: 5px;
        }
        
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Adjust form layout for smaller screens */
        table tr td[width="20%"] {
            width: 25%;
        }
        
        table tr td[width="30%"] {
            width: 25%;
        }
        
        .prf-header .row .col-7 {
            padding-left: 0;
        }
    }
    
    @media (max-width: 576px) {
        .prf-document {
            padding: 0.5em;
        }
        
        .small-text {
            font-size: 0.7rem;
        }
        
        .xsmall-text {
            font-size: 0.65rem;
        }
        
        th, td {
            padding: 1px 2px;
        }
        
        h4 {
            font-size: 1.1rem;
        }
        
        h6 {
            font-size: 0.85rem;
        }
        
        .col-2 img {
            max-height: 45px !important;
        }
        
        /* Further adjust form layout for mobile screens */
        table tr td[width="20%"] {
            width: 30%;
        }
        
        .table-responsive-sm {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }
    
    .controlled-copy-stamp {
        position: absolute;
        top: -30px;
        right: -5px;
        width: 260px;
        opacity: 1;
        z-index: 9999;
        pointer-events: none;
    }
</style>

@endsection