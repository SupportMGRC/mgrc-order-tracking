@extends('layouts.master')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">User Management</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">User Management</li>
                </ol>
            </div>
        </div>
    </div>
</div>

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

<div class="card">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-sm-8">
                <div class="d-flex align-items-center gap-2">
                    <form action="{{ route('users.index') }}" method="GET" class="d-flex gap-2 w-100">
                        <div class="col-5">
                            <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search for name or designation...">
                        </div>
                        <div class="col-3">
                            <select class="form-select" name="department">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-equalizer-fill me-1"></i> Filter
                        </button>
                    </form>
                </div>
            </div>
            <!--end col-->
            <div class="col-sm-auto ms-auto">
                <div class="list-grid-nav hstack gap-1">
                    <a href="{{ route('users.index', ['view' => 'grid'] + request()->except('view')) }}" class="btn btn-soft-info nav-link btn-icon fs-14 {{ request('view') != 'list' ? 'active' : '' }} filter-button"><i class="ri-grid-fill"></i></a>
                    {{-- <a href="{{ route('users.index', ['view' => 'list'] + request()->except('view')) }}" class="btn btn-soft-info nav-link btn-icon fs-14 {{ request('view') == 'list' ? 'active' : '' }} filter-button"><i class="ri-list-unordered"></i></a> --}}
                    <a href="{{ route('users.index', ['modal' => 'add']) }}" class="btn btn-success"><i class="ri-add-fill me-1 align-bottom"></i> Add User</a>
                </div>
            </div>
            <!--end col-->
        </div>
        <!--end row-->
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div>
            <div id="teamlist">
                <div class="team-list {{ request('view') == 'list' ? 'list-view-filter' : 'grid-view-filter' }} row" id="team-member-list">                                    
                    @foreach($users as $user)
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center">
                                    <div class="position-relative d-inline-block">
                                        <div class="avatar-lg">
                                            <div class="avatar-title bg-light rounded-circle text-primary">
                                                {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                            </div>
                                        </div>
                                    </div>
                                    <h5 class="fs-16 mb-1 mt-3">{{ $user->first_name }} {{ $user->last_name }}</h5>
                                    <p class="text-muted mb-0">{{ $user->designation }}</p>
                                    <p class="text-muted fs-12 mb-0">{{ $user->email }}</p>
                                    <p class="badge bg-light text-primary fs-12 mb-3">{{ $user->department }}</p>
                                </div>
                                <div class="hstack gap-2 justify-content-center">
                                    <a href="{{ route('users.index', ['modal' => 'edit', 'id' => $user->id]) }}" class="btn btn-soft-info">
                                        <i class="ri-pencil-fill align-bottom"></i>
                                    </a>
                                    <a href="{{ route('users.index', ['modal' => 'delete', 'id' => $user->id]) }}" class="btn btn-soft-danger">
                                        <i class="ri-delete-bin-2-line align-bottom"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if(count($users) == 0)
                <div class="py-4 mt-4 text-center">
                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:72px;height:72px"></lord-icon>
                    <h5 class="mt-4">Sorry! No Result Found</h5>
                </div>
                @endif
                
                <!-- Pagination -->
                <div class="d-flex justify-content-end mt-0">
                    @if($users->hasPages())
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            {{-- Previous Page Link --}}
                            <li class="page-item">
                                <a class="page-link" href="{{ $users->previousPageUrl() }}" aria-label="Previous">
                                    <i class="mdi mdi-chevron-left"></i>
                                </a>
                            </li>
                            
                            {{-- Pagination Elements --}}
                            @php
                                $startPage = max($users->currentPage() - 4, 1);
                                $endPage = min($startPage + 7, $users->lastPage());
                                
                                if ($endPage - $startPage < 7) {
                                    $startPage = max($endPage - 7, 1);
                                }
                            @endphp
                            
                            @for ($i = $startPage; $i <= $endPage; $i++)
                                @if ($users->currentPage() == $i)
                                    <li class="page-item active">
                                        <span class="page-link">
                                            {{ $i }}
                                            <span class="sr-only">(current)</span>
                                        </span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $users->url($i) }}">{{ $i }}</a>
                                    </li>
                                @endif
                            @endfor
                            
                            {{-- Next Page Link --}}
                            <li class="page-item">
                                <a class="page-link" href="{{ $users->nextPageUrl() }}" aria-label="Next">
                                    <i class="mdi mdi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    @endif
                </div>
            </div>
            
            <!-- Add User Modal -->
            @if(request()->has('modal') && request('modal') === 'add')
            <div class="modal fade show" id="addUserModal" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0">
                        <div class="modal-body">
                            <form autocomplete="off" id="memberlist-form" action="{{ route('users.store') }}" method="POST" class="needs-validation" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="px-1 pt-1">
                                            <div class="modal-team-cover position-relative mb-0 mt-n4 mx-n4 rounded-top overflow-hidden">
                                                <img src="{{ asset('assets/images/small/img-9.jpg') }}" alt="" id="cover-img" class="img-fluid">

                                                <div class="d-flex position-absolute start-0 end-0 top-0 p-3">
                                                    <div class="flex-grow-1">
                                                        <h5 class="modal-title text-white">Add New User</h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <div class="d-flex gap-3 align-items-center">
                                                            <a href="{{ route('users.index') }}" class="btn-close btn-close-white"></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center mb-4 mt-n5 pt-2">
                                            <div class="position-relative d-inline-block">
                                                <div class="avatar-lg">
                                                    <div class="avatar-title bg-light rounded-circle">
                                                        <img src="{{ asset('assets/images/users/user-dummy-img.jpg') }}" class="avatar-md rounded-circle h-auto" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter first name" required value="{{ old('first_name') }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Enter last name" required value="{{ old('last_name') }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required value="{{ old('username') }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required value="{{ old('email') }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="designation" class="form-label">Designation</label>
                                            <input type="text" class="form-control" id="designation" name="designation" placeholder="Enter designation" required value="{{ old('designation') }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="department" class="form-label">Department</label>
                                            <select class="form-select" id="department" name="department">
                                                <option value="">Select Department</option>
                                                <option value="Admin & Human Resource" {{ old('department') == 'Admin & Human Resource' ? 'selected' : '' }}>Admin & Human Resource</option>
                                                <option value="Cell Lab" {{ old('department') == 'Cell Lab' ? 'selected' : '' }}>Cell Lab</option>
                                                <option value="Medical Affairs" {{ old('department') == 'Medical Affairs' ? 'selected' : '' }}>Medical Affairs</option>
                                                <option value="Quality" {{ old('department') == 'Quality' ? 'selected' : '' }}>Quality</option>
                                                <option value="Finance" {{ old('department') == 'Finance' ? 'selected' : '' }}>Finance</option>
                                                <option value="Management" {{ old('department') == 'Management' ? 'selected' : '' }}>Management</option>
                                                <option value="Software" {{ old('department') == 'Software' ? 'selected' : '' }}>Software</option>
                                                <option value="Bioinformatics" {{ old('department') == 'Bioinformatics' ? 'selected' : '' }}>Bioinformatics</option>
                                                <option value="Genomics" {{ old('department') == 'Genomics' ? 'selected' : '' }}>Genomics</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="role" class="form-label">Role</label>
                                            <select class="form-select" id="role" name="role">
                                                <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="superadmin" {{ old('role') == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Email Notifications</label>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="receive_new_order_emails" name="receive_new_order_emails" value="1" {{ old('receive_new_order_emails') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="receive_new_order_emails">
                                                    Receive new order notifications
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="receive_order_ready_emails" name="receive_order_ready_emails" value="1" {{ old('receive_order_ready_emails') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="receive_order_ready_emails">
                                                    Receive order ready notifications
                                                </label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                                        </div>

                                        <div class="hstack gap-2 justify-content-end">
                                            <a href="{{ route('users.index') }}" class="btn btn-light">Close</a>
                                            <button type="submit" class="btn btn-success">Add User</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <!-- End Add User Modal -->

            <!-- Edit User Modal -->
            @if(request()->has('modal') && request('modal') === 'edit' && request()->has('id') && $editUser = \App\Models\User::find(request('id')))
            <div class="modal fade show" id="editUserModal" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0">
                        <div class="modal-body">
                            <form autocomplete="off" action="{{ route('users.update.post', $editUser->id) }}" method="POST" class="needs-validation" novalidate>
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $editUser->id }}">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="px-1 pt-1">
                                            <div class="modal-team-cover position-relative mb-0 mt-n4 mx-n4 rounded-top overflow-hidden">
                                                <img src="{{ asset('assets/images/small/img-9.jpg') }}" alt="" class="img-fluid">
                                                <div class="d-flex position-absolute start-0 end-0 top-0 p-3">
                                                    <div class="flex-grow-1">
                                                        <h5 class="modal-title text-white">Edit User</h5>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <div class="d-flex gap-3 align-items-center">
                                                            <a href="{{ route('users.index') }}" class="btn-close btn-close-white"></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center mb-4 mt-n5 pt-2">
                                            <div class="position-relative d-inline-block">
                                                <div class="avatar-lg">
                                                    <div class="avatar-title bg-light rounded-circle text-primary">
                                                        {{ strtoupper(substr($editUser->first_name, 0, 1) . substr($editUser->last_name, 0, 1)) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit-first-name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="edit-first-name" name="first_name" placeholder="Enter first name" required value="{{ old('first_name', $editUser->first_name) }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit-last-name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="edit-last-name" name="last_name" placeholder="Enter last name" required value="{{ old('last_name', $editUser->last_name) }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit-username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="edit-username" name="username" placeholder="Enter username" required value="{{ old('username', $editUser->username) }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit-email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="edit-email" name="email" placeholder="Enter email" required value="{{ old('email', $editUser->email) }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit-designation" class="form-label">Designation</label>
                                            <input type="text" class="form-control" id="edit-designation" name="designation" placeholder="Enter designation" required value="{{ old('designation', $editUser->designation) }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit-department" class="form-label">Department</label>
                                            <select class="form-select" id="edit-department" name="department">
                                                <option value="">Select Department</option>
                                                <option value="Admin & Human Resource" {{ old('department', $editUser->department) == 'Admin & Human Resource' ? 'selected' : '' }}>Admin & Human Resource</option>
                                                <option value="Cell Lab" {{ old('department', $editUser->department) == 'Cell Lab' ? 'selected' : '' }}>Cell Lab</option>
                                                <option value="Medical Affairs" {{ old('department', $editUser->department) == 'Medical Affairs' ? 'selected' : '' }}>Medical Affairs</option>
                                                <option value="Quality" {{ old('department', $editUser->department) == 'Quality' ? 'selected' : '' }}>Quality</option>
                                                <option value="Finance" {{ old('department', $editUser->department) == 'Finance' ? 'selected' : '' }}>Finance</option>
                                                <option value="Management" {{ old('department', $editUser->department) == 'Management' ? 'selected' : '' }}>Management</option>
                                                <option value="Software" {{ old('department', $editUser->department) == 'Software' ? 'selected' : '' }}>Software</option>
                                                <option value="Bioinformatics" {{ old('department', $editUser->department) == 'Bioinformatics' ? 'selected' : '' }}>Bioinformatics</option>
                                                <option value="Genomics" {{ old('department', $editUser->department) == 'Genomics' ? 'selected' : '' }}>Genomics</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit-role" class="form-label">Role</label>
                                            <select class="form-select" id="edit-role" name="role">
                                                <option value="user" {{ old('role', $editUser->role) == 'user' ? 'selected' : '' }}>User</option>
                                                <option value="admin" {{ old('role', $editUser->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="superadmin" {{ old('role', $editUser->role) == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Email Notifications</label>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="edit_receive_new_order_emails" name="receive_new_order_emails" value="1" {{ old('receive_new_order_emails', $editUser->receive_new_order_emails) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="edit_receive_new_order_emails">
                                                    Receive new order notifications
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="edit_receive_order_ready_emails" name="receive_order_ready_emails" value="1" {{ old('receive_order_ready_emails', $editUser->receive_order_ready_emails) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="edit_receive_order_ready_emails">
                                                    Receive order ready notifications
                                                </label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit-password" class="form-label">New Password (leave blank to keep current)</label>
                                            <input type="password" class="form-control" id="edit-password" name="password" placeholder="Enter new password">
                                        </div>

                                        <div class="hstack gap-2 justify-content-end">
                                            <a href="{{ route('users.index') }}" class="btn btn-light">Close</a>
                                            <button type="submit" class="btn btn-success">Update User</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <!--end edit modal-->

            <!-- Delete User Modal -->
            @if(request()->has('modal') && request('modal') === 'delete' && request()->has('id') && $deleteUser = \App\Models\User::find(request('id')))
            <div class="modal fade show" id="deleteUserModal" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete User</h5>
                            <a href="{{ route('users.index') }}" class="btn-close"></a>
                        </div>
                        <div class="modal-body">
                            <div class="mt-2 text-center">
                                <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                                <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                                    <h4>Are you sure?</h4>
                                    <p class="text-muted mx-4 mb-0">Are you sure you want to remove {{ $deleteUser->first_name }} {{ $deleteUser->last_name }}?</p>
                                </div>
                            </div>
                            <form action="{{ route('users.delete.post', $deleteUser->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $deleteUser->id }}">
                                <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                                    <a href="{{ route('users.index') }}" class="btn w-sm btn-light">Close</a>
                                    <button type="submit" class="btn w-sm btn-danger">Yes, Delete It!</button>
                                </div>
                            </form>
                        </div>
                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div><!-- /.modal -->
            @endif
            <!--end delete modal -->
        </div>
    </div><!-- end col -->
</div>
<!--end row-->

@endsection