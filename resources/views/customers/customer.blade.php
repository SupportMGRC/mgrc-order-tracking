@extends('layouts.master')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Customer Management</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Menu</a></li>
                        <li class="breadcrumb-item active">Customer Management</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->

<!-- Flash Messages -->
<div class="row">
    <div class="col-12">
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
        
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card" id="customerList">
            <div class="card-header border-0">
                <div class="row align-items-center gy-3">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">Customers</h5>
                    </div>
                    <div class="col-sm-auto">
                        <div class="d-flex gap-1 flex-wrap">
                            <button type="button" class="btn btn-success add-btn" data-bs-toggle="modal"
                                id="create-btn" data-bs-target="#addCustomerModal">
                                <i class="ri-add-line align-bottom me-1"></i> Add Customer
                            </button>
                            <button type="button" class="btn btn-secondary"><i
                                    class="ri-file-download-line align-bottom me-1"></i> Import</button>
                            <button class="btn btn-soft-danger" id="remove-actions"><i
                                    class="ri-delete-bin-2-line"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border border-dashed border-end-0 border-start-0">
                <form action="{{ route('customers.index') }}" method="GET">
                    <div class="row g-3 mb-3">
                        <div class="col-xxl-5 col-sm-6">
                            <div class="search-box">
                                <input type="text" class="form-control" name="search"
                                    placeholder="Search for customer name, email or phone..."
                                    value="{{ request('search') }}">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <!--end col-->
                        <div class="col-xxl-2 col-sm-6">
                            <div>
                                <input type="text" class="form-control" data-provider="flatpickr"
                                    data-date-format="d M, Y" data-range-date="true" name="date_range"
                                    id="demo-datepicker" placeholder="Select date" value="{{ request('date_range') }}">
                            </div>
                        </div>
                        <!--end col-->
                        <div class="col-xxl-2 col-sm-4">
                            <div>
                                <select class="form-control" data-choices data-choices-search-false name="gender"
                                    id="idGender">
                                    <option value="all"
                                        {{ request('gender') == 'all' || !request('gender') ? 'selected' : '' }}>All
                                    </option>
                                    <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>Male
                                    </option>
                                    <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>
                                        Female</option>
                                </select>
                            </div>
                        </div>
                        <!--end col-->
                        <div class="col-xxl-1 col-sm-4">
                            <div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ri-equalizer-fill me-1 align-bottom"></i>
                                    Filter
                                </button>
                            </div>
                        </div>
                        <!--end col-->
                    </div>
                    <!--end row-->
                </form>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive table-card mb-1">
                    <table class="table table-nowrap align-middle" id="customerTable">
                        <thead class="text-muted table-light">
                            <tr class="text-uppercase">
                                <th scope="col" style="width: 25px;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="checkAll" value="option">
                                    </div>
                                </th>
                                <th class="sort" data-sort="id">ID</th>
                                <th class="sort" data-sort="name">Name</th>
                                <th class="sort" data-sort="email">Email</th>
                                <th class="sort" data-sort="phone">Phone</th>
                                <th class="sort" data-sort="address">Address</th>
                                <th class="sort" data-sort="action">Action</th>
                            </tr>
                        </thead>
                        <tbody class="list form-check-all">
                            @forelse($customers as $customer)
                            <tr>
                                <th scope="row">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="checkAll" value="option1">
                                    </div>
                                </th>
                                <td class="id">{{ $customer->id }}</td>
                                <td class="name">{{ $customer->name }}</td>
                                <td class="email">{{ $customer->email }}</td>
                                <td class="phone">{{ $customer->phoneNo }}</td>
                                <td class="address">{{ $customer->address }}</td>
                                <td>
                                    <ul class="list-inline hstack gap-2 mb-0">
                                        <li class="list-inline-item" data-bs-toggle="tooltip"
                                            data-bs-trigger="hover" data-bs-placement="top" title="View">
                                            <a href="javascript:void(0);" class="text-info d-inline-block view-item-btn"
                                               data-bs-toggle="modal" data-bs-target="#viewCustomerModal{{ $customer->id }}">
                                                <i class="ri-eye-fill fs-16"></i>
                                            </a>
                                        </li>
                                        {{-- <li class="list-inline-item edit" data-bs-toggle="tooltip"
                                            data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                            <a href="javascript:void(0);" class="text-primary d-inline-block edit-item-btn"
                                               data-bs-toggle="modal" data-bs-target="#editCustomerModal{{ $customer->id }}">
                                                <i class="ri-pencil-fill fs-16"></i>
                                            </a>
                                        </li> --}}
                                        <li class="list-inline-item" data-bs-toggle="tooltip"
                                            data-bs-trigger="hover" data-bs-placement="top" title="Remove">
                                            <a class="text-danger d-inline-block remove-item-btn"
                                               data-bs-toggle="modal" data-bs-target="#deleteCustomerModal{{ $customer->id }}">
                                                <i class="ri-delete-bin-5-fill fs-16"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                            
                            <!-- Edit Customer Modal -->
                            <div class="modal fade" id="editCustomerModal{{ $customer->id }}" tabindex="-1" aria-labelledby="editCustomerModalLabel{{ $customer->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-light p-3">
                                            <h5 class="modal-title" id="editCustomerModalLabel{{ $customer->id }}">Edit Customer</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('customers.update', $customer->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="edit-name-{{ $customer->id }}" class="form-label">Name</label>
                                                    <input type="text" class="form-control" id="edit-name-{{ $customer->id }}" name="name" value="{{ $customer->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit-email-{{ $customer->id }}" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="edit-email-{{ $customer->id }}" name="email" value="{{ $customer->email }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit-phone-{{ $customer->id }}" class="form-label">Phone Number</label>
                                                    <input type="text" class="form-control" id="edit-phone-{{ $customer->id }}" name="phoneNo" value="{{ $customer->phoneNo }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit-gender-{{ $customer->id }}" class="form-label">Gender</label>
                                                    <select class="form-control" id="edit-gender-{{ $customer->id }}" name="gender">
                                                        <option value="">Select Gender</option>
                                                        <option value="male" {{ $customer->gender == 'male' ? 'selected' : '' }}>Male</option>
                                                        <option value="female" {{ $customer->gender == 'female' ? 'selected' : '' }}>Female</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit-birthdate-{{ $customer->id }}" class="form-label">Birthdate</label>
                                                    <input type="date" class="form-control" id="edit-birthdate-{{ $customer->id }}" name="birthdate" value="{{ $customer->birthdate }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit-address-{{ $customer->id }}" class="form-label">Address</label>
                                                    <textarea class="form-control" id="edit-address-{{ $customer->id }}" name="address" rows="3">{{ $customer->address }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <div class="hstack gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Update Customer</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- End Edit Customer Modal -->
                            
                            <!-- Delete Customer Modal -->
                            <div class="modal fade flip" id="deleteCustomerModal{{ $customer->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-body p-5 text-center">
                                            <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                                                colors="primary:#405189,secondary:#f06548"
                                                style="width:90px;height:90px"></lord-icon>
                                            <div class="mt-4 text-center">
                                                <h4>You are about to delete this customer?</h4>
                                                <p class="text-muted fs-15 mb-4">Deleting your customer will remove
                                                    all of their information from our database.</p>
                                                <div class="hstack gap-2 justify-content-center remove">
                                                    <button class="btn btn-link link-success fw-medium text-decoration-none"
                                                        id="deleteRecord-close" data-bs-dismiss="modal"><i
                                                            class="ri-close-line me-1 align-middle"></i>
                                                        Close</button>
                                                    <form action="{{ route('customers.destroy', $customer->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger" id="delete-record">Yes,
                                                            Delete It</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Delete Customer Modal -->
                            
                            <!-- View Customer Modal -->
                            <div class="modal fade" id="viewCustomerModal{{ $customer->id }}" tabindex="-1" aria-labelledby="viewCustomerModalLabel{{ $customer->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-light p-3">
                                            <h5 class="modal-title" id="viewCustomerModalLabel{{ $customer->id }}">Customer Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0">
                                                            <div class="avatar-lg">
                                                                <div class="avatar-title bg-soft-primary text-primary rounded-circle fs-1">
                                                                    {{ substr($customer->name, 0, 1) }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h4 class="mb-1">{{ $customer->name }}</h4>
                                                            <p class="text-muted mb-0">
                                                                <i class="ri-mail-line me-1"></i> {{ $customer->email ?? 'No email provided' }}
                                                            </p>
                                                            <p class="text-muted mb-0">
                                                                <i class="ri-phone-line me-1"></i> {{ $customer->phoneNo ?? 'No phone provided' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 d-flex justify-content-end align-items-start">
                                                    <button type="button" class="btn btn-primary btn-sm edit-from-view" 
                                                        data-customer-id="{{ $customer->id }}">
                                                        <i class="ri-pencil-line me-1"></i> Edit Customer
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <h5 class="text-muted fw-medium mb-2">Personal Information</h5>
                                                        <div class="table-responsive">
                                                            <table class="table table-borderless mb-0">
                                                                <tbody>
                                                                    <tr>
                                                                        <th scope="row" width="200">Full Name</th>
                                                                        <td>{{ $customer->name }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row">Gender</th>
                                                                        <td>{{ $customer->gender ?? 'Not specified' }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row">Birthdate</th>
                                                                        <td>
                                                                            @if($customer->birthdate)
                                                                                @if(is_string($customer->birthdate))
                                                                                    {{ \Carbon\Carbon::parse($customer->birthdate)->format('d M, Y') }}
                                                                                @else
                                                                                    {{ $customer->birthdate->format('d M, Y') }}
                                                                                @endif
                                                                            @else
                                                                                Not specified
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row">Email</th>
                                                                        <td>{{ $customer->email ?? 'Not provided' }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row">Phone</th>
                                                                        <td>{{ $customer->phoneNo ?? 'Not provided' }}</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <h5 class="text-muted fw-medium mb-2">Address & Additional Info</h5>
                                                        <div class="table-responsive">
                                                            <table class="table table-borderless mb-0">
                                                                <tbody>
                                                                    <tr>
                                                                        <th scope="row" width="200">Address</th>
                                                                        <td>{{ $customer->address ?? 'Not provided' }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row">Assigned To</th>
                                                                        <td>
                                                                            @if($customer->user)
                                                                                @if($customer->user->first_name && $customer->user->last_name)
                                                                                    {{ $customer->user->first_name . ' ' . $customer->user->last_name }}
                                                                                @else
                                                                                    {{ $customer->user->username ?? $customer->user->email ?? 'User #' . $customer->user->id }}
                                                                                @endif
                                                                            @else
                                                                                Not assigned
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row">Created On</th>
                                                                        <td>{{ $customer->created_at->format('d M, Y') }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row">Last Updated</th>
                                                                        <td>{{ $customer->updated_at->format('d M, Y') }}</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            @if($customer->orders && $customer->orders->count() > 0)
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <h5 class="text-muted fw-medium mb-2">Order History</h5>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-striped mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Order ID</th>
                                                                    <th>Date</th>
                                                                    <th>Status</th>
                                                                    <th>Items</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @forelse($customer->orders->take(5) as $order)
                                                                <tr>
                                                                    <td>#{{ $order->id }}</td>
                                                                    <td>
                                                                        @if($order->order_date)
                                                                            {{ $order->order_date->format('d M, Y') }}
                                                                        @else
                                                                            N/A
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if($order->status)
                                                                            <span class="badge bg-{{ $order->status == 'completed' ? 'success' : ($order->status == 'pending' ? 'warning' : 'info') }}">
                                                                                {{ ucfirst($order->status) }}
                                                                            </span>
                                                                        @else
                                                                            <span class="badge bg-secondary">Unknown</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if($order->products)
                                                                            {{ $order->products->count() }} item(s)
                                                                        @else
                                                                            0 items
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                @empty
                                                                <tr>
                                                                    <td colspan="4" class="text-center">No order history available</td>
                                                                </tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End View Customer Modal -->
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No customers found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="noresult" style="display: none">
                        <div class="text-center">
                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                colors="primary:#405189,secondary:#0ab39c" style="width:75px;height:75px">
                            </lord-icon>
                            <h5 class="mt-2">Sorry! No Result Found</h5>
                            <p class="text-muted">We've searched more than 150+ Customers, We did
                                not find any customers for you search.</p>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <div class="pagination-wrap hstack gap-2">
                        {{ $customers->links('vendor.pagination.bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                    </div>
                    <div class="mb-3">
                        <label for="phoneNo" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phoneNo" name="phoneNo" value="{{ old('phoneNo') }}">
                    </div>
                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-control" id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="birthdate" class="form-label">Birthdate</label>
                        <input type="date" class="form-control" id="birthdate" name="birthdate" value="{{ old('birthdate') }}">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3">{{ old('address') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="hstack gap-2 justify-content-end">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="add-btn">Add Customer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Add Customer Modal -->

@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle checkAll checkbox
        const checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('input[name="checkAll"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        }

        // Initialize Flatpickr for date picker
        if (typeof flatpickr !== 'undefined') {
            flatpickr("#demo-datepicker", {
                mode: "range",
                dateFormat: "d M, Y",
            });
        }
        
        // Fix modal issues
        $('.view-item-btn').on('click', function(e) {
            e.preventDefault();
        });
        
        $('.edit-item-btn').on('click', function(e) {
            e.preventDefault();
        });
        
        // Handle edit button in view modal
        $('.edit-from-view').on('click', function() {
            const customerId = $(this).data('customer-id');
            $(`#viewCustomerModal${customerId}`).modal('hide');
            setTimeout(() => {
                $(`#editCustomerModal${customerId}`).modal('show');
            }, 500);
        });
    });
</script>
@endsection
