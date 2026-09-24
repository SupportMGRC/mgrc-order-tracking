@extends('layouts.master')

@section('content')
    <style>
        .customer-dropdown .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
            width: 100%;
        }
        .customer-dropdown .dropdown-item {
            padding: 0.5rem 1rem;
            white-space: normal;
            cursor: pointer;
        }
        .dropdown-menu-list {
            padding: 0;
            margin: 0;
        }
        .dropdown-menu-list li {
            border-bottom: 1px solid #f0f0f0;
        }
        .dropdown-menu-list li:last-child {
            border-bottom: none;
        }
        .new-customer-option {
            background-color: #f8f9fa;
            font-weight: 500;
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">New Pickup</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('pickups.index') }}">Pickups</a></li>
                        <li class="breadcrumb-item active">New Pickup</li>
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

    @if ($errors->any())
    <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-3 align-middle fs-16"></i><strong>Validation Error!</strong> Please check the form and try again.
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="alert alert-info alert-border-left d-flex align-items-center" role="alert">
                <i class="ri-information-line me-3 align-middle fs-16"></i>
                <div>
                    <strong>Pickup Request:</strong> This pickup will be recorded as requested by <strong>{{ Auth::user()->username }}</strong>
                </div>
            </div>

            @if($products->isEmpty())
            <div class="alert alert-warning alert-border-left" role="alert">
                <i class="ri-alert-line me-2 align-middle"></i>
                No pickup items are set up yet. An admin needs to add one in <strong>Product</strong> with Type set to <strong>Pickup</strong>.
            </div>
            @endif

            <div class="card">
                <div class="card-body checkout-tab">
                    <form action="{{ route('pickups.store') }}" method="POST" id="pickupForm" novalidate>
                        @csrf

                        <div class="step-arrow-nav mt-n3 mx-n3 mb-3">
                            <ul class="nav nav-pills nav-justified custom-nav bg-warning bg-opacity-75" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3 text-black active" id="pills-customer-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-customer" type="button" role="tab"
                                        aria-controls="pills-customer" aria-selected="true">
                                        <i class="ri-user-2-line fs-16 p-2 bg-warning text-black rounded-circle align-middle me-2"></i>
                                        Customer Details
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3 text-black" id="pills-pickup-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-pickup" type="button" role="tab"
                                        aria-controls="pills-pickup" aria-selected="false">
                                        <i class="las la-shipping-fast fs-16 p-2 bg-warning text-black rounded-circle align-middle me-2"></i>
                                        Pickup Details
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content">
                            {{-- Step 1: Customer --}}
                            <div class="tab-pane fade show active" id="pills-customer" role="tabpanel" aria-labelledby="pills-customer-tab">
                                <div>
                                    <h5 class="mb-1">Customer Information</h5>
                                    <p class="text-muted mb-4">Who the items are collected from</p>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label for="customer_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                        <div class="dropdown customer-dropdown">
                                            <input type="text" class="form-control search-customer @error('customer_name') is-invalid @enderror"
                                                id="customer_name" name="customer_name"
                                                placeholder="Type to search or enter new customer name"
                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                autocomplete="off"
                                                value="{{ old('customer_name') }}" required />
                                            <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id') }}">
                                            <div class="dropdown-menu w-100">
                                                <ul class="list-unstyled dropdown-menu-list mb-0" id="customer_list">
                                                    <li>
                                                        <a class="dropdown-item new-customer-option" href="#">
                                                            <i class="ri-add-line align-middle me-2"></i>Enter New Customer
                                                        </a>
                                                    </li>
                                                    @foreach($customers as $customer)
                                                        <li>
                                                            <a class="dropdown-item customer-option" href="#"
                                                                data-id="{{ $customer->id }}"
                                                                data-name="{{ $customer->name }}"
                                                                data-email="{{ $customer->email }}"
                                                                data-phone="{{ $customer->phoneNo }}"
                                                                data-address="{{ $customer->address }}">
                                                                <span class="fw-medium">{{ $customer->name }}</span>
                                                                <small class="text-muted">{{ $customer->phoneNo }}</small>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback">Please enter the customer name</div>
                                        <div class="form-text">Type to search existing customers or enter a new customer name</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="mb-3">
                                            <label for="customer_email" class="form-label">Email <span class="text-muted">(Optional)</span></label>
                                            <input type="email" class="form-control @error('customer_email') is-invalid @enderror"
                                                id="customer_email" name="customer_email" placeholder="Enter email"
                                                value="{{ old('customer_email') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="mb-3">
                                            <label for="customer_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('customer_phone') is-invalid @enderror"
                                                id="customer_phone" name="customer_phone" placeholder="Enter phone no."
                                                value="{{ old('customer_phone') }}" required>
                                            <div class="invalid-feedback">Please enter a phone number</div>
                                            <div class="form-text">The despatcher calls this number on arrival.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="customer_address" class="form-label">Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('customer_address') is-invalid @enderror"
                                        id="customer_address" name="customer_address" placeholder="Enter address"
                                        rows="3" required>{{ old('customer_address') }}</textarea>
                                    <div class="invalid-feedback">Please enter the customer address</div>
                                </div>

                                <div class="d-flex flex-column flex-sm-row align-items-start gap-3 mt-3">
                                    <button type="button" class="btn btn-success btn-label right ms-auto w-100 w-sm-auto" id="toPickupDetails">
                                        <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                        Next to Pickup Details
                                    </button>
                                </div>
                            </div>

                            {{-- Step 2: Pickup details --}}
                            <div class="tab-pane fade" id="pills-pickup" role="tabpanel" aria-labelledby="pills-pickup-tab">
                                <div>
                                    <h5 class="mb-1">Pickup Details</h5>
                                    <p class="text-muted mb-4">What to collect, where and when. All items in one pickup must be for the same department.</p>
                                </div>

                                @php
                                    $oldItems = old('items', [['product_id' => '', 'quantity' => 1]]);
                                @endphp

                                <div id="pickupItems" class="mb-3">
                                    @foreach($oldItems as $i => $line)
                                    <div class="pickup-item p-2 border rounded bg-light shadow-sm mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-primary mb-0"><i class="ri-test-tube-line me-1"></i>Pickup Item</h6>
                                            <button type="button" class="btn btn-sm btn-danger remove-item">
                                                <i class="ri-delete-bin-line me-1"></i>Remove
                                            </button>
                                        </div>
                                        <hr class="mt-1 mb-3">
                                        <div class="row">
                                            <div class="col-md-8 col-12 mb-2">
                                                <label class="form-label">Item <span class="text-danger">*</span></label>
                                                <select class="form-select item-product" name="items[{{ $i }}][product_id]" required>
                                                    <option value="">Select item...</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}" data-department="{{ $product->receiving_department }}" @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback">Please select an item</div>
                                            </div>
                                            <div class="col-md-4 col-12 mb-2">
                                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control item-quantity" name="items[{{ $i }}][quantity]"
                                                    min="1" max="999" value="{{ $line['quantity'] ?? 1 }}" required>
                                                <div class="invalid-feedback">Please enter a quantity</div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <button type="button" class="btn btn-info btn-sm mb-4" id="addItem">
                                    <i class="ri-add-line me-1"></i>Add More Items
                                </button>

                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="mb-3">
                                            <label for="contact_person" class="form-label">Contact Person <span class="text-muted">(Optional)</span></label>
                                            <input type="text" class="form-control" id="contact_person" name="contact_person"
                                                placeholder="Person to hand over the items" value="{{ old('contact_person') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Time Sensitive Pickup</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="time_sensitive" name="time_sensitive" value="1" @checked(old('time_sensitive'))>
                                                <label class="form-check-label" for="time_sensitive">
                                                    <i class="ri-time-line me-1"></i>Mark as Time Sensitive
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="pickup_address" class="form-label">Pickup Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('pickup_address') is-invalid @enderror" id="pickup_address" name="pickup_address"
                                        rows="3" placeholder="Enter pickup address" required>{{ old('pickup_address') }}</textarea>
                                    <div class="invalid-feedback">Please enter the pickup address</div>
                                    <div class="form-text">Filled from the customer address. Change it if the items are collected elsewhere.</div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="pickup_date" class="form-label">Pickup Date <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('pickup_date') is-invalid @enderror" id="pickup_date" name="pickup_date"
                                                placeholder="Select date" value="{{ old('pickup_date') }}" required>
                                            <div class="invalid-feedback">Please select a date</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="pickup_time" class="form-label">Pickup Time <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('pickup_time') is-invalid @enderror" id="pickup_time" name="pickup_time"
                                                placeholder="e.g. 02:30 PM" value="{{ old('pickup_time') }}" required>
                                            <div class="invalid-feedback">Please select a time</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="3"
                                        placeholder="Anything the despatcher should know">{{ old('remarks') }}</textarea>
                                </div>

                                <div class="d-flex flex-column flex-sm-row align-items-start gap-3 mt-3">
                                    <button type="button" class="btn btn-light btn-label w-100 w-sm-auto" id="backToCustomer">
                                        <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                        Back to Customer Details
                                    </button>
                                    <button type="submit" id="savePickupBtn" class="btn btn-success btn-label right ms-auto w-100 w-sm-auto" @disabled($products->isEmpty())>
                                        <i class="ri-save-line label-icon align-middle fs-16 ms-2"></i>
                                        Save Pickup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Blank item row used by "Add More Items". __INDEX__ is replaced in JS. --}}
    <template id="pickupItemTemplate">
        <div class="pickup-item p-2 border rounded bg-light shadow-sm mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-primary mb-0"><i class="ri-test-tube-line me-1"></i>Pickup Item</h6>
                <button type="button" class="btn btn-sm btn-danger remove-item">
                    <i class="ri-delete-bin-line me-1"></i>Remove
                </button>
            </div>
            <hr class="mt-1 mb-3">
            <div class="row">
                <div class="col-md-8 col-12 mb-2">
                    <label class="form-label">Item <span class="text-danger">*</span></label>
                    <select class="form-select item-product" name="items[__INDEX__][product_id]" required>
                        <option value="">Select item...</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" data-department="{{ $product->receiving_department }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback">Please select an item</div>
                </div>
                <div class="col-md-4 col-12 mb-2">
                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" class="form-control item-quantity" name="items[__INDEX__][quantity]" min="1" max="999" value="1" required>
                    <div class="invalid-feedback">Please enter a quantity</div>
                </div>
            </div>
        </div>
    </template>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('pickupForm');
    const customerTabBtn = document.getElementById('pills-customer-tab');
    const pickupTabBtn = document.getElementById('pills-pickup-tab');
    const customerPane = document.getElementById('pills-customer');
    const pickupPane = document.getElementById('pills-pickup');

    // ---- Date / time pickers ----
    if (typeof flatpickr !== 'undefined') {
        const blockedDates = @json($blockedDates);
        const toYmd = d => d.toLocaleDateString('en-CA', { year: 'numeric', month: '2-digit', day: '2-digit' }).replace(/\//g, '-');

        flatpickr('#pickup_date', {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            allowInput: true,
            disable: [ date => blockedDates.includes(toYmd(date)) ],
            onDayCreate: function (dObj, dStr, fp, dayElem) {
                if (blockedDates.includes(toYmd(dayElem.dateObj))) {
                    dayElem.style.backgroundColor = '#ffebee';
                    dayElem.style.color = '#c62828';
                    dayElem.title = 'This date is not available';
                }
            }
        });

        flatpickr('#pickup_time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'h:i K',
            time_24hr: false,
            minuteIncrement: 15,
            allowInput: true
        });
    }

    // ---- Customer search (same behaviour as New Order) ----
    const nameInput = document.getElementById('customer_name');
    const customerList = document.getElementById('customer_list');
    const newCustomerLink = customerList.querySelector('.new-customer-option');
    const dropdown = bootstrap.Dropdown.getOrCreateInstance(nameInput);

    nameInput.addEventListener('input', function () {
        const text = nameInput.value.toLowerCase();
        document.getElementById('customer_id').value = '';

        customerList.querySelectorAll('.customer-option').forEach(function (opt) {
            const name = (opt.dataset.name || '').toLowerCase();
            const phone = (opt.dataset.phone || '').toLowerCase();
            opt.parentElement.style.display = (name.includes(text) || phone.includes(text)) ? '' : 'none';
        });

        newCustomerLink.innerHTML = text
            ? '<i class="ri-add-line align-middle me-2"></i>Add "' + nameInput.value.replace(/</g, '&lt;') + '" as new customer'
            : '<i class="ri-add-line align-middle me-2"></i>Enter New Customer';

        if (text) dropdown.show();
    });

    customerList.addEventListener('click', function (e) {
        const target = e.target.closest('.dropdown-item');
        if (!target) return;
        e.preventDefault();

        if (target.classList.contains('new-customer-option')) {
            document.getElementById('customer_id').value = '';
        } else {
            document.getElementById('customer_id').value = target.dataset.id;
            nameInput.value = target.dataset.name;
            document.getElementById('customer_email').value = target.dataset.email || '';
            document.getElementById('customer_phone').value = target.dataset.phone || '';
            document.getElementById('customer_address').value = target.dataset.address || '';
        }

        dropdown.hide();
        [nameInput, document.getElementById('customer_phone'), document.getElementById('customer_address')]
            .forEach(el => el.value.trim() && el.classList.remove('is-invalid'));
    });

    // ---- Validation helpers ----
    function validatePane(pane) {
        let firstInvalid = null;
        pane.querySelectorAll('[required]').forEach(function (el) {
            const ok = el.value.trim() !== '' && el.checkValidity();
            el.classList.toggle('is-invalid', !ok);
            if (!ok && !firstInvalid) firstInvalid = el;
        });
        return firstInvalid;
    }

    form.addEventListener('input', e => {
        if (e.target.matches('[required]') && e.target.value.trim() !== '') e.target.classList.remove('is-invalid');
    });
    form.addEventListener('change', e => {
        if (e.target.matches('[required]') && e.target.value.trim() !== '') e.target.classList.remove('is-invalid');
    });

    // ---- Step navigation ----
    document.getElementById('toPickupDetails').addEventListener('click', function () {
        const invalid = validatePane(customerPane);
        if (invalid) { invalid.focus(); return; }

        // Pre-fill pickup address from the customer address, once.
        const pickupAddress = document.getElementById('pickup_address');
        if (!pickupAddress.value.trim()) {
            pickupAddress.value = document.getElementById('customer_address').value;
        }
        pickupTabBtn.click();
    });

    document.getElementById('backToCustomer').addEventListener('click', () => customerTabBtn.click());

    // ---- Items ----
    const itemsWrap = document.getElementById('pickupItems');
    const template = document.getElementById('pickupItemTemplate');
    let nextIndex = itemsWrap.querySelectorAll('.pickup-item').length;

    document.getElementById('addItem').addEventListener('click', function () {
        const html = template.innerHTML.replace(/__INDEX__/g, nextIndex++);
        itemsWrap.insertAdjacentHTML('beforeend', html);
    });

    itemsWrap.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-item');
        if (!btn) return;
        if (itemsWrap.querySelectorAll('.pickup-item').length <= 1) {
            alert('At least one item is required.');
            return;
        }
        btn.closest('.pickup-item').remove();
    });

    // ---- Submit ----
    form.addEventListener('submit', function (e) {
        const customerInvalid = validatePane(customerPane);
        if (customerInvalid) {
            e.preventDefault();
            customerTabBtn.click();
            customerInvalid.focus();
            return;
        }
        const pickupInvalid = validatePane(pickupPane);
        if (pickupInvalid) {
            e.preventDefault();
            pickupInvalid.focus();
            return;
        }
        // One department per pickup. The server checks this too.
        const departments = new Set();
        itemsWrap.querySelectorAll('.item-product').forEach(function (sel) {
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.value && opt.dataset.department) departments.add(opt.dataset.department);
        });
        if (departments.size > 1) {
            e.preventDefault();
            alert('A pickup can only hold items for one department (' + Array.from(departments).join(' and ')
                + '). Create a separate pickup for each department.');
            return;
        }
        const btn = document.getElementById('savePickupBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line label-icon align-middle fs-16 ms-2"></i>Saving...';
    });

    // After a failed submit, open the tab holding the first error.
    @if($errors->any())
        if (!pickupPane.querySelector('.is-invalid') && customerPane.querySelector('.is-invalid')) {
            customerTabBtn.click();
        } else {
            pickupTabBtn.click();
        }
    @endif
});
</script>
@endsection
