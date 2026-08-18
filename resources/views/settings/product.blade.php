@extends('layouts.master')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Product Management</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Menu</a></li>
                        <li class="breadcrumb-item active">Product Management</li>
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
        <div class="card" id="productList">
            <div class="card-header border-0">
                <div class="row align-items-center gy-3">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">Products</h5>
                    </div>
                    <div class="col-sm-auto">
                        <div class="d-flex gap-1 flex-wrap">
                            <button type="button" class="btn btn-success add-btn" data-bs-toggle="modal"
                                id="create-btn" data-bs-target="#addProductModal">
                                <i class="ri-add-line align-bottom me-1"></i> Add Product
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
                <form action="{{ route('products.index') }}" method="GET">
                    <div class="row g-3 mb-3">
                        <div class="col-xxl-5 col-sm-6">
                            <div class="search-box">
                                <input type="text" class="form-control" name="search"
                                    placeholder="Search for product name, description or something..."
                                    value="{{ request('search') }}">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <!--end col-->
                        {{-- Date filter removed. It filtered products by created_at,
                             i.e. when the row was added to the database, which is not a
                             question anyone asks of a product list. --}}
                        {{-- <div class="col-xxl-2 col-sm-4">
                            <div>
                                <select class="form-control" data-choices data-choices-search-false name="stock_status"
                                    id="idStatus">
                                    <option value="all"
                                        {{ request('stock_status') == 'all' || !request('stock_status') ? 'selected' : '' }}>All
                                    </option>
                                    <option value="in_stock" {{ request('stock_status') == 'in_stock' ? 'selected' : '' }}>In Stock
                                    </option>
                                    <option value="low_stock" {{ request('stock_status') == 'low_stock' ? 'selected' : '' }}>
                                        Low Stock</option>
                                    <option value="out_of_stock" {{ request('stock_status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock
                                    </option>
                                </select>
                            </div>
                        </div> --}}
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
                    <table class="table table-nowrap align-middle" id="productTable">
                        <thead class="text-muted table-light">
                            <tr class="text-uppercase">
                                <th scope="col" style="width: 25px;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="checkAll" value="option">
                                    </div>
                                </th>
                                <th class="sort" data-sort="id">ID</th>
                                <th class="sort" data-sort="name">Name</th>
                                <th class="sort" data-sort="description">Description</th>
                                @if(auth()->user()->role == 'superadmin')
                                <th class="sort" data-sort="price">Price</th>
                                <th class="sort" data-sort="stock">Stock</th>
                                @endif
                                <th class="sort" data-sort="action">Action</th>
                            </tr>
                        </thead>
                        <tbody class="list form-check-all">
                            @forelse($products as $product)
                            <tr>
                                <th scope="row">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="checkAll" value="option1">
                                    </div>
                                </th>
                                <td class="id">{{ $product->id }}</td>
                                <td class="name">{{ $product->name }}</td>
                                <td class="description">{{ $product->description }}</td>
                                @if(auth()->user()->role == 'superadmin')
                                <td class="price">{{ $product->price ? number_format($product->price, 2) : '-' }}</td>
                                <td class="stock">{{ $product->stock ?? '-' }}</td>
                                @endif
                                <td>
                                    <ul class="list-inline hstack gap-2 mb-0">
                                        <li class="list-inline-item edit" data-bs-toggle="tooltip"
                                            data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                            <a href="javascript:void(0);" class="text-primary d-inline-block edit-item-btn"
                                               data-bs-toggle="modal" data-bs-target="#editProductModal{{ $product->id }}">
                                                <i class="ri-pencil-fill fs-16"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item" data-bs-toggle="tooltip"
                                            data-bs-trigger="hover" data-bs-placement="top" title="Remove">
                                            <a class="text-danger d-inline-block remove-item-btn"
                                               data-bs-toggle="modal" href="#deleteProductModal{{ $product->id }}">
                                                <i class="ri-delete-bin-5-fill fs-16"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                            
                            <!-- Edit Product Modal -->
                            <div class="modal fade" id="editProductModal{{ $product->id }}" tabindex="-1" aria-labelledby="editProductModalLabel{{ $product->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-light p-3">
                                            <h5 class="modal-title" id="editProductModalLabel{{ $product->id }}">Edit Product</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('products.update', $product->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="edit-name-{{ $product->id }}" class="form-label">Name</label>
                                                    <input type="text" class="form-control" id="edit-name-{{ $product->id }}" name="name" value="{{ $product->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit-description-{{ $product->id }}" class="form-label">Description</label>
                                                    <textarea class="form-control" id="edit-description-{{ $product->id }}" name="description" rows="3" required>{{ $product->description }}</textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit-price-{{ $product->id }}" class="form-label">Price</label>
                                                    <input type="number" step="0.01" min="0" class="form-control" id="edit-price-{{ $product->id }}" name="price" value="{{ $product->price }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit-stock-{{ $product->id }}" class="form-label">Stock</label>
                                                    <input type="number" min="0" class="form-control" id="edit-stock-{{ $product->id }}" name="stock" value="{{ $product->stock }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit-coa-template-{{ $product->id }}" class="form-label">COA Template</label>
                                                    @php
                                                        // Variant groups collapse to one entry: a product is "MSC P2",
                                                        // and QC picks with/without the patient name per order.
                                                        $coaSvc = app(\App\Services\CoaTemplateService::class);
                                                        $coaSelected = $coaSvc->canonicalProductValue($product->coa_template ?? '');
                                                        $coaIsUnset = ($product->coa_template ?? '') === '';
                                                    @endphp
                                                    <select class="form-select" id="edit-coa-template-{{ $product->id }}" name="coa_template"
                                                        @if(auth()->user()->role != 'superadmin') required @endif>
                                                        @if(auth()->user()->role == 'superadmin')
                                                            <option value="" @selected($coaIsUnset)>— Not set (ask when generating) —</option>
                                                        @else
                                                            {{-- An admin editing a product that is still unset has to resolve it
                                                                 before saving, which is how the unset state gets cleaned up. --}}
                                                            <option value="" disabled @selected($coaIsUnset)>— Select a template —</option>
                                                        @endif
                                                        @foreach ($coaSvc->productChoices() as $coaKey => $coaLabel)
                                                            <option value="{{ $coaKey }}" @selected($coaSelected === $coaKey)>{{ $coaLabel }}</option>
                                                        @endforeach
                                                        <option value="none" @selected(($product->coa_template ?? '') === 'none')>No COA for this product</option>
                                                    </select>
                                                    <div class="form-text">
                                                        Products set to <em>No COA</em> show no COA button.
                                                        @if(auth()->user()->role == 'superadmin')
                                                            Leave unset to pick the template when generating the COA. Superadmin only.
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Order Form Behaviour</label>
                                                    {{-- The hidden 0 makes an unticked box submit a value. Without it
                                                         an unticked box sends nothing and the flag can never be cleared. --}}
                                                    <input type="hidden" name="requires_patient_details" value="0">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value="1"
                                                               id="edit-requires-patient-details-{{ $product->id }}"
                                                               name="requires_patient_details"
                                                               @checked($product->requires_patient_details)>
                                                        <label class="form-check-label" for="edit-requires-patient-details-{{ $product->id }}">
                                                            Patient test product
                                                        </label>
                                                    </div>
                                                    <div class="form-text">Makes <em>Patient Name</em> and <em>Patient IC Number</em> compulsory on the order, and hides <em>Quantity</em> and <em>Remarks</em> for that item. Delivery address and all other fields are unaffected.</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <div class="hstack gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Update Product</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- End Edit Product Modal -->
                            
                            <!-- Delete Product Modal -->
                            <div class="modal fade flip" id="deleteProductModal{{ $product->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-body p-5 text-center">
                                            <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                                                colors="primary:#405189,secondary:#f06548"
                                                style="width:90px;height:90px"></lord-icon>
                                            <div class="mt-4 text-center">
                                                <h4>You are about to delete this product?</h4>
                                                <p class="text-muted fs-15 mb-4">Deleting your product will remove
                                                    all of your information from our database.</p>
                                                <div class="hstack gap-2 justify-content-center remove">
                                                    <button class="btn btn-link link-success fw-medium text-decoration-none"
                                                        id="deleteRecord-close" data-bs-dismiss="modal"><i
                                                            class="ri-close-line me-1 align-middle"></i>
                                                        Close</button>
                                                    <form action="{{ route('products.destroy', $product->id) }}" method="POST">
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
                            <!-- End Delete Product Modal -->
                            @empty
                            <tr>
                                <td colspan="{{ auth()->user()->role == 'superadmin' ? '6' : '4' }}" class="text-center">No products found</td>
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
                            <p class="text-muted">We've searched more than 150+ Products, We did
                                not find any products for you search.</p>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <div class="pagination-wrap hstack gap-2">
                        {{ $products->links('vendor.pagination.bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('products.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="{{ old('price') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" min="0" class="form-control" id="stock" name="stock" value="{{ old('stock') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="coa_template" class="form-label">COA Template</label>
                        <select class="form-select" id="coa_template" name="coa_template"
                                @if(auth()->user()->role != 'superadmin') required @endif>
                            @if(auth()->user()->role == 'superadmin')
                                {{-- Only a superadmin may leave a product unresolved, because
                                     only a superadmin can pick the template later on the COA screen. --}}
                                <option value="" @selected(old('coa_template') === '')>— Not set (ask when generating) —</option>
                            @else
                                <option value="" disabled @selected(old('coa_template') === null)>— Select a template —</option>
                            @endif
                            @foreach (app(\App\Services\CoaTemplateService::class)->productChoices() as $coaKey => $coaLabel)
                                <option value="{{ $coaKey }}" @selected(old('coa_template') === $coaKey)>{{ $coaLabel }}</option>
                            @endforeach
                            <option value="none" @selected(old('coa_template') === 'none')>No COA for this product</option>
                        </select>
                        <div class="form-text">
                            Products set to <em>No COA</em> show no COA button.
                            @if(auth()->user()->role == 'superadmin')
                                Leave unset to pick the template when generating the COA. Superadmin only.
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Order Form Behaviour</label>
                        {{-- The hidden 0 makes an unticked box submit a value. Without it
                             an unticked box sends nothing and the flag can never be cleared. --}}
                        <input type="hidden" name="requires_patient_details" value="0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1"
                                   id="requires_patient_details" name="requires_patient_details"
                                   @checked(old('requires_patient_details'))>
                            <label class="form-check-label" for="requires_patient_details">
                                Patient test product
                            </label>
                        </div>
                        <div class="form-text">Makes <em>Patient Name</em> and <em>Patient IC Number</em> compulsory on the order, and hides <em>Quantity</em> and <em>Remarks</em> for that item. Delivery address and all other fields are unaffected.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="hstack gap-2 justify-content-end">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="add-btn">Add Product</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Add Product Modal -->

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
    });
</script>
@endsection