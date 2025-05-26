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
        .customer-dropdown .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .search-customer:focus {
            box-shadow: none;
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
                <h4 class="mb-sm-0">New Order</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('orderhistory') }}">Orders</a></li>
                        <li class="breadcrumb-item active">New Order</li>
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
            <div class="card">
                <div class="card-body checkout-tab">
                    <form action="{{ route('neworder.store') }}" method="POST" id="orderForm" novalidate>
                        @csrf
                        <div class="step-arrow-nav mt-n3 mx-n3 mb-3">
                            <ul class="nav nav-pills nav-justified custom-nav bg-warning bg-opacity-75" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3 text-black active" id="pills-bill-info-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-bill-info" type="button" role="tab"
                                        aria-controls="pills-bill-info" aria-selected="true">
                                        <i class="ri-user-2-line fs-16 p-2 bg-warning text-black rounded-circle align-middle me-2"></i>
                                        Customer Details
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3 text-black" id="pills-bill-address-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-bill-address" type="button" role="tab"
                                        aria-controls="pills-bill-address" aria-selected="false">
                                        <i class="ri-truck-line fs-16 p-2 bg-warning text-black rounded-circle align-middle me-2"></i>
                                        Order Details
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="pills-bill-info" role="tabpanel" aria-labelledby="pills-bill-info-tab">
                                <div>
                                    <h5 class="mb-1">Customer Information</h5>
                                    <p class="text-muted mb-4">Please fill all information below</p>
                                </div>

                                <div>
                                    <div class="row mb-3">
                                        <div class="col-sm-12">
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
                                                            <a class="dropdown-item new-customer-option" href="#" 
                                                                data-id="" data-name="" data-email="" data-phone="" data-address="">
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
                                            @error('customer_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="invalid-feedback">Please enter the customer name</div>
                                            <div class="form-text">Type to search existing customers or enter a new customer name</div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="mb-3">
                                                <label for="customer_email" class="form-label">Email <span class="text-muted">(Optional)</span></label>
                                                <input type="email" class="form-control @error('customer_email') is-invalid @enderror" 
                                                    id="customer_email" name="customer_email" placeholder="Enter email"
                                                    value="{{ old('customer_email') }}">
                                                @error('customer_email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="mb-3">
                                                <label for="customer_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('customer_phone') is-invalid @enderror" 
                                                    id="customer_phone" name="customer_phone" placeholder="Enter phone no."
                                                    value="{{ old('customer_phone') }}" required>
                                                @error('customer_phone')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="invalid-feedback">Please enter a phone number</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="customer_address" class="form-label">Address <span class="text-danger">*</span></label>
                                        <textarea class="form-control @error('customer_address') is-invalid @enderror" 
                                            id="customer_address" name="customer_address" placeholder="Enter address" 
                                            rows="3" required>{{ old('customer_address') }}</textarea>
                                        @error('customer_address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="invalid-feedback">Please enter the customer address</div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mt-3">
                                        <button type="button" class="btn btn-success btn-label right ms-auto nexttab"
                                            data-nexttab="pills-bill-address-tab">
                                            <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                            Next to Order Details
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="pills-bill-address" role="tabpanel" aria-labelledby="pills-bill-address-tab">
                                <div>
                                    <h5 class="mb-1">Order Details</h5>
                                    <p class="text-muted mb-4">Please fill all information below</p>
                                </div>

                                <div class="order-items mb-3">
                                    <div class="order-item p-2 border rounded bg-light shadow-sm mb-3">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="text-primary mb-0"><i class="ri-medicine-bottle-line me-1"></i>Order Item</h6>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-danger shadow-sm delete-item" data-item-id="0">
                                                            <i class="ri-delete-bin-line me-1"></i>Remove
                                                        </button>
                                                    </div>
                                                </div>
                                                <hr class="mt-1 mb-3">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="products[0][type]" class="form-label fw-semibold">Order Type <span class="text-danger">*</span></label>
                                                    <select class="form-select order-type shadow-sm @error('products.0.type') is-invalid @enderror" 
                                                        id="products[0][type]" name="products[0][type]" required>
                                                        <option value="">Select Order Type...</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product->name }}" {{ old('products.0.type') == $product->name ? 'selected' : '' }}>
                                                                {{ $product->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('products.0.type')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <div class="invalid-feedback">Please select an order type</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="products[0][patient_name]" class="form-label fw-semibold">Patient Name</label>
                                                    <input type="text" class="form-control @error('products.0.patient_name') is-invalid @enderror" 
                                                        id="products[0][patient_name]" name="products[0][patient_name]" 
                                                        placeholder="Enter patient name" value="{{ old('products.0.patient_name') }}">
                                                    @error('products.0.patient_name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label for="products[0][quantity]" class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control @error('products.0.quantity') is-invalid @enderror" 
                                                        id="products[0][quantity]" name="products[0][quantity]" 
                                                        value="{{ old('products.0.quantity', 1) }}" min="1" max="100"
                                                        onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                                                    @error('products.0.quantity')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <div class="invalid-feedback">Please enter a quantity</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-0">
                                                    <label for="products[0][remarks]" class="form-label fw-semibold">Remarks</label>
                                                    <textarea class="form-control @error('products.0.remarks') is-invalid @enderror" 
                                                        id="products[0][remarks]" name="products[0][remarks]" 
                                                        placeholder="Enter any remarks for this order item" rows="2">{{ old('products.0.remarks') }}</textarea>
                                                    @error('products.0.remarks')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <button type="button" class="btn btn-info btn-sm shadow-sm" id="add-more-items">
                                        <i class="ri-add-line align-middle me-1"></i> Add More Items
                                    </button>
                                </div>

                                <input type="hidden" name="order_placed_by" value="{{ Auth::user()->username }}">
                                
                                <div class="row mb-3">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label d-block">Delivery Type <span class="text-danger">*</span></label>
                                            <div class="form-check-inline">
                                                <input class="form-check-input" 
                                                    type="radio" 
                                                    name="delivery_type" 
                                                    id="delivery_type_delivery" 
                                                    value="delivery" 
                                                    {{ old('delivery_type', 'delivery') == 'delivery' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="delivery_type_delivery">
                                                    <i class="ri-truck-line me-1"></i>Delivery
                                                </label>
                                            </div>
                                            <div class="form-check-inline ms-3">
                                                <input class="form-check-input" 
                                                    type="radio" 
                                                    name="delivery_type" 
                                                    id="delivery_type_self" 
                                                    value="self_collect"
                                                    {{ old('delivery_type') == 'self_collect' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="delivery_type_self">
                                                    <i class="ri-user-location-line me-1"></i>Self Collect
                                                </label>
                                            </div>
                                            @error('delivery_type')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="pickup_delivery_date" class="form-label"><span id="date_label">Delivery</span> Date <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('pickup_delivery_date') is-invalid @enderror" 
                                                id="pickup_delivery_date" name="pickup_delivery_date" 
                                                data-provider="flatpickr" data-date-format="Y-m-d" 
                                                value="{{ old('pickup_delivery_date') }}" required>
                                            @error('pickup_delivery_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="invalid-feedback">Please select a date</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="pickup_delivery_time" class="form-label"><span id="time_label">Delivery</span> Time <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('pickup_delivery_time') is-invalid @enderror" 
                                                id="pickup_delivery_time" name="pickup_delivery_time" 
                                                data-provider="timepickr" 
                                                placeholder="Select time"
                                                value="{{ old('pickup_delivery_time') }}" required>
                                            @error('pickup_delivery_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="invalid-feedback">Please select a time</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="remarks" class="form-label">General Remarks</label>
                                    <textarea class="form-control @error('remarks') is-invalid @enderror" 
                                        id="remarks" name="remarks" placeholder="Enter any general remarks for the entire order" 
                                        rows="3">{{ old('remarks') }}</textarea>
                                    @error('remarks')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex align-items-start gap-3 mt-3">
                                    <button type="button" class="btn btn-light btn-label previestab"
                                        data-previous="pills-bill-info-tab">
                                        <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                        Back to Customer Details
                                    </button>
                                    <button type="submit" class="btn btn-success btn-label right ms-auto">
                                        <i class="ri-save-line label-icon align-middle fs-16 ms-2"></i>
                                        Save Order
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- end tab content -->
                    </form>
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->

    <!-- Minimal JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date and time pickers
            if (typeof flatpickr !== 'undefined') {
                flatpickr("#pickup_delivery_date", {
                    dateFormat: "Y-m-d",
                    minDate: "today",
                    allowInput: true
                });
                
                flatpickr("#pickup_delivery_time", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "h:i K",
                    time_24hr: false,
                    minuteIncrement: 15,
                    allowInput: true
                });
            }
            
            // Customer search functionality
            const customerSearchInput = document.getElementById('customer_name');
            const customerList = document.getElementById('customer_list');
            
            // Initialize Bootstrap dropdown
            const dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
            
            // Filter customers in dropdown as user types
            customerSearchInput.addEventListener('input', function(e) {
                const searchText = e.target.value.toLowerCase();
                const customerItems = customerList.querySelectorAll('li');
                let resultsFound = false;
                
                // Always show "Enter New Customer" option
                const newCustomerOption = document.querySelector('.new-customer-option');
                if (newCustomerOption) {
                    newCustomerOption.setAttribute('data-name', searchText);
                }
                
                customerItems.forEach(function(item) {
                    if (item.querySelector('.new-customer-option')) {
                        item.style.display = 'block';
                        return;
                    }
                    
                    const customerOption = item.querySelector('.customer-option');
                    const customerName = customerOption.getAttribute('data-name').toLowerCase();
                    const customerPhone = customerOption.getAttribute('data-phone') ? 
                        customerOption.getAttribute('data-phone').toLowerCase() : '';
                    
                    const shouldDisplay = (customerName.includes(searchText) || customerPhone.includes(searchText));
                    item.style.display = shouldDisplay ? 'block' : 'none';
                    
                    if (shouldDisplay) resultsFound = true;
                });
                
                // Automatically show dropdown when typing
                if (searchText.length > 0) {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(customerSearchInput);
                    if (!dropdownInstance._isShown()) {
                        dropdownInstance.show();
                    }
                }
                
                // Update "Enter New Customer" option text
                if (searchText.length > 0) {
                    const newCustomerLink = document.querySelector('.new-customer-option');
                    if (newCustomerLink) {
                        newCustomerLink.innerHTML = `<i class="ri-add-line align-middle me-2"></i>Add "${searchText}" as new customer`;
                    }
                } else {
                    const newCustomerLink = document.querySelector('.new-customer-option');
                    if (newCustomerLink) {
                        newCustomerLink.innerHTML = `<i class="ri-add-line align-middle me-2"></i>Enter New Customer`;
                    }
                }
                
                // Add no results message if needed
                const noResultsMsg = document.getElementById('no-results-msg');
                if (!resultsFound && searchText.length > 0) {
                    if (!noResultsMsg) {
                        const msgItem = document.createElement('li');
                        msgItem.id = 'no-results-msg';
                        msgItem.className = 'px-3 py-2 text-center text-muted small';
                        msgItem.textContent = 'No matches found';
                        customerList.appendChild(msgItem);
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.remove();
                }
                
                // Validate the field since it's required
                validateField(customerSearchInput);
            });
            
            // Handle customer selection from dropdown
            customerList.addEventListener('click', function(e) {
                e.preventDefault();
                const target = e.target.closest('.dropdown-item');
                if (!target) return;
                
                let customerId = target.getAttribute('data-id');
                let customerName = target.getAttribute('data-name');
                const customerEmail = target.getAttribute('data-email');
                const customerPhone = target.getAttribute('data-phone');
                const customerAddress = target.getAttribute('data-address');
                
                // If new customer option was clicked, use the search input value as the name
                if (target.classList.contains('new-customer-option')) {
                    customerName = customerSearchInput.value;
                    document.getElementById('customer_id').value = '';
                } else {
                    document.getElementById('customer_id').value = customerId;
                }
                
                // Update name field (which is now the same as the search field)
                customerSearchInput.value = customerName;
                
                // If using an existing customer, populate email, phone and address fields
                document.getElementById('customer_email').value = customerEmail || '';
                document.getElementById('customer_phone').value = customerPhone || '';
                document.getElementById('customer_address').value = customerAddress || '';
                
                // Validate fields
                validateField(customerSearchInput);
                if (customerPhone) validateField(document.getElementById('customer_phone'));
                if (customerAddress) validateField(document.getElementById('customer_address'));
                
                // Close dropdown
                const dropdownInstance = bootstrap.Dropdown.getInstance(customerSearchInput);
                if (dropdownInstance) {
                    dropdownInstance.hide();
                }
            });
            
            // Tab navigation
            document.querySelector('.nexttab').addEventListener('click', function() {
                const customerTab = document.getElementById('pills-bill-info');
                const requiredFields = customerTab.querySelectorAll('[required]');
                let errors = [];
                
                requiredFields.forEach(function(field) {
                    if (!validateField(field)) {
                        let fieldName = field.getAttribute('placeholder') || 
                                       field.previousElementSibling?.textContent || 
                                       'Field';
                        fieldName = fieldName.replace(/\*|\(.*?\)/g, '').trim();
                        errors.push(`${fieldName} is required.`);
                    }
                });
                
                if (errors.length === 0) {
                    const nextTabId = this.getAttribute('data-nexttab');
                    document.getElementById(nextTabId).click();
                } else {
                    showValidationAlert(errors);
                }
            });
            
            document.querySelectorAll('.previestab').forEach(function(button) {
                button.addEventListener('click', function() {
                    const prevTabId = this.getAttribute('data-previous');
                    document.getElementById(prevTabId).click();
                });
            });
            
            // Add more items functionality
            let itemCount = 0;
            document.getElementById('add-more-items').addEventListener('click', function() {
                itemCount++;
                const newItem = document.createElement('div');
                newItem.className = 'order-item p-2 border rounded bg-light shadow-sm mb-3';
                newItem.innerHTML = `
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-primary mb-0"><i class="ri-medicine-bottle-line me-1"></i>Order Item</h6>
                                <div>
                                    <button type="button" class="btn btn-sm btn-danger shadow-sm delete-item" data-item-id="${itemCount}">
                                        <i class="ri-delete-bin-line me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                            <hr class="mt-1 mb-3">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="products[${itemCount}][type]" class="form-label fw-semibold">Order Type <span class="text-danger">*</span></label>
                                <select class="form-select order-type shadow-sm" id="products[${itemCount}][type]" name="products[${itemCount}][type]" required>
                                    <option value="">Select Order Type...</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->name }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Please select an order type</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="products[${itemCount}][patient_name]" class="form-label fw-semibold">Patient Name</label>
                                <input type="text" class="form-control" id="products[${itemCount}][patient_name]" name="products[${itemCount}][patient_name]" placeholder="Enter patient name">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="products[${itemCount}][quantity]" class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="products[${itemCount}][quantity]" name="products[${itemCount}][quantity]" value="1" min="1" max="100" onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                                <div class="invalid-feedback">Please enter a quantity</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-0">
                                <label for="products[${itemCount}][remarks]" class="form-label fw-semibold">Remarks</label>
                                <textarea class="form-control" id="products[${itemCount}][remarks]" name="products[${itemCount}][remarks]" placeholder="Enter any remarks for this order item" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                `;
                document.querySelector('.order-items').appendChild(newItem);
                
                const newSelect = newItem.querySelector('select');
                const newQuantity = newItem.querySelector('input[type="number"]');
                
                newSelect.addEventListener('change', function() {
                    validateField(this);
                });
                
                newQuantity.addEventListener('change', function() {
                    validateField(this);
                });
            });
            
            // Delete item functionality
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-item')) {
                    const itemElement = e.target.closest('.order-item');
                    if (document.querySelectorAll('.order-item').length > 1) {
                        itemElement.remove();
                    } else {
                        showValidationAlert('You need at least one product item. Cannot remove the last product.');
                        const productSection = itemElement.querySelector('select');
                        if (productSection) {
                            productSection.focus();
                        }
                    }
                }
            });
            
            // Field validation
            function validateField(field) {
                if (!field.checkValidity()) {
                    field.classList.add('is-invalid');
                    return false;
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                    return true;
                }
            }
            
            // Show validation alert
            function showValidationAlert(message) {
                const existingAlerts = document.querySelectorAll('.validation-alert');
                existingAlerts.forEach(function(alert) {
                    alert.remove();
                });
                
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-border-left alert-dismissible fade show validation-alert';
                
                if (Array.isArray(message)) {
                    let messageHtml = `
                        <i class="ri-error-warning-line me-3 align-middle fs-16"></i>
                        <strong>Validation Error!</strong> Please check the form and try again.
                        <ul class="mb-0 my-5">
                    `;
                    
                    message.forEach(item => {
                        messageHtml += `<li>${item}</li>`;
                    });
                    
                    messageHtml += '</ul>';
                    alertDiv.innerHTML = messageHtml;
                } else {
                    alertDiv.innerHTML = `
                        <i class="ri-error-warning-line me-3 align-middle fs-16"></i>
                        <strong>Validation Error!</strong> ${message}
                    `;
                }
                
                const closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'btn-close';
                closeButton.setAttribute('data-bs-dismiss', 'alert');
                closeButton.setAttribute('aria-label', 'Close');
                alertDiv.appendChild(closeButton);
                
                const cardRow = document.querySelector('.card').closest('.row');
                cardRow.parentNode.insertBefore(alertDiv, cardRow);
                
                window.scrollTo({
                    top: alertDiv.offsetTop - 20,
                    behavior: 'smooth'
                });
            }
            
            // Add validation listeners to all required fields
            document.querySelectorAll('[required]').forEach(function(field) {
                field.addEventListener('blur', function() {
                    validateField(this);
                });
                
                field.addEventListener('change', function() {
                    validateField(this);
                });
            });
            
            // Form validation on submit
            const form = document.getElementById('orderForm');
            
            // Handle delivery type changes
            const deliveryTypeInputs = document.querySelectorAll('input[name="delivery_type"]');
            const dateLabel = document.getElementById('date_label');
            const timeLabel = document.getElementById('time_label');
            
            deliveryTypeInputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    const isDelivery = this.value === 'delivery';
                    dateLabel.textContent = isDelivery ? 'Delivery' : 'Self Collect';
                    timeLabel.textContent = isDelivery ? 'Delivery' : 'Self Collect';
                });
            });
            
            form.addEventListener('submit', function(event) {
                let errors = [];
                
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(function(field) {
                    if (!validateField(field)) {
                        let fieldName = field.getAttribute('placeholder') || 
                                       field.previousElementSibling?.textContent || 
                                       'Field';
                        fieldName = fieldName.replace(/\*|\(.*?\)/g, '').trim();
                        errors.push(`${fieldName} is required.`);
                    }
                });
                
                const productItems = document.querySelectorAll('.order-item');
                if (productItems.length === 0) {
                    errors.push('You need at least one product item.');
                } else {
                    productItems.forEach(function(item, index) {
                        const productType = item.querySelector('select');
                        const productQuantity = item.querySelector('input[type="number"]');
                        
                        if (!productType.value) {
                            errors.push(`Product #${index + 1}: Type is required.`);
                        }
                        
                        if (!productQuantity.value || productQuantity.value < 1) {
                            errors.push(`Product #${index + 1}: Quantity must be at least 1.`);
                        }
                    });
                }
                
                if (errors.length > 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    showValidationAlert(errors);
                    form.classList.add('was-validated');
                }
            });
        });
    </script>
@endsection
