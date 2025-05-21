@extends('layouts.master')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Edit Customer</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

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
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Customer Information</h5>
                        <div class="flex-shrink-0">
                            <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-info">
                                <i class="ri-eye-line me-1 align-bottom"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('customers.update', $customer->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                    id="name" name="name" value="{{ old('name', $customer->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-muted">(Optional)</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                    id="email" name="email" value="{{ old('email', $customer->email) }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phoneNo" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('phoneNo') is-invalid @enderror" 
                                    id="phoneNo" name="phoneNo" value="{{ old('phoneNo', $customer->phoneNo) }}" required>
                                @error('phoneNo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select @error('gender') is-invalid @enderror" 
                                    id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender', $customer->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $customer->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="birthdate" class="form-label">Birthdate</label>
                                @php
                                    $birthdate = $customer->birthdate ? \Carbon\Carbon::parse($customer->birthdate)->format('Y-m-d') : '';
                                @endphp
                                <input type="text" 
                                    class="form-control @error('birthdate') is-invalid @enderror" 
                                    id="birthdate" 
                                    name="birthdate" 
                                    data-provider="flatpickr" 
                                    data-date-format="Y-m-d"
                                    value="{{ old('birthdate', $birthdate) }}"
                                    data-default-date="{{ $birthdate }}"
                                    placeholder="Select birthdate">
                                @error('birthdate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="user_search_input" class="form-label">Person in Charge</label>
                                <div class="dropdown user-dropdown">
                                    <input type="text" class="form-control" id="user_search_input" 
                                        placeholder="Search for person in charge" 
                                        data-bs-toggle="dropdown" aria-expanded="false"
                                        value="{{ $customer->userID ? ($users->where('id', $customer->userID)->first()->username ?? '') : '' }}" />
                                    <input type="hidden" id="userID" name="userID" value="{{ old('userID', $customer->userID) }}">
                                    <div class="dropdown-menu w-100">
                                        <div class="p-2 px-3 pt-1 searchlist-input">
                                            <input type="text" class="form-control form-control-sm border search-user" 
                                                placeholder="Type to search users..." />
                                        </div>
                                        <ul class="list-unstyled dropdown-menu-list mb-0" id="user_list">
                                            <li>
                                                <a class="dropdown-item clear-user-option" href="#" data-id="">
                                                    <i class="ri-close-line align-middle me-2"></i>Clear Selection
                                                </a>
                                            </li>
                                            @foreach($users as $user)
                                                <li>
                                                    <a class="dropdown-item user-option" href="#" 
                                                        data-id="{{ $user->id }}"
                                                        data-username="{{ $user->username }}">
                                                        <span class="fw-medium">{{ $user->username }}</span> 
                                                        <small class="text-muted">(ID: {{ $user->id }})</small>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                @error('userID')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                id="address" name="address" rows="3" required>{{ old('address', $customer->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="text-end">
                            <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-light me-1">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-1 align-bottom"></i> Update Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .user-dropdown .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
            width: 100%;
        }
        .user-dropdown .dropdown-item {
            padding: 0.5rem 1rem;
            white-space: normal;
            cursor: pointer;
        }
        .user-dropdown .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .search-user:focus {
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
        .clear-user-option {
            background-color: #f8f9fa;
            font-weight: 500;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Flatpickr for birthdate
            if (typeof flatpickr !== 'undefined') {
                const birthdateInput = document.getElementById('birthdate');
                const defaultDate = birthdateInput.getAttribute('data-default-date');
                
                const fp = flatpickr("#birthdate", {
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    altInput: true,
                    altFormat: "F j, Y",
                    maxDate: "today",
                    defaultDate: defaultDate || undefined,
                    onChange: function(selectedDates, dateStr) {
                        // Update both the visible and hidden inputs
                        birthdateInput.value = dateStr;
                    }
                });

                // If there's a default date, set it explicitly
                if (defaultDate) {
                    fp.setDate(defaultDate);
                }
            }

            // User search dropdown functionality
            const userSearchInput = document.getElementById('user_search_input');
            const userList = document.getElementById('user_list');
            const searchUserInput = document.querySelector('.search-user');
            
            // Initialize Bootstrap dropdown
            const dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
            
            // Filter users in dropdown
            searchUserInput.addEventListener('input', function(e) {
                const searchText = e.target.value.toLowerCase();
                const userItems = userList.querySelectorAll('li');
                let hasVisibleItems = false;
                
                userItems.forEach(function(item) {
                    // Always show "Clear Selection" option
                    if (item.querySelector('.clear-user-option')) {
                        item.style.display = 'block';
                        return;
                    }
                    
                    const userOption = item.querySelector('.user-option');
                    const username = userOption.getAttribute('data-username').toLowerCase();
                    const userId = userOption.getAttribute('data-id');
                    
                    if (username.includes(searchText) || userId.includes(searchText)) {
                        item.style.display = 'block';
                        hasVisibleItems = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Prevent dropdown from closing during search
                e.stopPropagation();
            });
            
            // Handle user selection
            userList.addEventListener('click', function(e) {
                e.preventDefault();
                const target = e.target.closest('.dropdown-item');
                if (!target) return;
                
                const userId = target.getAttribute('data-id');
                const username = target.getAttribute('data-username') || '';
                
                // Update hidden input with user ID
                document.getElementById('userID').value = userId;
                
                // Update search input display
                userSearchInput.value = username;
                
                // Close dropdown manually
                const dropdownInstance = bootstrap.Dropdown.getInstance(userSearchInput);
                if (dropdownInstance) {
                    dropdownInstance.hide();
                }
            });
            
            // Prevent dropdown from closing when clicking in the search input
            document.querySelector('.searchlist-input').addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
@endsection 