@extends('layouts.master')

@section('content')
<style>
    /* Minimal CSS - Data is drawn directly on canvas! */
    
    @media print {
        @page {
            size: A4;
            margin: 10mm;
        }
        
        body * {
            visibility: hidden;
        }
        
        .controls,
        .page-navigation,
        .no-print {
            display: none !important;
        }
        
        /* Hide the preview container when printing */
        #pdf-container,
        #pdf-canvas-wrapper,
        #pdf-canvas {
            display: none !important;
            visibility: hidden !important;
        }
        
        /* Show the print container with all pages */
        #print-all-pages,
        #print-all-pages * {
            visibility: visible !important;
            display: block !important;
        }
        
        #print-all-pages {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            background: white;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        #print-all-pages canvas {
            display: block !important;
            margin: auto !important;
            width: 100% !important;
            max-width: 210mm !important;
            max-height: 290mm !important;
            height: auto !important;
            page-break-after: always;
            page-break-inside: avoid;
        }
        
        #print-all-pages canvas:last-child {
            page-break-after: auto;
        }
        
        /* Text is drawn on canvas, no extra CSS needed for print */
    }
</style>

<div class="row no-print">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0">Certificate of Analysis (COA)</h4>
                <p class="text-muted mb-0">
                    <small>
                        <strong>Product:</strong> {{ $product->name }} | 
                        <strong>Current Batch:</strong> 
                        @if($orderProduct->pivot->batch_number)
                            <span class="badge bg-success">{{ $orderProduct->pivot->batch_number }}</span>
                        @else
                            <span class="badge bg-warning">Not Set</span>
                        @endif
                    </small>
                </p>
            </div>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('orderhistory') }}">Orders</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('orderdetails', $order->id) }}">Order #{{ $order->id }}</a></li>
                    <li class="breadcrumb-item active">COA</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="sticky-top bg-white p-3 shadow-sm mb-3 no-print" style="z-index: 100;">
    <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('orderdetails', $order->id) }}" class="btn btn-secondary">
            <i class="ri-arrow-left-line align-middle me-1"></i> Back to Order
        </a>
        <div class="d-flex gap-2">
                <button onclick="printCOA()" class="btn btn-success">
                    <i class="ri-printer-line align-middle me-1"></i> Print
                </button>
                <button onclick="downloadCOA()" class="btn btn-info">
                <i class="ri-download-2-line align-middle me-1"></i> Download
                </button>
            </div>
        </div>
    </div>
    
<div class="row g-3">
    <!-- LEFT SIDE: PDF Viewer -->
    <div class="col-lg-8 col-md-7">
        <div class="card h-100">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="card-title mb-0">COA Preview</h5>
                        <div class="vr"></div>
                        <select id="pdf-template-selector" class="form-select form-select-sm" style="width: auto;" onchange="changePdfTemplate()">
                            <option value="General_Exosome_COA.pdf" selected>General Exosome</option>
                            <option value="Cardio_Exsosome_COA.pdf">Cardio Exosome</option>
                            <option value="Well_Exosome_COA.pdf">Well Exosome</option>
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-2">
        <button onclick="previousPage()" class="btn btn-sm btn-outline-secondary" id="prev-page">
                            <i class="ri-arrow-left-s-line"></i>
        </button>
                        <span class="text-muted">
            Page <span id="page-num">1</span> of <span id="page-count">1</span>
        </span>
        <button onclick="nextPage()" class="btn btn-sm btn-outline-secondary" id="next-page">
                            <i class="ri-arrow-right-s-line"></i>
        </button>
                        <div class="vr"></div>
            <button onclick="changeZoom(0.9)" class="btn btn-sm btn-outline-secondary">-</button>
                        <span class="text-muted" id="zoom-level">100%</span>
            <button onclick="changeZoom(1.1)" class="btn btn-sm btn-outline-secondary">+</button>
        </div>
    </div>
</div>
            <div class="card-body p-0">
                <div id="loading" class="text-center p-5 text-muted fs-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading PDF...</span>
                    </div>
                    <p class="mt-3 mb-0">Loading Certificate of Analysis...</p>
                </div>
                <div id="pdf-container" class="position-relative mx-auto bg-light overflow-auto w-100" style="display: none;">
                    <div id="pdf-canvas-wrapper" class="position-relative my-3 mx-auto shadow">
                        <canvas id="pdf-canvas" class="d-block mx-auto bg-white"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE: Edit Form -->
    <div class="col-lg-4 col-md-5">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0 text-white">
                    <i class="ri-edit-line me-1"></i> Edit COA Information
                </h5>
            </div>
            <div class="card-body">
                <form id="coa-form">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product</label>
                        <input type="text" class="form-control" value="{{ $product->name }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="batch_number" class="form-label fw-semibold">
                            Batch Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="batch_number" name="batch_number" 
                               value="{{ $orderProduct->pivot->batch_number ?? '' }}" 
                               placeholder="Enter batch number">
                    </div>

                    <div class="mb-3">
                        <label for="mfg_date" class="form-label fw-semibold">
                            Manufacturing Date
                        </label>
                        <input type="text" class="form-control" id="mfg_date" name="mfg_date" 
                               value="{{ $order->order_date ? $order->order_date->format('F Y') : '' }}" 
                               placeholder="e.g., October 2025" readonly>
                        <small class="text-muted">Based on order date</small>
                    </div>

                    <div class="mb-3">
                        <label for="expiry_date" class="form-label fw-semibold">
                            Expiry Date
                        </label>
                        <input type="text" class="form-control" id="expiry_date" name="expiry_date" 
                               value="{{ $order->order_date ? $order->order_date->addYears(3)->format('F Y') : '' }}" 
                               placeholder="e.g., October 2028" readonly>
                        <small class="text-muted">Auto-calculated (3 years)</small>
                    </div>

                    <div class="mb-3">
                        <label for="prepared_by" class="form-label fw-semibold">
                            Prepared By
                        </label>
                        <input type="text" class="form-control" id="prepared_by" name="prepared_by" 
                               value="{{ $orderProduct->pivot->prepared_by ?? 'Quality Control Manager' }}" 
                               placeholder="Enter name">
                    </div>

                    <div class="mb-3">
                        <label for="qc_document_number" class="form-label fw-semibold">
                            QC Document Number
                        </label>
                        <input type="text" class="form-control" id="qc_document_number" name="qc_document_number" 
                               value="{{ $orderProduct->pivot->qc_document_number ?? 'MGRC/COA-' . $order->id . '-' . $product->id }}" 
                               placeholder="Enter QC document number">
                    </div>

                    <!-- Hidden fields for signature info (auto-generated, not editable) -->
                    <input type="hidden" id="signatory_name" value="{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}">
                    <input type="hidden" id="signatory_designation" value="Quality Control Manager">
                    <input type="hidden" id="signatory_date" value="{{ date('d F Y') }}">

                    <hr class="my-3">

                    <div class="alert alert-info alert-sm mb-3">
                        <i class="ri-information-line me-1"></i>
                        <small>
                            <strong>Live Preview:</strong> Changes appear on PDF in real-time<br>
                            <strong>Signature:</strong> Auto-generated from logged-in user<br>
                            <strong>Sync:</strong> Data syncs with Order Details page<br>
                            <strong>Templates:</strong> Choose from dropdown (same placement data)
                        </small>
                    </div>

                    <div class="d-grid">
                        <button type="button" onclick="saveCOA()" class="btn btn-primary btn-lg">
                            <i class="ri-save-line me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- PDF.js Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
console.log('=== COA Editor Debug Start ===');
console.log('📦 Batch Number Sync Status:');
console.log('  Current Batch: {{ $orderProduct->pivot->batch_number ?? "Not Set" }}');
console.log('  Product: {{ $product->name }}');
console.log('  Order: #{{ $order->id }}');
console.log('  ✅ Changes will sync with Order Details page');

// Check if PDF.js loaded
if (typeof pdfjsLib === 'undefined') {
    console.error('PDF.js library failed to load!');
    document.getElementById('loading').innerHTML = '<div class="alert alert-danger">PDF.js library failed to load. Check your internet connection.</div>';
} else {
    console.log('PDF.js library loaded successfully');
}

// PDF.js Configuration
try {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    console.log('PDF.js worker configured');
} catch (e) {
    console.error('Failed to configure PDF.js worker:', e);
}

// State variables
let pdfDoc = null;
let pageNum = 1;
let pageRendering = false;
let pageNumPending = null;
let scale = 1.5;
let canvas = document.getElementById('pdf-canvas');
let ctx = canvas ? canvas.getContext('2d') : null;
let editMode = false;

console.log('Canvas element:', canvas);
console.log('Canvas context:', ctx);

// Dynamic PDF URL based on selected template
let currentPdfTemplate = 'General_Exosome_COA.pdf';
const pdfBasePath = '{{ asset("assets/pdf") }}';
let url = pdfBasePath + '/' + currentPdfTemplate;
console.log('👤 Logged in user:', '{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}');

// Debug: Log the URL being attempted
console.log('Attempting to load PDF from:', url);
console.log('Full URL:', window.location.origin + url);

// Add timeout to detect stuck loading
let loadingTimeout = setTimeout(function() {
    console.warn('PDF loading taking longer than 10 seconds...');
    let loadingDiv = document.getElementById('loading');
    if (loadingDiv && loadingDiv.style.display !== 'none') {
        loadingDiv.innerHTML = '<div class="alert alert-warning">' +
            '<div class="spinner-border text-warning" role="status"><span class="visually-hidden">Loading...</span></div>' +
            '<p class="mt-2"><strong>Still loading...</strong></p>' +
            '<p><small>This is taking longer than expected. Please check:</small></p>' +
            '<ul class="text-start" style="font-size: 12px;">' +
            '<li>PDF file size (large files take longer)</li>' +
            '<li>Internet connection (for loading PDF.js libraries)</li>' +
            '<li>Browser console (F12) for errors</li>' +
            '</ul>' +
            '<p><small>URL: ' + url + '</small></p>' +
            '</div>';
    }
}, 10000);

// Check if pdfjsLib is available
if (typeof pdfjsLib === 'undefined') {
    clearTimeout(loadingTimeout);
    console.error('pdfjsLib is not defined!');
    document.getElementById('loading').innerHTML = '<div class="alert alert-danger">PDF.js library not loaded. Please refresh the page.</div>';
} else {
    pdfjsLib.getDocument({
        url: url,
        cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/cmaps/',
        cMapPacked: true
    }).promise.then(function(pdfDoc_) {
        clearTimeout(loadingTimeout);
        pdfDoc = pdfDoc_;
        console.log('PDF loaded successfully! Pages:', pdfDoc.numPages);
        document.getElementById('page-count').textContent = pdfDoc.numPages;
        document.getElementById('loading').style.display = 'none';
        document.getElementById('pdf-container').style.display = 'block';
        
        // Initial render
        renderPage(pageNum);
    }).catch(function(error) {
        clearTimeout(loadingTimeout);
        console.error('Error loading PDF:', error);
        console.error('Error name:', error.name);
        console.error('Error message:', error.message);
        console.error('PDF URL attempted:', url);
        
        let errorMsg = '<div class="alert alert-danger">';
        errorMsg += '<h5><strong>❌ Failed to Load PDF</strong></h5>';
        errorMsg += '<hr>';
        errorMsg += '<p><strong>URL:</strong> ' + url + '</p>';
        errorMsg += '<p><strong>Error:</strong> ' + error.message + '</p>';
        errorMsg += '<hr>';
        errorMsg += '<p><strong>🔧 Troubleshooting Steps:</strong></p>';
        errorMsg += '<ol class="text-start">';
        errorMsg += '<li>Open browser console (press F12) and check for errors</li>';
        errorMsg += '<li>Verify PDF exists: <code>public/assets/pdf/COA_REJUVMSC_Template.pdf</code></li>';
        errorMsg += '<li>Try opening the PDF directly: <a href="' + url + '" target="_blank">Click here</a></li>';
        errorMsg += '<li>Check file size - very large PDFs may fail to load</li>';
        errorMsg += '<li>Try a different browser (Chrome, Firefox)</li>';
        errorMsg += '</ol>';
        errorMsg += '</div>';
        
        document.getElementById('loading').innerHTML = errorMsg;
    });
}

/**
 * Render the page
 */
function renderPage(num) {
    pageRendering = true;
    
    pdfDoc.getPage(num).then(function(page) {
        const viewport = page.getViewport({scale: scale});
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        
        // Center the canvas
        canvas.style.width = viewport.width + 'px';
        canvas.style.height = viewport.height + 'px';
        
        const renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };
        
        const renderTask = page.render(renderContext);
        
        renderTask.promise.then(function() {
            pageRendering = false;
            console.log('Page rendered successfully:', num);
            console.log('Canvas dimensions:', canvas.width, 'x', canvas.height);
            
            // Overlay data on PDF for viewing/printing
            renderDataOverlay(num);
            
            if (pageNumPending !== null) {
                renderPage(pageNumPending);
                pageNumPending = null;
            }
        });
    });
    
    document.getElementById('page-num').textContent = num;
}

/**
 * Queue page rendering
 */
function queueRenderPage(num) {
    if (pageRendering) {
        pageNumPending = num;
    } else {
        renderPage(num);
    }
}

/**
 * Previous page
 */
function previousPage() {
    if (pageNum <= 1) {
        return;
    }
    pageNum--;
    queueRenderPage(pageNum);
}

/**
 * Next page
 */
function nextPage() {
    if (pageNum >= pdfDoc.numPages) {
        return;
    }
    pageNum++;
    queueRenderPage(pageNum);
}

/**
 * Change zoom level
 */
function changeZoom(factor) {
    scale *= factor;
    document.getElementById('zoom-level').textContent = Math.round(scale * 100 / 1.5) + '%';
    queueRenderPage(pageNum);
}

/**
 * Render data DIRECTLY on PDF canvas - Text becomes part of the PDF!
 */
function renderDataOverlay(page) {
    // Get current values from form
    const batchNumber = document.getElementById('batch_number')?.value || '';
    const mfgDate = document.getElementById('mfg_date')?.value || '';
    const expiryDate = document.getElementById('expiry_date')?.value || '';
    const userName = '{{ Auth::user()->username }}';  // Current logged-in user
    const signatoryName = document.getElementById('signatory_name')?.value || '';
    const signatoryDesignation = document.getElementById('signatory_designation')?.value || '';
    const signatoryDate = document.getElementById('signatory_date')?.value || '';
    const qcDocumentNumber = document.getElementById('qc_document_number')?.value || '';
    
    // ═══════════════════════════════════════════════════════
    // 📍 EDIT POSITIONS HERE - Adjust for General_Exosome_COA.pdf
    // ═══════════════════════════════════════════════════════
    // xPercent = LEFT/RIGHT position (0-100)
    // yPercent = UP/DOWN position (0-100)
    // 
    // To move UP → DECREASE yPercent
    // To move DOWN → INCREASE yPercent
    // To move LEFT → DECREASE xPercent
    // To move RIGHT → INCREASE xPercent
    // ═══════════════════════════════════════════════════════
    
    let dataFields = [];
    
    // Page 1: Batch number, dates, signature section
    if (page === 1) {
        dataFields = [
            { value: batchNumber, xPercent: 39.8, yPercent: 23, isRegular: true },
            { value: mfgDate, xPercent: 39.8, yPercent: 26, isRegular: true },
            { value: expiryDate, xPercent: 39.8, yPercent: 29, isRegular: true },
            { value: userName, xPercent: 50, yPercent: 90, isSignature: true },  // Signature in Mistral font
            { value: signatoryName, xPercent: 50, yPercent: 92, isRegular: true, centered: true },  // Name under signature
            { value: signatoryDesignation, xPercent: 50, yPercent: 93.5, isRegular: true, centered: true },  // Designation
            { value: signatoryDate, xPercent: 50, yPercent: 95, isRegular: true, centered: true },  // Date
            { value: qcDocumentNumber, xPercent: 13.75, yPercent: 98.40, isQcDoc: true }  // QC Document Number at bottom left
        ];
    }
    // Page 2: Batch number, dates, signature section, QC doc
    else if (page === 2) {
        dataFields = [
            { value: batchNumber, xPercent: 45, yPercent: 18.70, isRegular: true },
            { value: mfgDate, xPercent: 45, yPercent: 21.70, isRegular: true },
            { value: expiryDate, xPercent: 45, yPercent: 24.70, isRegular: true },
            { value: userName, xPercent: 50, yPercent: 90, isSignature: true },  // Signature in Mistral font
            { value: signatoryName, xPercent: 50, yPercent: 92, isRegular: true, centered: true },  // Name under signature
            { value: signatoryDesignation, xPercent: 50, yPercent: 93.5, isRegular: true, centered: true },  // Designation
            { value: signatoryDate, xPercent: 50, yPercent: 95, isRegular: true, centered: true },  // Date
            { value: qcDocumentNumber, xPercent: 13.75, yPercent: 98.40, isQcDoc: true }  // QC Document Number at bottom left
        ];
    }
    // Page 3: QC Document Number only
    else if (page === 3) {
        dataFields = [
            { value: qcDocumentNumber, xPercent: 13.75, yPercent: 98.40, isQcDoc: true }  // QC Document Number at bottom left
        ];
    }
    
    // If no fields for this page, return early
    if (dataFields.length === 0) return;
    
    // Draw text directly on canvas
    const canvasWidth = canvas.width;
    const canvasHeight = canvas.height;
    
    // ═══════════════════════════════════════════════════════
    // 📝 FONT SIZES - Edit here to adjust text sizes
    // ═══════════════════════════════════════════════════════
    const regularFontSize = Math.round(22 * scale / 1.5);
    const signatureFontSize = Math.round(36 * scale / 1.5);  // Larger for signature
    const signatoryInfoFontSize = Math.round(18 * scale / 1.5);  // Font size 18 for name, designation, date (increased for visibility)
    const qcDocNumberFontSize = Math.round(10 * scale / 1.5);  // ⭐ EDIT THIS: Font size for QC Document Number (currently 12)
    
    // Draw each field on the canvas
    dataFields.forEach(field => {
        if (!field.value) return; // Skip empty values
        
        // Calculate position on canvas (in actual canvas pixels)
        const x = (canvasWidth * field.xPercent / 100);
        const y = (canvasHeight * field.yPercent / 100);
        
        // Set font based on field type
        if (field.isSignature) {
            // Signature: Mistral font, larger size, centered
            ctx.font = `${signatureFontSize}px 'Mistral', 'Brush Script MT', cursive`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
        } else if (field.centered) {
            // Signatory info (name, designation, date): Calibri Bold, size 18, centered
            ctx.font = `bold ${signatoryInfoFontSize}px Calibri, Arial, sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
        } else if (field.isQcDoc) {
            // QC Document Number: Smaller Calibri font
            ctx.font = `${qcDocNumberFontSize}px Calibri, Arial, sans-serif`;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
        } else {
            // Regular fields: Calibri font
            ctx.font = `${regularFontSize}px Calibri, Arial, sans-serif`;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
        }
        
        ctx.fillStyle = '#000000';
        
        // Draw text on canvas
        ctx.fillText(field.value, x, y);
    });
    
    console.log('📝 Data drawn directly on PDF canvas (scales with zoom!)');
    console.log('Regular font:', regularFontSize + 'px', '| Signature font:', signatureFontSize + 'px', '| Scale:', scale);
    console.log('✍️ Signature:', userName || 'Not available');
}

/**
 * Update data overlay when form changes - Re-renders the page
 */
function updateDataOverlay() {
    if (pdfDoc && pageNum) {
        // Re-render the entire page to redraw text on canvas
        renderPage(pageNum);
    }
}

// Listen for form changes to update overlay in real-time
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const formInputs = document.querySelectorAll('#coa-form input');
        formInputs.forEach(input => {
            input.addEventListener('input', updateDataOverlay);
            input.addEventListener('change', updateDataOverlay);
        });
    }, 1000);
});

/**
 * Save COA data from sidebar form - Syncs with Order Details batch information
 */
function saveCOA() {
    // Get form data (signature info is auto-generated and not saved)
    const data = {
        batch_number: document.getElementById('batch_number').value,
        prepared_by: document.getElementById('prepared_by').value,
        qc_document_number: document.getElementById('qc_document_number').value
    };
    
    console.log('💾 Saving COA data:', data);
    
    // Show loading state
    const saveBtn = event?.target || document.querySelector('button[onclick="saveCOA()"]');
    const originalText = saveBtn?.innerHTML;
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="ri-loader-4-line align-middle me-1 spinner-border spinner-border-sm"></i> Saving...';
    }
    
    // Send data to server
    fetch('{{ route("orders.coa.save", ["order" => $order->id, "product" => $product->id]) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        // Restore button state
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
        
        if (data.success) {
            // Success notification
            console.log('✅ COA data saved successfully!');
            
            // Update PDF overlay with new data
            updateDataOverlay();
            
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <i class="ri-checkbox-circle-line me-2"></i>
                <strong>Success!</strong> COA data saved and synced with Order Details.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('#coa-form').insertBefore(alert, document.querySelector('#coa-form').firstChild);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => alert.remove(), 3000);
        } else {
            alert('❌ Error saving COA data: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        // Restore button state
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
        console.error('❌ Error:', error);
        alert('❌ Error saving COA data. Please check console for details.');
    });
}

/**
 * Print COA - All Pages
 */
async function printCOA() {
    // Disable edit mode before printing
    if (editMode) {
        toggleEditMode();
    }
    
    if (!pdfDoc) {
        alert('PDF not loaded yet. Please wait...');
        return;
    }
    
    console.log('🖨️ Preparing to print all pages...');
    
    // Create a temporary container for all pages
    const printContainer = document.createElement('div');
    printContainer.id = 'print-all-pages';
    printContainer.style.display = 'none';
    document.body.appendChild(printContainer);
    
    try {
        // Render all pages with high-resolution scale for print quality
        const printScale = 2.5;  // Higher scale = better print quality (2.5x resolution)
        
        for (let i = 1; i <= pdfDoc.numPages; i++) {
            console.log('Rendering page', i, 'for print...');
            
            const page = await pdfDoc.getPage(i);
            const viewport = page.getViewport({scale: printScale});
            
            // Create high-resolution canvas for this page
            const pageCanvas = document.createElement('canvas');
            pageCanvas.width = viewport.width;
            pageCanvas.height = viewport.height;
            pageCanvas.className = 'print-page';
            pageCanvas.style.pageBreakAfter = 'always';
            pageCanvas.style.display = 'block';
            pageCanvas.style.margin = '0 auto';
            // Scale down display size while keeping high resolution
            pageCanvas.style.width = (viewport.width / printScale * 1.3) + 'px';
            pageCanvas.style.height = (viewport.height / printScale * 1.3) + 'px';
            
            const pageCtx = pageCanvas.getContext('2d');
            
            // Render PDF page
            await page.render({
                canvasContext: pageCtx,
                viewport: viewport
            }).promise;
            
            // Overlay data on this page (same logic as renderDataOverlay)
            await renderDataOnCanvas(pageCanvas, pageCtx, i, printScale);
            
            printContainer.appendChild(pageCanvas);
        }
        
        // Show print container and trigger print
        printContainer.style.display = 'block';
        
        setTimeout(() => {
            window.print();
            
            // Clean up after print dialog closes
            setTimeout(() => {
                printContainer.remove();
                console.log('✅ Print complete, cleaned up temporary canvases');
            }, 1000);
        }, 500);
        
    } catch (error) {
        console.error('Error preparing print:', error);
        alert('Error preparing pages for printing');
        printContainer.remove();
    }
}

/**
 * Helper function to render data on a specific canvas
 */
function renderDataOnCanvas(canvas, ctx, page, canvasScale = scale) {
    // Get current values from form
    const batchNumber = document.getElementById('batch_number')?.value || '';
    const mfgDate = document.getElementById('mfg_date')?.value || '';
    const expiryDate = document.getElementById('expiry_date')?.value || '';
    const userName = '{{ Auth::user()->username }}';
    const signatoryName = document.getElementById('signatory_name')?.value || '';
    const signatoryDesignation = document.getElementById('signatory_designation')?.value || '';
    const signatoryDate = document.getElementById('signatory_date')?.value || '';
    const qcDocumentNumber = document.getElementById('qc_document_number')?.value || '';
    
    let dataFields = [];
    
    // Page 1: Batch number, dates, signature section
    if (page === 1) {
        dataFields = [
            { value: batchNumber, xPercent: 39.8, yPercent: 23, isRegular: true },
            { value: mfgDate, xPercent: 39.8, yPercent: 26, isRegular: true },
            { value: expiryDate, xPercent: 39.8, yPercent: 29, isRegular: true },
            { value: userName, xPercent: 50, yPercent: 90, isSignature: true },
            { value: signatoryName, xPercent: 50, yPercent: 92, isRegular: true, centered: true },
            { value: signatoryDesignation, xPercent: 50, yPercent: 93.5, isRegular: true, centered: true },
            { value: signatoryDate, xPercent: 50, yPercent: 95, isRegular: true, centered: true },
            { value: qcDocumentNumber, xPercent: 13.75, yPercent: 98.40, isQcDoc: true }
        ];
    }
    // Page 2: Batch number, dates, signature section, QC doc
    else if (page === 2) {
        dataFields = [
            { value: batchNumber, xPercent: 45, yPercent: 18.70, isRegular: true },
            { value: mfgDate, xPercent: 45, yPercent: 21.70, isRegular: true },
            { value: expiryDate, xPercent: 45, yPercent: 24.70, isRegular: true },
            { value: userName, xPercent: 50, yPercent: 90, isSignature: true },
            { value: signatoryName, xPercent: 50, yPercent: 92, isRegular: true, centered: true },
            { value: signatoryDesignation, xPercent: 50, yPercent: 93.5, isRegular: true, centered: true },
            { value: signatoryDate, xPercent: 50, yPercent: 95, isRegular: true, centered: true },
            { value: qcDocumentNumber, xPercent: 13.75, yPercent: 98.40, isQcDoc: true }
        ];
    }
    // Page 3: QC Document Number only
    else if (page === 3) {
        dataFields = [
            { value: qcDocumentNumber, xPercent: 13.75, yPercent: 98.40, isQcDoc: true }
        ];
    }
    
    if (dataFields.length === 0) return;
    
    const canvasWidth = canvas.width;
    const canvasHeight = canvas.height;
    
    const regularFontSize = Math.round(22 * canvasScale / 1.5);
    const signatureFontSize = Math.round(36 * canvasScale / 1.5);
    const signatoryInfoFontSize = Math.round(18 * canvasScale / 1.5);
    const qcDocNumberFontSize = Math.round(10 * canvasScale / 1.5);
    
    dataFields.forEach(field => {
        if (!field.value) return;
        
        const x = (canvasWidth * field.xPercent / 100);
        const y = (canvasHeight * field.yPercent / 100);
        
        if (field.isSignature) {
            ctx.font = `${signatureFontSize}px 'Mistral', 'Brush Script MT', cursive`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
        } else if (field.centered) {
            ctx.font = `bold ${signatoryInfoFontSize}px Calibri, Arial, sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
        } else if (field.isQcDoc) {
            ctx.font = `${qcDocNumberFontSize}px Calibri, Arial, sans-serif`;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
        } else {
            ctx.font = `${regularFontSize}px Calibri, Arial, sans-serif`;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
        }
        
        ctx.fillStyle = '#000000';
        ctx.fillText(field.value, x, y);
    });
}

/**
 * Download COA as PDF
 */
function downloadCOA() {
    // Use html2canvas to capture the current view with edits
    const container = document.getElementById('pdf-canvas-wrapper');
    
    html2canvas(container, {
        scale: 2,
        useCORS: true,
        logging: false
    }).then(canvas => {
        // Convert to blob and download
        canvas.toBlob(function(blob) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'COA_Order{{ $order->id }}_{{ $product->name }}_' + new Date().getTime() + '.png';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    });
}

/**
 * Change PDF Template
 */
function changePdfTemplate() {
    const selector = document.getElementById('pdf-template-selector');
    const selectedTemplate = selector.value;
    
    console.log('🔄 Changing PDF template to:', selectedTemplate);
    
    // Show loading
    document.getElementById('loading').style.display = 'block';
    document.getElementById('pdf-container').style.display = 'none';
    
    // Update current template and URL
    currentPdfTemplate = selectedTemplate;
    url = pdfBasePath + '/' + currentPdfTemplate;
    
    // Reset page number
    pageNum = 1;
    
    // Load new PDF
    pdfjsLib.getDocument({
        url: url,
        cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/cmaps/',
        cMapPacked: true
    }).promise.then(function(pdfDoc_) {
        pdfDoc = pdfDoc_;
        console.log('✅ New PDF loaded successfully! Pages:', pdfDoc.numPages);
        document.getElementById('page-count').textContent = pdfDoc.numPages;
        document.getElementById('loading').style.display = 'none';
        document.getElementById('pdf-container').style.display = 'block';
        
        // Render first page
        renderPage(pageNum);
    }).catch(function(error) {
        console.error('❌ Error loading new PDF:', error);
        
        let errorMsg = '<div class="alert alert-danger">';
        errorMsg += '<h5><strong>❌ Failed to Load PDF Template</strong></h5>';
        errorMsg += '<hr>';
        errorMsg += '<p><strong>Template:</strong> ' + selectedTemplate + '</p>';
        errorMsg += '<p><strong>URL:</strong> ' + url + '</p>';
        errorMsg += '<p><strong>Error:</strong> ' + error.message + '</p>';
        errorMsg += '<hr>';
        errorMsg += '<p><strong>🔧 Troubleshooting:</strong></p>';
        errorMsg += '<ol class="text-start">';
        errorMsg += '<li>Verify PDF exists in: <code>public/assets/pdf/' + selectedTemplate + '</code></li>';
        errorMsg += '<li>Try opening the PDF directly: <a href="' + url + '" target="_blank">Click here</a></li>';
        errorMsg += '<li>Check browser console (F12) for detailed errors</li>';
        errorMsg += '</ol>';
        errorMsg += '</div>';
        
        document.getElementById('loading').innerHTML = errorMsg;
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'p':
                e.preventDefault();
                printCOA();
                break;
            case 's':
                e.preventDefault();
                saveCOA();
                break;
        }
    }
    
    // Arrow keys for navigation
    if (!editMode) {
        if (e.key === 'ArrowLeft') {
            previousPage();
        } else if (e.key === 'ArrowRight') {
            nextPage();
        }
    }
});

console.log('✅ COA Editor with Sidebar Form - Ready!');
console.log('📝 Edit fields in the sidebar, changes sync with Order Details');
</script>
@endsection

