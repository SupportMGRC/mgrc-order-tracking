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
            <div class="card" id="customerListCustom">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Customers</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('customers.create') }}" class="btn btn-success add-btn">
                                    <i class="ri-add-line align-bottom me-1"></i> Add Customer
                                </a>
                                <button type="button" class="btn btn-secondary">
                                    <i class="ri-file-download-line align-bottom me-1"></i> Import
                                </button>
                                <button class="btn btn-soft-danger" id="remove-actions">
                                    <i class="ri-delete-bin-2-line"></i>
                                </button>
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
                            <div class="col-xxl-2 col-sm-6">
                                <div>
                                    <input type="text" class="form-control" data-provider="flatpickr"
                                        data-date-format="d M, Y" data-range-date="true" name="date_range"
                                        id="demo-datepicker" placeholder="Select date" value="{{ request('date_range') }}">
                                </div>
                            </div>
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
                            <div class="col-xxl-1 col-sm-4">
                                <div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="ri-equalizer-fill me-1 align-bottom"></i>
                                        Filter
                                    </button>
                                </div>
                            </div>
                        </div>
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
                                                <a href="{{ route('customers.show', $customer->id) }}" class="text-info d-inline-block">
                                                    <i class="ri-eye-fill fs-16"></i>
                                                </a>
                                            </li>
                                            {{-- <li class="list-inline-item edit" data-bs-toggle="tooltip"
                                                data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                                <a href="{{ route('customers.edit', $customer->id) }}" class="text-primary d-inline-block">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li> --}}
                                            <li class="list-inline-item" data-bs-toggle="tooltip"
                                                data-bs-trigger="hover" data-bs-placement="top" title="Remove">
                                                <a href="javascript:void(0);" class="text-danger d-inline-block remove-item-btn"
                                                   onclick="if(confirm('Are you sure you want to delete this customer?')) { document.getElementById('delete-form-{{ $customer->id }}').submit(); }">
                                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                                </a>
                                                <form id="delete-form-{{ $customer->id }}" action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No customers found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center justify-content-sm-end">
                        <div class="pagination-wrap hstack gap-2">
                            {{ $customers->links('vendor.pagination.bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Add any necessary JavaScript for the customer listing page
    document.addEventListener('DOMContentLoaded', function() {
        // Handle bulk delete
        const removeActionsBtn = document.getElementById('remove-actions');
        const checkAll = document.getElementById('checkAll');
        const checkboxes = document.querySelectorAll('input[name="checkAll"]');

        checkAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        removeActionsBtn.addEventListener('click', function() {
            const selectedIds = Array.from(checkboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.closest('tr').querySelector('.id').textContent);

            if (selectedIds.length === 0) {
                alert('Please select at least one customer to delete');
                return;
            }

            if (confirm('Are you sure you want to delete the selected customers?')) {
                // Implement bulk delete functionality here
                // You'll need to create a new route and controller method for this
            }
        });
    });
</script>
@endpush 