@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Blocked Dates Management</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Blocked Dates</li>
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
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-calendar-event-line me-2"></i>Block New Date
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('blocked-dates.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="blocked_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('blocked_date') is-invalid @enderror" 
                                id="blocked_date" name="blocked_date" 
                                data-provider="flatpickr" data-date-format="Y-m-d"
                                value="{{ old('blocked_date') }}" required>
                            @error('blocked_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">Select Type...</option>
                                <option value="holiday" {{ old('type') == 'holiday' ? 'selected' : '' }}>Holiday</option>
                                <option value="maintenance" {{ old('type') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="closure" {{ old('type') == 'closure' ? 'selected' : '' }}>Closure</option>
                                <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <input type="text" class="form-control @error('reason') is-invalid @enderror" 
                                id="reason" name="reason" placeholder="e.g., Christmas Day, System Maintenance"
                                value="{{ old('reason') }}">
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Optional - provide a specific reason for blocking this date</div>
                        </div>

                        <button type="submit" class="btn btn-danger w-100">
                            <i class="ri-calendar-close-line me-1"></i>Block Date
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ri-list-check me-2"></i>Blocked Dates List
                    </h5>
                    <span class="badge bg-danger">{{ $blockedDates->total() }} Total</span>
                </div>
                <div class="card-body">
                    @if($blockedDates->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-nowrap">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($blockedDates as $blockedDate)
                                        <tr>
                                            <td>
                                                <span class="fw-bold">{{ $blockedDate->blocked_date->format('d/m/Y') }}</span>
                                                <br>
                                                <small class="text-muted">{{ $blockedDate->blocked_date->format('l') }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ ucfirst($blockedDate->type) }}</span>
                                            </td>
                                            <td>
                                                {{ $blockedDate->reason ?: '-' }}
                                            </td>
                                            <td>
                                                @if($blockedDate->is_active)
                                                    <span class="badge bg-danger">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>{{ $blockedDate->creator->username ?? 'Unknown' }}</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <!-- Toggle Status -->
                                                    <form action="{{ route('blocked-dates.toggle', $blockedDate) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm {{ $blockedDate->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                                title="{{ $blockedDate->is_active ? 'Deactivate' : 'Activate' }}">
                                                            <i class="ri-{{ $blockedDate->is_active ? 'pause' : 'play' }}-line"></i>
                                                        </button>
                                                    </form>

                                                    <!-- Edit Button -->
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editModal{{ $blockedDate->id }}"
                                                            title="Edit">
                                                        <i class="ri-edit-line"></i>
                                                    </button>

                                                    <!-- Delete Button -->
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-blocked-date"
                                                            data-id="{{ $blockedDate->id }}"
                                                            data-date="{{ $blockedDate->blocked_date->format('d/m/Y') }}"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteBlockedDateModal"
                                                            title="Delete">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal{{ $blockedDate->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Blocked Date</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="{{ route('blocked-dates.update', $blockedDate) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Date</label>
                                                                <input type="text" class="form-control" 
                                                                    value="{{ $blockedDate->blocked_date->format('d/m/Y') }}" 
                                                                    disabled>
                                                                <div class="form-text">Date cannot be changed after creation</div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="edit_type{{ $blockedDate->id }}" class="form-label">Type <span class="text-danger">*</span></label>
                                                                <select class="form-select" id="edit_type{{ $blockedDate->id }}" name="type" required>
                                                                    <option value="holiday" {{ $blockedDate->type == 'holiday' ? 'selected' : '' }}>Holiday</option>
                                                                    <option value="maintenance" {{ $blockedDate->type == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                                                    <option value="closure" {{ $blockedDate->type == 'closure' ? 'selected' : '' }}>Closure</option>
                                                                    <option value="other" {{ $blockedDate->type == 'other' ? 'selected' : '' }}>Other</option>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="edit_reason{{ $blockedDate->id }}" class="form-label">Reason</label>
                                                                <input type="text" class="form-control" 
                                                                    id="edit_reason{{ $blockedDate->id }}" name="reason"
                                                                    value="{{ $blockedDate->reason }}">
                                                            </div>

                                                            {{-- <div class="mb-3">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" 
                                                                        id="edit_is_active{{ $blockedDate->id }}" name="is_active" value="1"
                                                                        {{ $blockedDate->is_active ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="edit_is_active{{ $blockedDate->id }}">
                                                                        Active (blocks orders on this date)
                                                                    </label>
                                                                </div>
                                                            </div> --}}
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $blockedDates->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ri-calendar-line display-4 text-muted"></i>
                            <h5 class="mt-3 text-muted">No Blocked Dates</h5>
                            <p class="text-muted">No dates have been blocked yet. Use the form on the left to block dates from ordering.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Blocked Date Modal -->
    <div class="modal fade" id="deleteBlockedDateModal" tabindex="-1" aria-labelledby="deleteBlockedDateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title" id="deleteBlockedDateModalLabel">
                        <i class="ri-delete-bin-line me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the blocked date <strong id="deleteDateText"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1"></i>Cancel
                    </button>
                    <form id="deleteBlockedDateForm" action="" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
                            <i class="ri-delete-bin-line me-1"></i>Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize flatpickr for the date input
            if (typeof flatpickr !== 'undefined') {
                flatpickr("#blocked_date", {
                    dateFormat: "Y-m-d",
                    minDate: "today",
                    allowInput: true
                });
            }

            // Delete Blocked Date Modal Handling
            const deleteModal = document.getElementById('deleteBlockedDateModal');
            const deleteForm = document.getElementById('deleteBlockedDateForm');
            const deleteDateText = document.getElementById('deleteDateText');

            document.querySelectorAll('.delete-blocked-date').forEach(button => {
                button.addEventListener('click', function() {
                    const blockedDateId = this.getAttribute('data-id');
                    const blockedDate = this.getAttribute('data-date');
                    
                    // Set the form action dynamically
                    deleteForm.action = `/settings/blocked-dates/${blockedDateId}`;
                    
                    // Update modal text
                    deleteDateText.textContent = blockedDate;
                });
            });
        });
    </script>
@endsection 