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

    Workflow: Quality Control fills the COA in and submits it. Submitting
    stores who signed it and locks it for everyone. To change a submitted COA,
    QC requests an edit; the COA approver (QC HOD) or a superadmin approves,
    which clears it for QC to fill in again, or rejects.

      $submitted       locked?
      $canEdit         may fill in and submit (QC / superadmin, not submitted)
      $canPrint        QC, QA, superadmin
      $canDownload     anyone once submitted; QC / superadmin also on a draft
      $canRequestEdit  QC, on a submitted COA
      $canDecideEdit   COA approver or superadmin

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

    // Which pages go on certificate paper, for the printing note.
    $pageCount = (int) ($template['pages'] ?? 2);
    $plainPages = array_values(array_diff(range(1, $pageCount), $certificatePages));
    $pageList = function (array $pages) {
        $names = array_map(fn ($p) => 'Page ' . $p, $pages);
        return count($names) > 1
            ? implode(', ', array_slice($names, 0, -1)) . ' and ' . end($names)
            : ($names[0] ?? '');
    };
@endphp

<style>
    /* Signature font, bundled so every PC draws the same signature. */
    @font-face {
        font-family: 'CoaSignature';
        src: url('{{ asset('assets/fonts/HerrVonMuellerhoff-Regular.ttf') }}') format('truetype');
        font-display: block;
    }
</style>

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

<div class="sticky-top bg-white p-3 shadow-sm mb-3 no-print" style="z-index: 100;">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a href="{{ route('orderdetails', $order->id) }}" class="btn btn-secondary">
            <i class="ri-arrow-left-line align-middle me-1"></i> Back to Order
        </a>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            @php
                // With alternates present the badge names the certificate and the
                // toggle carries the wording, so the two don't repeat each other.
                $badgeLabel = !empty($variants)
                    ? ($template['variant_group_label'] ?? $template['label'])
                    : $template['label'];
            @endphp
            <span class="badge bg-success fs-6">{{ $badgeLabel }}</span>

            @if($submitted)
                <span class="badge bg-dark fs-6" title="Submitted COAs cannot be changed without HOD approval">
                    <i class="ri-lock-line align-middle me-1"></i>Submitted
                </span>
            @else
                <span class="badge bg-warning text-dark fs-6" title="Not submitted yet">
                    <i class="ri-draft-line align-middle me-1"></i>Draft
                </span>
            @endif

            @if(!$canEdit && !$submitted)
                <span class="badge bg-secondary fs-6" title="Quality Control fills in and submits this COA">
                    <i class="ri-eye-line align-middle me-1"></i>View only
                </span>
            @endif

            @if($canEdit && !empty($variants))
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
            @elseif(!empty($variants))
                <span class="badge bg-light text-dark border fs-6">{{ $template['variant_label'] ?? $template['label'] }}</span>
            @endif

            @if(Auth::user()->role === 'superadmin' && !$submitted)
                <button onclick="toggleChangeTemplate()" class="btn btn-outline-secondary btn-sm" title="Change template (superadmin)">
                    <i class="ri-refresh-line align-middle"></i>
                </button>
            @endif

            @if($canPrint)
                <button onclick="printCOA()" class="btn btn-success">
                    <i class="ri-printer-line align-middle me-1"></i> Print
                </button>
            @endif

            @if($canDownload)
                <button onclick="downloadCOA()" class="btn btn-info">
                    <i class="ri-download-2-line align-middle me-1"></i> Download
                </button>
            @else
                <span class="d-inline-block" tabindex="0" title="Available after QC submits">
                    <button class="btn btn-info" disabled style="pointer-events: none;">
                        <i class="ri-download-2-line align-middle me-1"></i> Available after QC submits
                    </button>
                </span>
            @endif
        </div>
    </div>

    @if(Auth::user()->role === 'superadmin' && !$submitted)
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

    <!-- RIGHT: form -->
    <div class="col-lg-4 col-md-5">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0 text-white">
                    @if($canEdit)
                        <i class="ri-edit-line me-1"></i> Edit COA Information
                    @else
                        <i class="ri-eye-line me-1"></i> COA Information
                    @endif
                </h5>
            </div>
            <div class="card-body">

                @if($submitted)
                    <div class="alert alert-dark mb-3">
                        <i class="ri-lock-line me-1"></i>
                        <small>
                            <strong>Submitted</strong>
                            by {{ $signatoryName ?: ($submittedBy ? $submittedBy->fullName() : 'Quality Control') }}
                            @if($submittedAt) on {{ $submittedAt->format('j M Y, g:i A') }}@endif.
                            This COA is locked. To change it, Quality Control requests an edit and the HOD approves it.
                        </small>
                    </div>
                @endif

                <form id="coa-form" onsubmit="return false;">
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
                            $fc = $coords['page1'][$field] ?? [];
                            $suffix = $fc['suffix'] ?? null;
                        @endphp
                        <div class="mb-3">
                            <label for="f_{{ $field }}" class="form-label fw-semibold">{{ $label }}</label>
                            @if($suffix)
                                <div class="input-group">
                                    <input type="text"
                                           class="form-control coa-field"
                                           id="f_{{ $field }}"
                                           data-field="{{ $field }}"
                                           name="{{ $inputName }}"
                                           value="{{ $val }}"
                                           placeholder="Number only, e.g. 25"
                                           inputmode="decimal"
                                           autocomplete="off"
                                           @readonly(!$canEdit)>
                                    <span class="input-group-text">{{ trim($suffix) }}@if(!empty($fc['suffix_sup']))<sup>{{ $fc['suffix_sup'] }}</sup>@endif</span>
                                </div>
                            @else
                                <input type="text"
                                       class="form-control coa-field"
                                       id="f_{{ $field }}"
                                       data-field="{{ $field }}"
                                       name="{{ $inputName }}"
                                       value="{{ $val }}"
                                       placeholder="Enter {{ strtolower($label) }}"
                                       autocomplete="off"
                                       @readonly(!$canEdit)>
                            @endif
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
                                               autocomplete="off"
                                               @readonly(!$canEdit)>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($acceptsImage)
                        <hr class="my-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Morphology of Cells Image</label>
                            @if($canEdit)
                                <input type="file" class="form-control" id="morphology_image"
                                       accept="image/jpeg,image/png,.jpg,.jpeg,.png">
                                <small class="text-muted">Shown on page 2. JPG, JPEG or PNG, up to {{ $morphologyMaxMb }} MB — scaled to fill the frame, so the edges may be cropped. Upload it before submitting.</small>
                            @else
                                <small class="text-muted d-block">Shown on page 2. Uploaded by Quality Control.</small>
                            @endif
                            <div id="morphology-preview" class="mt-2" style="{{ $coaValues['morphology_image'] ? '' : 'display:none;' }}">
                                <img src="{{ $coaValues['morphology_image'] ?? '' }}"
                                     alt="Morphology" class="img-fluid border rounded" style="max-height: 120px;">
                            </div>
                            @if($canEdit)
                                <button type="button" onclick="uploadMorphology()" class="btn btn-outline-primary btn-sm mt-2" id="morphology-upload-btn">
                                    <i class="ri-upload-2-line me-1"></i> Upload Image
                                </button>
                            @endif
                        </div>
                    @endif

                    <hr class="my-3">

                    <div class="alert alert-info alert-sm mb-3">
                        <i class="ri-information-line me-1"></i>
                        <small>
                            @if($canEdit)
                                <strong>Live Preview:</strong> changes appear on the PDF as you type.<br>
                                <strong>Signature:</strong> your name. It is stored when you submit and does not change afterwards.<br>
                            @elseif($submitted)
                                <strong>Signature:</strong> the Quality Control staff who submitted this COA.<br>
                            @else
                                <strong>Signature:</strong> added when Quality Control submits this COA.<br>
                            @endif
                            <strong>COA No.:</strong> also shows in the Order's QC Doc column.
                        </small>
                    </div>

                    @if($canPrint)
                        <div class="alert alert-warning alert-sm mb-3">
                            <i class="ri-printer-line me-1"></i>
                            <small>
                                <strong>Printing</strong><br>
                                @if(!empty($certificatePages))
                                    {{ $pageList($certificatePages) }}: certificate paper (gold border).<br>
                                    @if(!empty($plainPages))
                                        {{ $pageList($plainPages) }}: plain A4 paper.<br>
                                    @endif
                                @else
                                    All pages: plain A4 paper. This COA does not use certificate paper.<br>
                                @endif
                                In the print dialog set <strong>Margins&nbsp;=&nbsp;None</strong> and
                                <strong>Scale&nbsp;=&nbsp;100%</strong>, and turn off &ldquo;Fit to page&rdquo;.
                                The pages are already sized for the paper, so any other setting will shift or shrink them.
                            </small>
                        </div>
                    @endif

                    @if($canEdit)
                        <div class="d-grid">
                            <button type="button" onclick="submitCOA()" class="btn btn-primary btn-lg" id="submit-coa-btn">
                                <i class="ri-send-plane-line me-1"></i> Submit COA
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Every field must be filled in. After submitting, the COA is locked and can only be
                            changed if the HOD approves a request to edit.
                        </small>
                    @elseif(!$submitted)
                        <div class="alert alert-secondary mb-0">
                            <i class="ri-eye-line me-1"></i>
                            <small>
                                <strong>View only.</strong> Quality Control fills in and submits this COA.
                                Download is available once it is submitted.
                            </small>
                        </div>
                    @endif
                </form>

                {{-- ── Request to edit a submitted COA ─────────────────────────── --}}
                @if($submitted)
                    <hr class="my-3">
                    <h6 class="fw-semibold mb-2"><i class="ri-edit-box-line me-1"></i> Request to Edit</h6>

                    @if($pendingRequest)
                        <div class="alert alert-warning mb-2">
                            <small>
                                <strong>Waiting for HOD approval.</strong><br>
                                Requested by {{ $pendingRequest->requester ? $pendingRequest->requester->fullName() : 'Unknown' }}
                                on {{ $pendingRequest->created_at->format('j M Y, g:i A') }}.<br>
                                <strong>Reason:</strong> {{ $pendingRequest->reason }}
                            </small>
                        </div>

                        @if($canDecideEdit)
                            <div class="d-flex gap-2">
                                <form method="POST" class="flex-fill"
                                      action="{{ route('orders.coa.edit-request.approve', [$order->id, $product->id, $pendingRequest->id]) }}"
                                      onsubmit="return confirm('Approve this request?\n\nEvery COA value on this order line will be cleared (COA No, dates, results, morphology image and signature). Quality Control then fills it in and submits it again.\n\nPatient name and batch number are kept.');">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="ri-check-line me-1"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" class="flex-fill"
                                      action="{{ route('orders.coa.edit-request.reject', [$order->id, $product->id, $pendingRequest->id]) }}"
                                      onsubmit="return confirm('Reject this request? The COA stays locked as it is.');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="ri-close-line me-1"></i> Reject
                                    </button>
                                </form>
                            </div>
                        @endif
                    @else
                        @if($lastDecided && $lastDecided->status === \App\Models\CoaEditRequest::STATUS_REJECTED)
                            <p class="text-muted small mb-2">
                                Last request was rejected by {{ $lastDecided->decider ? $lastDecided->decider->fullName() : 'the HOD' }}
                                on {{ $lastDecided->decided_at ? $lastDecided->decided_at->format('j M Y, g:i A') : '-' }}.
                            </p>
                        @endif

                        @if($canRequestEdit)
                            <form method="POST" action="{{ route('orders.coa.edit-request', [$order->id, $product->id]) }}">
                                @csrf
                                <div class="mb-2">
                                    <label for="edit-reason" class="form-label small mb-1">Reason</label>
                                    <textarea id="edit-reason" name="reason" rows="3" maxlength="1000"
                                              class="form-control form-control-sm @error('reason') is-invalid @enderror"
                                              placeholder="What needs to be corrected?" required>{{ old('reason') }}</textarea>
                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="ri-mail-send-line me-1"></i> Send Request to HOD
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    If approved, the whole COA is cleared and must be filled in again.
                                </small>
                            </form>
                        @else
                            <p class="text-muted small mb-0">No edit request. Quality Control can request one if a correction is needed.</p>
                        @endif
                    @endif
                @elseif($lastDecided && $lastDecided->status === \App\Models\CoaEditRequest::STATUS_APPROVED)
                    <p class="text-muted small mt-3 mb-0">
                        <i class="ri-history-line me-1"></i>
                        The previous submission was cleared on
                        {{ $lastDecided->decided_at ? $lastDecided->decided_at->format('j M Y, g:i A') : '-' }}
                        after {{ $lastDecided->decider ? $lastDecided->decider->fullName() : 'the HOD' }}
                        approved an edit request.
                    </p>
                @endif
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
    // Before submission: whoever is filling it in (blank for view-only
    // readers). After submission: the name stored with the COA, identical
    // for everyone who opens it.
    signatureName: @json($signatoryName),
    certificatePages: @json($certificatePages),
    canDownload: @json((bool) $canDownload),
    submitted:   @json((bool) $submitted),
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

// The signature font must be loaded before anything is drawn, or the canvas
// silently falls back to another font. Waits at most 4 seconds.
const SIGNATURE_FONT = 'CoaSignature';
let fontsReady = null;
function ensureFonts() {
    if (fontsReady) return fontsReady;
    const load = (document.fonts && document.fonts.load)
        ? document.fonts.load("24px '" + SIGNATURE_FONT + "'").catch(function () {})
        : Promise.resolve();
    const timeout = new Promise(function (resolve) { setTimeout(resolve, 4000); });
    fontsReady = Promise.race([load, timeout]);
    return fontsReady;
}

if (typeof pdfjsLib === 'undefined') {
    document.getElementById('loading').innerHTML =
        '<div class="alert alert-danger">PDF viewer failed to load. Check your connection and refresh.</div>';
} else {
    ensureFonts().then(function () { return pdfjsLib.getDocument({
        url: COA.pdfUrl,
        cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/cmaps/',
        cMapPacked: true
    }).promise; }).then(function (doc) {
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

window.addEventListener('beforeunload', function (e) {
    if (!COA.submitted && coaFormDirty()) {
        e.preventDefault();
        e.returnValue = '';
    }
});

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

// ─── Submit ──────────────────────────────────────────────────────────────────
// Submitting locks the COA, so every field is checked here first and again
// on the server.
function submitCOA() {
    const data = {};
    const missing = [];

    // Every editable field, sent under its real input name.
    coaTrackedInputs().forEach(function (input) {
        data[input.name] = input.value.trim();
        if (data[input.name] === '') {
            const label = document.querySelector('label[for="' + input.id + '"]');
            missing.push(label ? label.textContent.trim() : input.name);
        }
    });

    if (document.getElementById('morphology_image') && !morphologyImg) {
        missing.push('Morphology of Cells Image (upload it first)');
    }

    if (missing.length) {
        alert('Please fill in every field before submitting.\n\nMissing:\n- ' + missing.join('\n- '));
        return;
    }

    if (!confirm('Submit this COA?\n\nAfter submitting, it is locked and cannot be edited without HOD approval. ' +
                 'Your name is stored as the signature.')) {
        return;
    }

    const btn = document.getElementById('submit-coa-btn');
    const restore = setButtonBusy(btn, 'Submitting...');

    fetch(COA.saveUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': COA.csrf },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Reload so the page comes back locked, with the stored signature.
            coaTrackedInputs().forEach(function (i) { coaInitial[i.id] = i.value; });
            window.location.reload();
        } else {
            restore();
            alert(res.message || 'The COA could not be submitted.');
        }
    })
    .catch(err => {
        restore();
        alert('Error submitting the COA. Check the console for details.');
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
   slide size). A4 is 595.28 x 841.89 pt.

   Each page is placed one of two ways, set per template in
   config/coa_templates.php ('certificate_pages'):

   certificate  MGRC certificate paper carries a printed gold border; nothing
                may cross it. The inner edge of that border, measured from a
                200 dpi scan of a blank sheet and squared up on A4:

                    x 40.42 -> 554.86      (514.44 wide)
                    y 35.39 -> 806.51      (771.12 tall)

                The artwork is WIDER than the frame (540 > 514.44), so it is
                scaled to fit the frame and centred inside it. Page 1 of the
                cell templates (MSC P2, MSC P3, NK, NKT).

   plain        plain A4: the artwork at its designed size (100%), centred.
                About 11 mm white above and below and 10 mm at the sides, so
                no office printer clips the contact strip at the bottom of
                page 2. Every other page.

   Placement is expressed in absolute millimetres in the print stylesheet. vh
   units are not dependable inside a print context — an earlier version sized
   by 100vh and the printed sheets came out at roughly 107%.

   TUNING — change these numbers only, then reprint:
     PAPER.clearance   gap left between the artwork and the gold rule, in pt
     PAPER.shiftX/Y    + moves right / down, - moves left / up (1 mm = 2.835 pt)
                       (certificate pages only)

   Print with Margins = None and Scale = 100%, or the browser resizes the sheet
   again on top of this.
──────────────────────────────────────────────────────────────────────────────*/
const PAPER = {
    w: 595.28,                                          // A4 portrait, points
    h: 841.89,
    frame: { x: 40.42, y: 35.39, w: 514.44, h: 771.12 },// inner edge of the gold rule
    clearance: 4.0,
    shiftX: 0,
    shiftY: 0,
};

function isCertificatePage(pageNo) {
    return (COA.certificatePages || []).indexOf(pageNo) !== -1;
}

// Where page pageNo (1-based) of the artwork sits on the sheet, in PDF points.
function paperPlacement(pageNo) {
    const aw = COA.pageWidth, ah = COA.pageHeight;

    if (isCertificatePage(pageNo)) {
        const f = PAPER.frame, c = PAPER.clearance;
        const s = Math.min((f.w - 2 * c) / aw, (f.h - 2 * c) / ah);
        return {
            scale: s,
            x: f.x + (f.w - aw * s) / 2 + PAPER.shiftX,
            y: f.y + (f.h - ah * s) / 2 + PAPER.shiftY,
            w: aw * s,
            h: ah * s,
        };
    }

    return { scale: 1, x: (PAPER.w - aw) / 2, y: (PAPER.h - ah) / 2, w: aw, h: ah };
}

// Build the CSS font string for a coordinate entry.
//   px = the coordinate's font_size (in template points) x canvas pixels-per-point
function cssFontFor(c, pxPerPt, fontName, size) {
    size = (size || c.font_size || 10) * pxPerPt;
    const name = String(fontName || c.font || '').toLowerCase();

    if (name.indexOf('muellerhoff') !== -1) {
        return size.toFixed(2) + "px '" + SIGNATURE_FONT + "', cursive";
    }

    const weight = name.indexOf('bold') !== -1 ? 'bold ' : '';
    const style  = name.indexOf('italic') !== -1 ? 'italic ' : '';
    return style + weight + size.toFixed(2) + "px Calibri, Carlito, Arial, sans-serif";
}

// Split a value into the runs that get drawn, left to right:
//   coa_number         bold navy label, a gap, then the number
//   viable_cell_count  the number, ' x 10', then a raised superscript
//   anything else      the value on its own
// Each run: { text, font, size, color, rise, gapAfter } with size and rise in
// template points.
function runsFor(c, text) {
    const size  = c.font_size || 10;
    const color = c.color || '#000000';
    const hasValue = !(text === null || text === undefined || text === '');
    const runs = [];

    if (c.label) {
        runs.push({ text: c.label, font: c.label_font || c.font, size: size,
                    color: c.label_color || color, rise: 0,
                    gapAfter: hasValue ? (c.gap || 0) : 0 });
    }
    if (!hasValue) return runs;

    runs.push({ text: String(text), font: c.font, size: size, color: color, rise: 0, gapAfter: 0 });

    if (c.suffix) {
        runs.push({ text: c.suffix, font: c.font, size: size, color: color, rise: 0, gapAfter: 0 });
        if (c.suffix_sup) {
            // Superscript sized and lifted as on QC's printed templates:
            // 6.72 pt on 9.96 pt Calibri, baseline raised 3.0 pt.
            runs.push({ text: c.suffix_sup, font: c.font, size: size * 0.675, color: color,
                        rise: size * 0.302, gapAfter: 0 });
        }
    }
    return runs;
}

// Draw one value at its coordinate.
//
// y is a BASELINE, so we draw with textBaseline='alphabetic'. That is the one
// vertical reference every font agrees on, which is what makes the value land
// on the same line as the label printed beside it whether the browser resolved
// Calibri, Carlito or Arial.
function drawValue(context, cnv, c, text) {
    if (!c) return;
    const runs = runsFor(c, text);
    if (!runs.length) return;

    const W = cnv.width, H = cnv.height;
    const sx = W / COA.pageWidth;       // canvas pixels per template point
    const sy = H / COA.pageHeight;

    context.textBaseline = 'alphabetic';
    context.textAlign = 'left';

    // Width of the whole group at a given scale factor, in canvas pixels.
    function measure(k) {
        let w = 0;
        runs.forEach(function (r) {
            context.font = cssFontFor(c, sy, r.font, r.size * k);
            w += context.measureText(r.text).width + r.gapAfter * k * sx;
        });
        return w;
    }

    // A group with a width budget (the COA number, the signature) is stepped
    // down until it fits, rather than running into the title or off the rule.
    let k = 1;
    let width = measure(k);
    if (c.max_w) {
        const budget = W * c.max_w / 100;
        while (k > 0.3 && width > budget) {
            k -= 0.02;
            width = measure(k);
        }
    }

    const align = c.align || 'left';
    let x;
    if (align === 'right') {
        x = W * c.right / 100 - width;
    } else if (align === 'center') {
        x = W * (c.cx !== undefined ? c.cx : c.x) / 100 - width / 2;
    } else {
        x = W * c.x / 100;
    }
    x += (c.dx || 0) * sx;
    const y = H * c.y / 100 + (c.dy || 0) * sy;

    runs.forEach(function (r) {
        context.font = cssFontFor(c, sy, r.font, r.size * k);
        context.fillStyle = r.color;
        context.fillText(r.text, x, y - r.rise * k * sy);
        x += context.measureText(r.text).width + r.gapAfter * k * sx;
    });
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

    for (const field of COA.editable) {
        drawValue(context, cnv, coords[field], fieldValue(field));
    }

    // The COA No label is drawn even before a number is entered: it is no
    // longer printed on the PDF, because it has to move with the number.
    if (coords.coa_number && COA.editable.indexOf('coa_number') === -1) {
        drawValue(context, cnv, coords.coa_number, '');
    }

    // Signature over the rule and the printed name under it, both centred.
    drawValue(context, cnv, coords.signature, COA.signatureName);
    drawValue(context, cnv, coords.signatory_name, COA.signatureName);

    // Leave the context in a predictable state for the next caller.
    context.fillStyle = '#000000';
    context.textAlign = 'left';
    context.textBaseline = 'alphabetic';
}

// Build one JPEG data-URL per PDF page, with the data overlaid.
async function buildPageImages() {
    await ensureFonts();
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
    let body = '';
    images.forEach(function (src, i) {
        const p = paperPlacement(i + 1);
        body += '<div class="coa-page"><img src="' + src + '" style="' +
            'left:'   + mm(p.x) + ';' +
            'top:'    + mm(p.y) + ';' +
            'width:'  + mm(p.w) + ';' +
            'height:' + mm(p.h) + ';"></div>';
    });

    // Absolute millimetres throughout: a print stylesheet must not depend on
    // viewport units, which the browser is free to reinterpret when it lays the
    // sheet out. Each page carries its own placement (certificate or plain A4).
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
    if (!COA.canDownload) { alert('Download is available after Quality Control submits this COA.'); return; }
    if (!pdfDoc) { alert('PDF not loaded yet.'); return; }

    if (!window.jspdf || !window.jspdf.jsPDF) {
        alert('The PDF library did not load. Check your connection and refresh the page.');
        return;
    }

    const btn = document.querySelector('button[onclick="downloadCOA()"]');
    const restore = setButtonBusy(btn, 'Preparing...');

    try {
        const images = await buildPageImages();
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });

        // Same placement as the print path, page by page, so a downloaded
        // file printed later lands exactly where the browser print would.
        images.forEach(function (src, i) {
            if (i > 0) doc.addPage();
            const p = paperPlacement(i + 1);
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
