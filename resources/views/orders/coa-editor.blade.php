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

    Coordinate schema v2: every y is a text BASELINE, not a bounding-box top.
    See the header of config/coa_templates.php for why that matters.
--}}

@php
    // Flatten the coordinate config into something the JS can read directly.
    $coords = $template['coordinates'] ?? [];

    // Immunophenotyping results live in a table on page 2, so they get their
    // own block in the form rather than being mixed in with the page 1 fields.
    $mainFields   = [];
    $immunoFields = [];
    foreach ($editable as $f) {
        if (strpos($f, 'immuno_') === 0) {
            $immunoFields[] = $f;
        } else {
            $mainFields[] = $f;
        }
    }
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

                    @foreach($mainFields as $field)
                        @php
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

                    {{-- "Prepared By" removed: no COA template prints it, and it is
                         still editable on the Batch Information form. --}}

                    @if(!empty($immunoFields))
                        <hr class="my-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold mb-1">Immunophenotyping</label>
                            <small class="text-muted d-block mb-2">
                                Flow cytometry results for Table 1 on page 2. Numbers only &mdash; the
                                template already prints the &ldquo;%&rdquo; column heading.
                            </small>
                            <div class="row g-2">
                                @foreach($immunoFields as $field)
                                    @php
                                        $label = $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field));
                                    @endphp
                                    <div class="col-6">
                                        <label for="f_{{ $field }}" class="form-label small mb-1">{{ $label }}</label>
                                        <input type="text"
                                               class="form-control form-control-sm coa-field"
                                               id="f_{{ $field }}"
                                               data-field="{{ $field }}"
                                               name="{{ $field }}"
                                               value="{{ $coaValues[$field] ?? '' }}"
                                               placeholder="—"
                                               autocomplete="off">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($acceptsImage)
                        <hr class="my-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Morphology of Cells Image</label>
                            <input type="file" class="form-control" id="morphology_image"
                                   accept="image/jpeg,image/png,.jpg,.jpeg,.png">
                            <small class="text-muted">Shown on page 2. JPG, JPEG or PNG, up to {{ $morphologyMaxMb }} MB — scaled to fill the frame, so the edges may be cropped.</small>
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

                    <div class="alert alert-warning alert-sm mb-3">
                        <i class="ri-printer-line me-1"></i>
                        <small>
                            <strong>Printing on certificate paper:</strong> in the print dialog set
                            <strong>Margins&nbsp;=&nbsp;None</strong> and <strong>Scale&nbsp;=&nbsp;100%</strong>,
                            and turn off &ldquo;Fit to page&rdquo;. The certificate is already sized to
                            sit inside the gold border.
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

// Map each editable field to the coordinate key it draws at.
function fieldValue(field) {
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
let morphologyImg = null;

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
function drawOverlay(page) {
    drawOverlayOn(ctx, canvas, page);
}

// Re-draw as the user types.
function refreshOverlay() { if (pdfDoc) renderPage(pageNum); }

// ─── Unsaved-change guard ────────────────────────────────────────────────────
const coaInitial = {};

function coaTrackedInputs() {
    return document.querySelectorAll('#coa-form input.coa-field');
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
    const data = {};

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
const MORPHOLOGY_MAX_MB = {{ $morphologyMaxMb }};

function uploadMorphology() {
    const input = document.getElementById('morphology_image');
    if (!input || !input.files || !input.files[0]) {
        alert('Please choose an image first.');
        return;
    }

    const file = input.files[0];
    if (file.size > MORPHOLOGY_MAX_MB * 1024 * 1024) {
        alert('That image is larger than ' + MORPHOLOGY_MAX_MB + ' MB. Please choose a smaller file.');
        return;
    }

    const btn = document.getElementById('morphology-upload-btn');
    const restore = setButtonBusy(btn, 'Uploading...');

    const fd = new FormData();
    fd.append('morphology_image', input.files[0]);
    fd.append('_token', COA.csrf);

    fetch(COA.uploadUrl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            restore();
            if (res.success && res.url) {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => { morphologyImg = img; refreshOverlay(); };
                img.src = res.url + '?t=' + Date.now();

                const prev = document.getElementById('morphology-preview');
                if (prev) {
                    prev.style.display = '';
                    const pimg = prev.querySelector('img');
                    if (pimg) pimg.src = res.url + '?t=' + Date.now();
                }
            } else {
                alert('Upload failed: ' + (res.message || 'Unknown error'));
            }
        })
        .catch(err => {
            restore();
            console.error(err);
            alert('Upload failed. Check the console for details.');
        });
}

// ─── Overlay drawing ─────────────────────────────────────────────────────────
// Resolution at which the printable/downloadable page images are rasterised.
const PRINT_SCALE = 3.0;

/* ── Paper fit ────────────────────────────────────────────────────────────────
   The certificate artwork is 540 x 780 pt (7.50 x 10.83 in — the PowerPoint
   slide size). A4 is 595.28 x 841.89 pt. MGRC certificate paper carries a
   printed gold border; nothing may cross it.

   The inner edge of that border, measured from a 200 dpi scan of a blank
   sheet and then squared up on A4:

       x 40.42 -> 554.86      (514.44 wide)
       y 35.39 -> 806.51      (771.12 tall)

   The artwork is WIDER than the frame (540 > 514.44), so printing it at its
   designed size — never mind stretching it to the full sheet — always ran the
   page 2 contact strip and the REMARKS stamps over the gold rule. Scaling to
   fit the frame and centring inside it is the only placement that clears the
   border on all four sides.

   Placement is expressed in absolute millimetres in the print stylesheet. The
   previous version sized the image as a percentage of 100vh, and vh units are
   not dependable inside a print context — that is why the printed sheets came
   out at roughly 107% and pushed the footer onto the border.

   TUNING — change these numbers only, then reprint:
     PAPER.mode        'certificate' fits inside the gold border
                       'plain'       artwork at designed size, centred on A4
     PAPER.clearance   gap left between the artwork and the gold rule, in pt
     PAPER.shiftX/Y    + moves right / down, - moves left / up (1 mm = 2.835 pt)

   Print with Margins = None and Scale = 100%, or the browser resizes the sheet
   again on top of this.
──────────────────────────────────────────────────────────────────────────────*/
const PAPER = {
    w: 595.28,                                          // A4 portrait, points
    h: 841.89,
    frame: { x: 40.42, y: 35.39, w: 514.44, h: 771.12 },// inner edge of the gold rule
    clearance: 4.0,
    mode: 'certificate',
    shiftX: 0,
    shiftY: 0,
};

// Where the artwork sits on the sheet, in PDF points.
function paperPlacement() {
    const aw = COA.pageWidth, ah = COA.pageHeight;
    let s, x, y;

    if (PAPER.mode === 'certificate') {
        const f = PAPER.frame, c = PAPER.clearance;
        s = Math.min((f.w - 2 * c) / aw, (f.h - 2 * c) / ah);
        x = f.x + (f.w - aw * s) / 2;
        y = f.y + (f.h - ah * s) / 2;
    } else {
        s = 1;
        x = (PAPER.w - aw) / 2;
        y = (PAPER.h - ah) / 2;
    }

    return { scale: s, x: x + PAPER.shiftX, y: y + PAPER.shiftY, w: aw * s, h: ah * s };
}

// Build the CSS font string for a coordinate entry.
//   px = the coordinate's font_size (in template points) x canvas pixels-per-point
function cssFontFor(c, pxPerPt) {
    const size = (c.font_size || 10) * pxPerPt;
    const name = String(c.font || '').toLowerCase();

    if (name.indexOf('mistral') !== -1) {
        return size.toFixed(2) + "px 'Mistral', 'Brush Script MT', cursive";
    }

    const weight = name.indexOf('bold') !== -1 ? 'bold ' : '';
    const style  = name.indexOf('italic') !== -1 ? 'italic ' : '';
    return style + weight + size.toFixed(2) + "px Calibri, Carlito, Arial, sans-serif";
}

// Draw one value at its coordinate.
//
// y is a BASELINE, so we draw with textBaseline='alphabetic'. That is the one
// vertical reference every font agrees on, which is what makes the value land
// on the same line as the label printed beside it whether the browser resolved
// Calibri, Carlito or Arial.
function drawValue(context, cnv, c, text) {
    if (!c || text === null || text === undefined || text === '') return;

    const W = cnv.width, H = cnv.height;
    const sx = W / COA.pageWidth;       // canvas pixels per template point
    const sy = H / COA.pageHeight;

    context.font = cssFontFor(c, sy);
    context.textBaseline = 'alphabetic';

    // A value with a width budget (currently the COA number, whose length
    // varies per batch) is stepped down until it fits rather than being allowed
    // to run over the right-hand rule and off the certificate border.
    if (c.max_w) {
        const budget = W * c.max_w / 100;
        let size = c.font_size || 10;
        while (size > 3 && context.measureText(text).width > budget) {
            size -= 0.25;
            context.font = cssFontFor({ font: c.font, font_size: size }, sy);
        }
    }

    const centred = (c.align === 'center');
    context.textAlign = centred ? 'center' : 'left';

    const xPct = centred ? (c.cx !== undefined ? c.cx : c.x) : c.x;
    const x = W * xPct / 100 + (c.dx || 0) * sx;
    const y = H * c.y  / 100 + (c.dy || 0) * sy;

    context.fillText(text, x, y);
}

// Draw the micrograph into its slot.
//
// 'cover' scales the image so the frame is completely filled and crops whatever
// overhangs — QC asked for no white gutter. 'contain' is the old behaviour and
// is kept so a template can opt out in config without a code change.
function drawMorphology(context, cnv, slot) {
    if (!morphologyImg || !slot) return;

    const W = cnv.width, H = cnv.height;
    const x = W * slot.x / 100, y = H * slot.y / 100;
    const w = W * slot.w / 100, h = H * slot.h / 100;

    const iw = morphologyImg.width, ih = morphologyImg.height;
    if (!iw || !ih) return;

    const ir = iw / ih, sr = w / h;
    const ha = { left: 0, center: 0.5, right: 1 }[slot.align  || 'center'];
    const va = { top: 0, middle: 0.5, bottom: 1 }[slot.valign || 'middle'];

    if ((slot.fit || 'cover') === 'cover') {
        // Crop the source rectangle, then stretch it over the whole slot.
        let sw = iw, sh = ih, sx0 = 0, sy0 = 0;
        if (ir > sr) {                       // image wider than the slot: trim the sides
            sw = ih * sr;
            sx0 = (iw - sw) * ha;
        } else {                             // image taller: trim top and bottom
            sh = iw / sr;
            sy0 = (ih - sh) * va;
        }
        context.drawImage(morphologyImg, sx0, sy0, sw, sh, x, y, w, h);
        return;
    }

    // contain: whole image inside the slot, white space around it
    let dw = w, dh = h;
    if (ir > sr) dh = w / ir;
    else         dw = h * ir;
    context.drawImage(morphologyImg, x + (w - dw) * ha, y + (h - dh) * va, dw, dh);
}

function drawOverlayOn(context, cnv, page) {
    const coords = COA.coordinates['page' + page];
    if (!coords) return;

    // Micrograph first so text sits on top of it.
    drawMorphology(context, cnv, coords.morphology_slot);

    context.fillStyle = '#000000';

    for (const field of COA.editable) {
        drawValue(context, cnv, coords[field], fieldValue(field));
    }

    // The signature is centred over its printed rule, the way Word centres a
    // paragraph, so a long name and a short one both sit square on the line.
    drawValue(context, cnv, coords.signature, COA.signatureName);

    // Leave the context in a predictable state for the next caller.
    context.textAlign = 'left';
    context.textBaseline = 'alphabetic';
}

// Build one JPEG data-URL per PDF page, with the data overlaid.
async function buildPageImages() {
    const images = [];
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
        drawOverlayOn(cctx, c, i);
        images.push(c.toDataURL('image/jpeg', 0.95));
    }
    return images;
}

const PT_TO_MM = 25.4 / 72;
function mm(pt) { return (pt * PT_TO_MM).toFixed(3) + 'mm'; }

function printWindowHtml(images) {
    const p = paperPlacement();
    let body = '';
    images.forEach(function (src) {
        body += '<div class="coa-page"><img src="' + src + '"></div>';
    });

    // Absolute millimetres throughout: a print stylesheet must not depend on
    // viewport units, which the browser is free to reinterpret when it lays the
    // sheet out.
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>COA</title>' +
        '<style>' +
        '@page { size: A4 portrait; margin: 0; }' +
        '* { margin:0; padding:0; box-sizing:border-box; }' +
        'html,body { margin:0; padding:0; background:#fff; }' +
        '.coa-page {' +
            ' position:relative;' +
            ' width:210mm;' +
            ' height:297mm;' +
            ' overflow:hidden;' +
            ' page-break-after:always;' +
            ' break-after:page;' +
        '}' +
        '.coa-page:last-child {' +
            ' page-break-after:auto;' +
            ' break-after:auto;' +
        '}' +
        '.coa-page img {' +
            ' position:absolute;' +
            ' left:'   + mm(p.x) + ';' +
            ' top:'    + mm(p.y) + ';' +
            ' width:'  + mm(p.w) + ';' +
            ' height:' + mm(p.h) + ';' +
            ' display:block;' +
        '}' +
        '</style></head><body>' + body + '</body></html>';
}

async function printCOA() {
    if (!pdfDoc) { alert('PDF not loaded yet.'); return; }

    const printBtn = document.querySelector('button[onclick="printCOA()"]');
    const restore = setButtonBusy(printBtn, 'Preparing...');

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

function sanitiseForFilename(s) {
    return String(s || '').replace(/[^A-Za-z0-9._-]+/g, '_').replace(/^_+|_+$/g, '');
}

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

        // Same placement as the print path, so a downloaded file dropped onto
        // certificate paper lands exactly where the browser print would.
        const p = paperPlacement();

        images.forEach(function (src, i) {
            if (i > 0) doc.addPage();
            doc.addImage(src, 'JPEG', p.x, p.y, p.w, p.h);
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
