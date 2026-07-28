@extends('layouts.master')

@section('content')

{{--
    COA Editor — data-driven.

    Everything on this page is driven by the template resolved for this order
    line in OrderController::showCOA():

      $templateKey   e.g. 'nk'
      $template      the config/coa_templates.php entry (label, pdf, coordinates)
      $editable      which fields to show, in order
      $fieldLabels   human labels for those fields
      $acceptsImage  whether this template has a morphology image slot
      $pdfUrl        the blank template PDF
      $coaValues     current saved values (patient name pre-filled from the order)

    The values are drawn onto the PDF with PDF.js at the coordinates measured
    from the template, so what QC sees on screen is what prints.
--}}

@php
    // Flatten the coordinate config into something the JS can read directly.
    $coords = $template['coordinates'] ?? [];
    // The signature and product/expiry dates on cell products are printed with
    // specific fonts; the config carries the font + size per field.
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Certificate of Analysis</h4>
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
</div>

<div class="sticky-top bg-white p-3 shadow-sm mb-3 no-print" style="z-index: 100;">
    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('orderdetails', $order->id) }}" class="btn btn-secondary">
            <i class="ri-arrow-left-line align-middle me-1"></i> Back to Order
        </a>
        <div class="d-flex gap-2 align-items-center">
            @php
                // With alternates present the badge names the certificate and the
                // toggle carries the wording, so the two don't repeat each other.
                $badgeLabel = !empty($variants)
                    ? ($template['variant_group_label'] ?? $template['label'])
                    : $template['label'];
            @endphp
            <span class="badge bg-success fs-6">{{ $badgeLabel }}</span>

            @if(!empty($variants))
                {{-- Alternate wordings of the same certificate. Open to Quality,
                     not just superadmins: whether the patient's name appears is a
                     QC decision taken per order, not a product setting. --}}
                <form method="POST"
                      action="{{ route('orders.coa.template', [$order->id, $product->id]) }}"
                      onsubmit="return confirmVariantSwitch()"
                      class="btn-group btn-group-sm" role="group"
                      aria-label="Certificate version">
                    @csrf
                    @foreach($variants as $vKey => $vLabel)
                        <button type="submit" name="coa_template" value="{{ $vKey }}"
                                class="btn {{ $vKey === $templateKey ? 'btn-success' : 'btn-outline-secondary' }}">
                            {{ $vLabel }}
                        </button>
                    @endforeach
                </form>
            @endif

            @if(Auth::user()->role === 'superadmin')
                <button onclick="toggleChangeTemplate()" class="btn btn-outline-secondary btn-sm" title="Change template (superadmin)">
                    <i class="ri-refresh-line align-middle"></i>
                </button>
            @endif
            <button onclick="printCOA()" class="btn btn-success">
                <i class="ri-printer-line align-middle me-1"></i> Print
            </button>
            <button onclick="downloadCOA()" class="btn btn-info">
                <i class="ri-download-2-line align-middle me-1"></i> Download
            </button>
        </div>
    </div>

    @if(Auth::user()->role === 'superadmin')
        {{-- Superadmin-only: re-point this order line to a different template --}}
        <div id="change-template-panel" class="mt-3 p-3 border rounded bg-light" style="display: none;">
            <form method="POST" action="{{ route('orders.coa.template', [$order->id, $product->id]) }}" class="d-flex gap-2 align-items-end">
                @csrf
                <div class="flex-grow-1">
                    <label class="form-label mb-1 small fw-semibold">Change COA template for this order line</label>
                    <select name="coa_template" class="form-select form-select-sm">
                        @foreach(config('coa_templates') as $k => $t)
                            <option value="{{ $k }}" @selected($k === $templateKey)>{{ $t['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </form>
            <small class="text-muted d-block mt-1">Only affects this order. The product's default is unchanged.</small>
        </div>
    @endif
</div>

<div class="row g-3">
    <!-- LEFT: PDF preview -->
    <div class="col-lg-8 col-md-7">
        <div class="card h-100">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0">COA Preview</h5>
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

    <!-- RIGHT: edit form -->
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

                    @foreach($editable as $field)
                        @php
                            // COA No. is stored in qc_document_number (same value the
                            // Order Details "QC Doc" column shows), so its input name
                            // is qc_document_number even though the label is "COA No."
                            $inputName = $field === 'coa_number' ? 'qc_document_number' : $field;
                            $val = $field === 'coa_number'
                                ? ($coaValues['qc_document_number'] ?? '')
                                : ($coaValues[$field] ?? '');
                            $label = $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field));
                        @endphp
                        <div class="mb-3">
                            <label for="f_{{ $field }}" class="form-label fw-semibold">{{ $label }}</label>
                            <input type="text"
                                   class="form-control coa-field"
                                   id="f_{{ $field }}"
                                   data-field="{{ $field }}"
                                   name="{{ $inputName }}"
                                   value="{{ $val }}"
                                   placeholder="Enter {{ strtolower($label) }}"
                                   autocomplete="off">
                        </div>
                    @endforeach

                    {{-- Prepared By is not drawn on the certificate but is kept for
                         the Order Details page, so preserve the existing field. --}}
                    <div class="mb-3">
                        <label for="prepared_by" class="form-label fw-semibold">Prepared By</label>
                        <input type="text" class="form-control" id="prepared_by" name="prepared_by"
                               value="{{ $coaValues['prepared_by'] ?: 'Quality Control Manager' }}"
                               placeholder="Enter name">
                    </div>

                    @if($acceptsImage)
                        <hr class="my-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Morphology of Cells Image</label>
                            <input type="file" class="form-control" id="morphology_image"
                                   accept="image/jpeg,image/png">
                            <small class="text-muted">Shown on page 2. JPEG or PNG, up to 8 MB — auto-resized to fit the frame.</small>
                            <div id="morphology-preview" class="mt-2" style="{{ $coaValues['morphology_image'] ? '' : 'display:none;' }}">
                                <img src="{{ $coaValues['morphology_image'] ?? '' }}"
                                     alt="Morphology" class="img-fluid border rounded" style="max-height: 120px;">
                            </div>
                            <button type="button" onclick="uploadMorphology()" class="btn btn-outline-primary btn-sm mt-2" id="morphology-upload-btn">
                                <i class="ri-upload-2-line me-1"></i> Upload Image
                            </button>
                        </div>
                    @endif

                    {{-- Signature is auto-generated from the logged-in user. --}}
                    <input type="hidden" id="signature_name" value="{{ Auth::user()->username ?? (Auth::user()->first_name ?? '') }}">

                    <hr class="my-3">

                    <div class="alert alert-info alert-sm mb-3">
                        <i class="ri-information-line me-1"></i>
                        <small>
                            <strong>Live Preview:</strong> changes appear on the PDF as you type.<br>
                            <strong>Signature:</strong> auto-generated from your login.<br>
                            <strong>COA No.:</strong> also shows in the Order's QC Doc column.
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
<!-- PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<!-- jsPDF: builds the downloadable file client-side, so Download saves a real
     PDF instead of re-opening the print dialog. -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
// ─── Config injected from the resolved template ──────────────────────────────
console.log('COA editor script starting');

window.COA = {
    pdfUrl:      @json($pdfUrl),
    pageWidth:   {{ $template['page_width'] ?? 540 }},
    pageHeight:  {{ $template['page_height'] ?? 780 }},
    pages:       {{ $template['pages'] ?? 2 }},
    coordinates: @json($coords),
    editable:    @json($editable),
    signatureName: @json(Auth::user()->username ?? (Auth::user()->first_name ?? '')),
    morphologyUrl: @json($coaValues['morphology_image'] ?? null),
    uploadUrl:   @json(route('orders.coa.morphology', [$order->id, $product->id])),
    saveUrl:     @json(route('orders.coa.save', [$order->id, $product->id])),
    csrf:        @json(csrf_token()),
};
const COA = window.COA;
console.log('COA config loaded. pages =', COA.pages, '| template pdf =', COA.pdfUrl);

// Map each editable form field to the coordinate key it draws at.
// coa_number draws wherever the config calls it coa_number.
function fieldValue(field) {
    if (field === 'coa_number') {
        const el = document.querySelector('[data-field="coa_number"]');
        return el ? el.value : '';
    }
    const el = document.querySelector('[data-field="' + field + '"]');
    return el ? el.value : '';
}

// ─── PDF.js setup ────────────────────────────────────────────────────────────
if (typeof pdfjsLib !== 'undefined') {
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
}

let pdfDoc = null;
let pageNum = 1;
let pageRendering = false;
let pageNumPending = null;
let scale = 1.5;
let morphologyImg = null;   // loaded HTMLImageElement, if any

const canvas = document.getElementById('pdf-canvas');
const ctx = canvas ? canvas.getContext('2d') : null;

// Preload an existing morphology image so it draws on first render.
if (COA.morphologyUrl) {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => { morphologyImg = img; if (pdfDoc) renderPage(pageNum); };
    img.src = COA.morphologyUrl;
}

if (typeof pdfjsLib === 'undefined') {
    document.getElementById('loading').innerHTML =
        '<div class="alert alert-danger">PDF viewer failed to load. Check your connection and refresh.</div>';
} else {
    pdfjsLib.getDocument({
        url: COA.pdfUrl,
        cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/cmaps/',
        cMapPacked: true
    }).promise.then(function (doc) {
        pdfDoc = doc;
        document.getElementById('page-count').textContent = Math.min(doc.numPages, COA.pages || doc.numPages);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('pdf-container').style.display = 'block';
        renderPage(pageNum);
    }).catch(function (err) {
        document.getElementById('loading').innerHTML =
            '<div class="alert alert-danger"><strong>Failed to load PDF.</strong><br>' +
            'URL: ' + COA.pdfUrl + '<br>Error: ' + err.message + '</div>';
    });
}

function renderPage(num) {
    if (!pdfDoc) return;
    pageRendering = true;

    pdfDoc.getPage(num).then(function (page) {
        const viewport = page.getViewport({ scale: scale });
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        canvas.style.width = viewport.width + 'px';
        canvas.style.height = viewport.height + 'px';

        const task = page.render({ canvasContext: ctx, viewport: viewport });
        task.promise.then(function () {
            pageRendering = false;
            drawOverlay(num);
            if (pageNumPending !== null) {
                renderPage(pageNumPending);
                pageNumPending = null;
            }
        });
    });

    document.getElementById('page-num').textContent = num;
}

function queueRenderPage(num) {
    if (pageRendering) pageNumPending = num;
    else renderPage(num);
}

function previousPage() { if (pageNum > 1) { pageNum--; queueRenderPage(pageNum); } }
function nextPage() { const max = Math.min(pdfDoc ? pdfDoc.numPages : 1, COA.pages || 99); if (pdfDoc && pageNum < max) { pageNum++; queueRenderPage(pageNum); } }

function changeZoom(factor) {
    scale *= factor;
    document.getElementById('zoom-level').textContent = Math.round(scale * 100 / 1.5) + '%';
    queueRenderPage(pageNum);
}

// ─── Draw the field values onto the current page ─────────────────────────────
//
// The on-screen preview and the print/download output must be pixel-identical,
// so both go through drawOverlayOn() below. Keeping one implementation is what
// stops the preview and the printed sheet drifting apart.
function drawOverlay(page) {
    drawOverlayOn(ctx, canvas, page, scale);
}

// Re-draw as the user types.
function refreshOverlay() { if (pdfDoc) renderPage(pageNum); }

// ─── Unsaved-change guard ────────────────────────────────────────────────────
//
// Switching wording reloads the page, so anything typed but not saved would be
// lost silently. Snapshot the form on load and warn only when it has actually
// changed. File inputs are excluded: the image is uploaded separately and is
// already persisted by the time it matters.
const coaInitial = {};

function coaTrackedInputs() {
    return document.querySelectorAll('#coa-form input.coa-field, #coa-form #prepared_by');
}

function coaFormDirty() {
    return Array.prototype.some.call(coaTrackedInputs(), function (i) {
        return (coaInitial[i.id] || '') !== i.value;
    });
}

function confirmVariantSwitch() {
    if (!coaFormDirty()) return true;
    return confirm('You have unsaved changes. Switching to the other version will discard them.\n\nContinue?');
}

document.addEventListener('DOMContentLoaded', function () {
    coaTrackedInputs().forEach(function (i) { coaInitial[i.id] = i.value; });

    document.querySelectorAll('#coa-form input.coa-field').forEach(function (input) {
        input.addEventListener('input', refreshOverlay);
        input.addEventListener('change', refreshOverlay);
    });
});

// ─── Superadmin: toggle the change-template panel ────────────────────────────
function toggleChangeTemplate() {
    const p = document.getElementById('change-template-panel');
    if (p) p.style.display = p.style.display === 'none' ? 'block' : 'none';
}

// ─── Save ────────────────────────────────────────────────────────────────────
function saveCOA() {
    const data = { prepared_by: document.getElementById('prepared_by').value };

    // Every editable field, sent under its real input name.
    document.querySelectorAll('#coa-form input.coa-field').forEach(function (input) {
        data[input.name] = input.value;
    });

    const btn = document.querySelector('button[onclick="saveCOA()"]');
    const original = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line spinner-border spinner-border-sm me-1"></i> Saving...'; }

    fetch(COA.saveUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': COA.csrf },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (btn) { btn.disabled = false; btn.innerHTML = original; }
        if (res.success) {
            refreshOverlay();
            // The form now matches what is stored, so a variant switch should
            // no longer warn about losing work.
            coaTrackedInputs().forEach(function (i) { coaInitial[i.id] = i.value; });
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = '<i class="ri-checkbox-circle-line me-2"></i><strong>Saved.</strong> COA data updated.' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            const form = document.querySelector('#coa-form');
            form.insertBefore(alert, form.firstChild);
            setTimeout(() => alert.remove(), 3000);
        } else {
            alert('Error saving: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(err => {
        if (btn) { btn.disabled = false; btn.innerHTML = original; }
        alert('Error saving COA data. Check the console for details.');
        console.error(err);
    });
}

// ─── Morphology image upload ─────────────────────────────────────────────────
// QC uploads straight off the microscope — typically a 4:3 frame of a few MB.
// Anything past this is refused here with a plain message, rather than being
// posted and bouncing off PHP's upload limit as an opaque server error.
const MORPHOLOGY_MAX_MB = 8;

function uploadMorphology() {
    const input = document.getElementById('morphology_image');
    if (!input || !input.files.length) { alert('Choose an image first.'); return; }

    const file = input.files[0];
    const sizeMb = file.size / (1024 * 1024);
    if (sizeMb > MORPHOLOGY_MAX_MB) {
        alert('That image is ' + sizeMb.toFixed(1) + ' MB. Please upload a JPEG or PNG under '
              + MORPHOLOGY_MAX_MB + ' MB.');
        return;
    }

    const btn = document.getElementById('morphology-upload-btn');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line spinner-border spinner-border-sm me-1"></i> Uploading...';

    const fd = new FormData();
    fd.append('morphology_image', input.files[0]);
    fd.append('_token', COA.csrf);

    fetch(COA.uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': COA.csrf }, body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = original;
            if (res.success && res.url) {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => { morphologyImg = img; refreshOverlay(); };
                img.src = res.url + '?t=' + Date.now();   // bust cache

                const prev = document.getElementById('morphology-preview');
                if (prev) { prev.style.display = ''; prev.querySelector('img').src = res.url + '?t=' + Date.now(); }
            } else {
                alert('Upload failed: ' + (res.message || 'Unknown error'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = original;
            alert('Upload failed. Check the console.');
            console.error(err);
        });
}

// ─── Print / download ────────────────────────────────────────────────────────
//
// Render every page to a high-res canvas, overlay the data, and hand the
// resulting images to a clean standalone window that prints them. Building the
// images in THIS window (which already has a working PDF.js) and passing only
// finished JPEGs to the print window means the print window loads no scripts at
// all — so the theme's bundled PDF.js and other plugins can't interfere, which
// was the source of the blank/extra pages.
const PRINT_SCALE = 3.0;

// Build the CSS font string for a coordinate entry.
//
// The config carries a `font` name per field (e.g. 'Calibri-Bold'). This used
// to be ignored, so a value whose template label is bold — the signature date
// next to a bold "Date:" — was drawn in regular weight and read as misaligned
// even when its position was right. Honour the name instead.
function cssFontFor(c, field, renderScale) {
    const size = Math.round((c.font_size || 10) * renderScale);
    const name = String(c.font || '').toLowerCase();

    if (field === 'signature' || name.indexOf('mistral') !== -1) {
        return size + "px 'Mistral', 'Brush Script MT', cursive";
    }

    const weight = name.indexOf('bold') !== -1 ? 'bold ' : '';
    const style  = name.indexOf('italic') !== -1 ? 'italic ' : '';
    // Carlito is the metric-compatible Calibri substitute present on most Linux
    // boxes, so the server-rendered and Windows-rendered output stay in step.
    return style + weight + size + "px Calibri, Carlito, Arial, sans-serif";
}

// Optional per-field nudges, expressed in PDF points so they read in the same
// units as font_size and stay correct at any zoom or print scale.
//   dx  positive = right      dy  positive = down
function nudgeX(c, cnv) { return (c.dx || 0) * (cnv.width  / COA.pageWidth); }
function nudgeY(c, cnv) { return (c.dy || 0) * (cnv.height / COA.pageHeight); }

function drawOverlayOn(context, cnv, page, renderScale) {
    const coords = COA.coordinates['page' + page];
    if (!coords) return;
    const W = cnv.width, H = cnv.height;

    // Morphology micrograph (page 2), drawn first so text sits on top.
    if (morphologyImg && coords.morphology_slot) {
        const s = coords.morphology_slot;
        const x = W * s.x / 100, y = H * s.y / 100, w = W * s.w / 100, h = H * s.h / 100;
        const ir = morphologyImg.width / morphologyImg.height, sr = w / h;

        // Fit inside the slot, preserving aspect ratio.
        let dw = w, dh = h;
        if (ir > sr) { dh = w / ir; }        // wider than the slot: width-bound
        else         { dw = h * ir; }        // taller than the slot: height-bound

        // Anchor within the slot. A 4:3 micrograph is height-bound in these
        // slots, so centring left a wide white gutter on the left of the frame;
        // the certificate wants the image flush with the left margin.
        const halign = s.align || 'left';
        const valign = s.valign || 'middle';

        let dx = x;
        if (halign === 'center') dx = x + (w - dw) / 2;
        else if (halign === 'right') dx = x + (w - dw);

        let dy = y;
        if (valign === 'middle') dy = y + (h - dh) / 2;
        else if (valign === 'bottom') dy = y + (h - dh);

        context.drawImage(morphologyImg, dx, dy, dw, dh);
    }

    context.fillStyle = '#000000';
    context.textAlign = 'left';
    context.textBaseline = 'top';

    for (const field of COA.editable) {
        const c = coords[field];
        if (!c) continue;
        const value = fieldValue(field);
        if (!value) continue;
        context.font = cssFontFor(c, field, renderScale);
        context.fillText(
            value,
            W * c.x / 100 + nudgeX(c, cnv),
            H * c.y / 100 + nudgeY(c, cnv)
        );
    }

    if (coords.signature && COA.signatureName) {
        const c = coords.signature;
        context.font = cssFontFor(c, 'signature', renderScale);
        context.fillText(
            COA.signatureName,
            W * c.x / 100 + nudgeX(c, cnv),
            H * c.y / 100 + nudgeY(c, cnv)
        );
    }
}

// Build one JPEG data-URL per PDF page, with the data overlaid.
async function buildPageImages() {
    const images = [];
    // Cap at the template's known page count. A conflicting PDF.js elsewhere on
    // the page can misreport pdfDoc.numPages; the config is the source of truth.
    const configured = (window.COA && window.COA.pages) ? window.COA.pages : 2;
    const total = Math.min(pdfDoc.numPages, configured);
    for (let i = 1; i <= total; i++) {
        const page = await pdfDoc.getPage(i);
        const viewport = page.getViewport({ scale: PRINT_SCALE });
        const c = document.createElement('canvas');
        c.width = viewport.width;
        c.height = viewport.height;
        const cctx = c.getContext('2d');
        await page.render({ canvasContext: cctx, viewport: viewport }).promise;
        drawOverlayOn(cctx, c, i, PRINT_SCALE);
        images.push(c.toDataURL('image/jpeg', 0.95));
    }
    return images;
}

// Write a minimal, script-free HTML doc containing exactly the page images.
// Each image is forced to fit within a single printed page. The blank-page
// artefact came from a full-width image being TALLER than the page (the PDF
// page ratio 540:780 is taller than A4), spilling a sliver onto a second
// physical page. Constraining by height (100vh) and letting width scale keeps
// every image on exactly one page.
function printWindowHtml(images) {
    let body = '';
    images.forEach(function (src) {
        body += '<div class="coa-page"><img src="' + src + '"></div>';
    });
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>COA</title>' +
        '<style>' +
        '@page { size: A4 portrait; margin: 0; }' +
        '* { margin:0; padding:0; box-sizing:border-box; }' +
        'html,body { margin:0; padding:0; }' +
        '.coa-page {' +
            ' width:100%;' +
            ' height:100vh;' +               /* exactly one viewport/page tall */
            ' display:flex;' +
            ' align-items:center;' +
            ' justify-content:center;' +
            ' overflow:hidden;' +
            ' page-break-after:always;' +
            ' break-after:page;' +
        '}' +
        '.coa-page:last-child {' +
            ' page-break-after:auto;' +       /* no break after the final page */
            ' break-after:auto;' +
        '}' +
        '.coa-page img {' +
            ' max-width:100%;' +
            ' max-height:100%;' +
            ' width:auto;' +
            ' height:auto;' +
            ' display:block;' +
        '}' +
        '</style></head><body>' + body + '</body></html>';
}

async function printCOA() {
    if (!pdfDoc) { alert('PDF not loaded yet.'); return; }

    const printBtn = document.querySelector('button[onclick="printCOA()"]');
    const restore = setButtonBusy(printBtn, 'Preparing...');

    // buildPageImages already caps at the template's page count, so the extra
    // pages a conflicting PDF.js might report never reach here.
    let finalImages;
    try {
        finalImages = await buildPageImages();
    } catch (err) {
        restore();
        console.error(err);
        alert('Could not prepare the certificate for printing.');
        return;
    }
    restore();

    const w = window.open('', '_blank');
    if (!w) { alert('Please allow pop-ups for this site to print the COA.'); return; }

    w.document.open();
    w.document.write(printWindowHtml(finalImages));
    w.document.close();

    const imgs = w.document.images;
    let done = 0;
    function maybePrint() {
        done++;
        if (done >= imgs.length) {
            setTimeout(function () { w.focus(); w.print(); }, 300);
        }
    }
    if (imgs.length === 0) { w.focus(); w.print(); return; }
    for (let i = 0; i < imgs.length; i++) {
        if (imgs[i].complete) maybePrint();
        else { imgs[i].onload = maybePrint; imgs[i].onerror = maybePrint; }
    }
}

// ─── Download ────────────────────────────────────────────────────────────────
//
// Download used to call printCOA(), so both buttons did the same thing and the
// user had to walk through the printer dialog and pick "Save as PDF". Here we
// assemble the same page images into a real PDF with jsPDF and hand it to the
// browser as a file, so the download starts on its own.

function sanitiseForFilename(s) {
    return String(s || '').replace(/[^A-Za-z0-9._-]+/g, '_').replace(/^_+|_+$/g, '');
}

// COA_<product>_<coa number>.pdf, falling back to the order id when the COA
// number has not been filled in yet. Read live from the form so the file is
// named correctly even before Save.
function downloadFilename() {
    const product = sanitiseForFilename(@json($product->name));
    const coaNo   = sanitiseForFilename(fieldValue('coa_number'));
    const tail    = coaNo || ('Order' + {{ (int) $order->id }});
    return ('COA_' + product + '_' + tail + '.pdf').replace(/_+/g, '_');
}

function setButtonBusy(btn, busyLabel) {
    if (!btn) return function () {};
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + busyLabel;
    return function () { btn.disabled = false; btn.innerHTML = original; };
}

async function downloadCOA() {
    if (!pdfDoc) { alert('PDF not loaded yet.'); return; }

    // If the jsPDF CDN did not load, fall back to the old behaviour rather than
    // leaving the button dead.
    if (!window.jspdf || !window.jspdf.jsPDF) {
        console.warn('jsPDF unavailable — falling back to the print dialog.');
        return printCOA();
    }

    const btn = document.querySelector('button[onclick="downloadCOA()"]');
    const restore = setButtonBusy(btn, 'Preparing...');

    try {
        const images = await buildPageImages();
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });

        const pageW = doc.internal.pageSize.getWidth();
        const pageH = doc.internal.pageSize.getHeight();

        // The template is 540x780pt, which is a taller ratio than A4. Fit by
        // whichever axis binds first and centre the result, so the proportions
        // match what the preview and the printed sheet show.
        const ratio = COA.pageWidth / COA.pageHeight;
        let w = pageW, h = pageW / ratio;
        if (h > pageH) { h = pageH; w = pageH * ratio; }
        const offsetX = (pageW - w) / 2;
        const offsetY = (pageH - h) / 2;

        images.forEach(function (src, i) {
            if (i > 0) doc.addPage();
            doc.addImage(src, 'JPEG', offsetX, offsetY, w, h);
        });

        doc.save(downloadFilename());
    } catch (err) {
        console.error(err);
        alert('Could not build the PDF. Check the console for details.');
    } finally {
        restore();
    }
}
</script>

@endsection