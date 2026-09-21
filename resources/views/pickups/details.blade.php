@extends('layouts.master')

@section('content')
    <style>
        .pickup-progress { padding: 20px 10px; position: relative; }
        .pickup-progress .track-line {
            height: 2px; background-color: #e0e0e0;
            position: absolute; top: 45px; left: 12%; right: 12%; z-index: 0;
        }
        .pickup-step { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; z-index: 1; }
        .pickup-step-icon {
            height: 50px; width: 50px; border-radius: 50%;
            background-color: #f5f5f5; border: 2px solid #e0e0e0; color: #9e9e9e;
            display: flex; align-items: center; justify-content: center; font-size: 20px;
        }
        .pickup-step-text { margin-top: 8px; font-size: 14px; font-weight: 500; color: #757575; text-align: center; }
        .pickup-step.completed .pickup-step-icon { background-color: #d4edda; border-color: #28a745; color: #28a745; }
        .pickup-step.completed .pickup-step-text { color: #28a745; }
        .pickup-step.active .pickup-step-icon { background-color: #cce5ff; border-color: #007bff; color: #007bff; transform: scale(1.1); }
        .pickup-step.active .pickup-step-text { color: #007bff; font-weight: 600; }
        @media (max-width: 768px) {
            .pickup-step-icon { height: 40px; width: 40px; font-size: 16px; }
            .pickup-step-text { font-size: 12px; }
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Pickup Details</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('pickups.index') }}">Pickup History</a></li>
                        <li class="breadcrumb-item active">{{ $pickup->reference_no }}</li>
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

    @php
        // Progress steps. Cancelled is shown as a banner instead of a step.
        $steps = [
            \App\Models\Pickup::STATUS_NEW        => ['label' => 'New',        'icon' => 'ri-file-list-3-line'],
            \App\Models\Pickup::STATUS_ON_THE_WAY => ['label' => 'On the Way', 'icon' => 'ri-truck-line'],
            \App\Models\Pickup::STATUS_PICKED_UP  => ['label' => 'Picked Up',  'icon' => 'ri-hand-heart-line'],
            \App\Models\Pickup::STATUS_RECEIVED   => ['label' => 'Received',   'icon' => 'ri-checkbox-circle-line'],
        ];
        $stepKeys = array_keys($steps);
        $currentIndex = array_search($pickup->status, $stepKeys, true);
        $isCancelled = $pickup->status === \App\Models\Pickup::STATUS_CANCELLED;
    @endphp

    {{-- Status --}}
    <div class="card mb-4 {{ $pickup->time_sensitive ? 'border border-danger' : '' }}">
        <div class="card-header">
            <div class="d-sm-flex align-items-center justify-content-between">
                <h5 class="card-title flex-grow-1 mb-0">Pickup Status</h5>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-sm-0">
                    <span class="badge fs-13 bg-light text-dark px-3 py-2">
                        <i class="ri-calendar-line align-middle me-1"></i>
                        Pickup: {{ $pickup->pickup_date->format('d M, Y') }}, {{ $pickup->pickup_time->format('h:i A') }}
                    </span>
                    @if($pickup->time_sensitive)
                        <span class="badge fs-13 bg-danger px-3 py-2">
                            <i class="ri-time-line align-middle me-1"></i>Time Sensitive
                        </span>
                    @endif
                    <span class="badge fs-13 bg-{{ $pickup->statusBadge() }} px-3 py-2">
                        Current Status: {{ $pickup->statusLabel() }}
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($isCancelled)
                <div class="alert alert-danger mb-0">
                    <i class="ri-close-circle-line me-2 align-middle"></i>
                    <strong>This pickup has been cancelled</strong>
                    @if($pickup->cancelled_by_name)
                        by {{ $pickup->cancelled_by_name }}
                    @endif
                    @if($pickup->cancelled_at)
                        on {{ $pickup->cancelled_at->format('d M, Y h:i A') }}
                    @endif
                    @if($pickup->cancel_reason)
                        <div class="mt-1" style="white-space: pre-line;">Reason: {{ $pickup->cancel_reason }}</div>
                    @endif
                </div>
            @else
                <div class="pickup-progress">
                    <div class="track-line"></div>
                    <div class="d-flex justify-content-between">
                        @foreach($steps as $key => $step)
                            @php
                                $i = $loop->index;
                                $state = $currentIndex === false ? '' : ($i < $currentIndex ? 'completed' : ($i === $currentIndex ? 'active' : ''));
                                // Received is the final step, so show it as done rather than in progress.
                                if ($key === \App\Models\Pickup::STATUS_RECEIVED && $i === $currentIndex) {
                                    $state = 'completed';
                                }
                            @endphp
                            @php
                                $stepTime = [
                                    'new'        => $pickup->created_at,
                                    'on_the_way' => $pickup->on_the_way_at,
                                    'picked_up'  => $pickup->picked_up_at,
                                    'received'   => $pickup->received_at,
                                ][$key] ?? null;
                            @endphp
                            <div class="pickup-step {{ $state }}">
                                <div class="pickup-step-icon"><i class="{{ $step['icon'] }}"></i></div>
                                <span class="pickup-step-text">{{ $step['label'] }}</span>
                                @if($stepTime)
                                    <small class="text-muted text-center">{{ $stepTime->format('d M, h:i A') }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(in_array(true, $can, true))
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                    @if($can['on_the_way'])
                        <form action="{{ route('pickups.onTheWay', $pickup->id) }}" method="POST"
                              onsubmit="return confirm('Take this pickup and mark it On the Way?');">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="ri-truck-line me-1 align-bottom"></i> Mark as On the Way
                            </button>
                        </form>
                    @endif
                    @if($can['picked_up'])
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pickedUpModal">
                            <i class="ri-hand-heart-line me-1 align-bottom"></i> Mark as Picked Up
                        </button>
                    @endif
                    @if($can['received'])
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#receivedModal">
                            <i class="ri-checkbox-circle-line me-1 align-bottom"></i> Mark as Received
                        </button>
                    @endif
                    @if($can['cancel'])
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="ri-close-circle-line me-1 align-bottom"></i> Cancel Pickup
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            {{-- Items --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pickup {{ $pickup->reference_no }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Item</th>
                                    <th style="width: 120px;">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pickup->items as $item)
                                <tr>
                                    <td>{{ $item->product->name ?? 'Unknown item' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Remarks --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-chat-3-line align-middle me-1"></i>Remarks</h5>
                </div>
                <div class="card-body">
                    @if($pickup->remarks)
                        <p class="mb-0" style="white-space: pre-line;">{{ $pickup->remarks }}</p>
                    @else
                        <p class="text-muted mb-0">No remarks</p>
                    @endif
                </div>
            </div>

            {{-- Pickup confirmation (filled by the despatcher) --}}
            @if($pickup->despatcher_name || $pickup->picked_up_at)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-truck-line align-middle me-1"></i>Pickup Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Despatcher</p>
                            <p class="fw-medium mb-0">{{ $pickup->despatcher_name ?? '-' }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">On the Way</p>
                            <p class="fw-medium mb-0">{{ $pickup->on_the_way_at ? $pickup->on_the_way_at->format('d M, Y h:i A') : '-' }}</p>
                        </div>
                        @if($pickup->picked_up_at)
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Picked Up</p>
                            <p class="fw-medium mb-0">{{ $pickup->picked_up_at->format('d M, Y h:i A') }}</p>
                            @if($pickup->picked_up_by_name && $pickup->picked_up_by_name !== $pickup->despatcher_name)
                                <small class="text-muted">Recorded by {{ $pickup->picked_up_by_name }}</small>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Handed Over By</p>
                            <p class="fw-medium mb-0">{{ $pickup->handed_over_by ?? '-' }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Temperature at Pickup</p>
                            <p class="fw-medium mb-0">{{ $pickup->pickup_temperature ?: '-' }}</p>
                        </div>
                        <div class="col-12">
                            <p class="text-muted mb-1">Despatcher Remarks</p>
                            <p class="mb-0" style="white-space: pre-line;">{{ $pickup->pickup_remarks ?: '-' }}</p>
                        </div>
                        @if(!empty($pickup->pickup_photos))
                        <div class="col-12">
                            <p class="text-muted mb-2">Pickup Photos</p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($pickup->pickup_photos as $photo)
                                    <a href="{{ asset('storage/' . \App\Models\Pickup::PHOTO_DIR . '/' . $photo) }}" target="_blank" rel="noopener">
                                        <img src="{{ asset('storage/' . \App\Models\Pickup::PHOTO_DIR . '/' . $photo) }}" alt="Pickup photo"
                                             class="rounded border" style="width: 120px; height: 120px; object-fit: cover;">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Received at MGRC --}}
            @if($pickup->received_at)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-checkbox-circle-line align-middle me-1 text-success"></i>Received at MGRC</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Received By</p>
                            <p class="fw-medium mb-0">{{ $pickup->received_by_name ?? '-' }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Received</p>
                            <p class="fw-medium mb-0">{{ $pickup->received_at->format('d M, Y h:i A') }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Temperature on Arrival</p>
                            <p class="fw-medium mb-0">{{ $pickup->received_temperature ?: '-' }}</p>
                        </div>
                        <div class="col-12">
                            <p class="text-muted mb-1">Receiving Remarks</p>
                            <p class="mb-0" style="white-space: pre-line;">{{ $pickup->received_remarks ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-xl-4">
            {{-- Customer --}}
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Customer Details</h5>
                    @if($pickup->customer)
                        <a href="{{ route('customers.show', $pickup->customer->id) }}" class="link-secondary">View Profile</a>
                    @endif
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 vstack gap-3">
                        <li class="fw-semibold">{{ $pickup->customer->name ?? '-' }}</li>
                        @if($pickup->customer && $pickup->customer->email)
                            <li><i class="ri-mail-line me-2 align-middle text-muted"></i>{{ $pickup->customer->email }}</li>
                        @endif
                        <li><i class="ri-phone-line me-2 align-middle text-muted"></i>{{ $pickup->contact_phone }}</li>
                        @if($pickup->contact_person)
                            <li><i class="ri-user-line me-2 align-middle text-muted"></i>Contact: {{ $pickup->contact_person }}</li>
                        @endif
                        <li><i class="ri-user-follow-line me-2 align-middle text-muted"></i>Requested by: {{ $pickup->requested_by ?? '-' }}</li>
                        <li><i class="ri-time-line me-2 align-middle text-muted"></i>Requested on: {{ $pickup->created_at->format('d M, Y h:i A') }}</li>
                    </ul>
                </div>
            </div>

            {{-- Pickup address --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-map-pin-line align-middle me-1 text-muted"></i>Pickup Address</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-line;">{{ $pickup->pickup_address }}</p>
                </div>
            </div>
        </div>
    </div>
    {{-- ===== Modals ===== --}}
    @if($can['picked_up'])
    <div class="modal fade" id="pickedUpModal" tabindex="-1" aria-labelledby="pickedUpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle p-3">
                    <h5 class="modal-title" id="pickedUpModalLabel">Mark Pickup as Picked Up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('pickups.pickedUp', $pickup->id) }}" method="POST" enctype="multipart/form-data" id="pickedUpForm">
                    @csrf
                    <div class="modal-body">
                        @if($errors->pickedUp->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->pickedUp->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="alert alert-info">
                            <i class="ri-information-line me-1"></i>
                            Marking as "Picked Up" means you have collected the items from the customer.
                        </div>

                        <div class="mb-3">
                            <label for="pickup_photos" class="form-label">Pickup Photo(s) <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="pickup_photos" name="pickup_photos[]"
                                   accept="image/*" capture="environment" multiple required>
                            <div class="form-text" id="pickupPhotosInfo">Photo of the collected items. Up to 5 photos, 10MB each. Photos are compressed before upload.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="picked_up_at" class="form-label">Pickup Date &amp; Time <span class="text-danger">*</span></label>
                                <input type="text" class="form-control js-datetime-now" id="picked_up_at" name="picked_up_at"
                                       value="{{ old('picked_up_at') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="handed_over_by" class="form-label">Handed Over By <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="handed_over_by" name="handed_over_by"
                                       placeholder="Name of person who gave the items" value="{{ old('handed_over_by', $pickup->contact_person) }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pickup_temperature" class="form-label">Temperature at Pickup <span class="text-muted">(Optional)</span></label>
                            <input type="text" class="form-control" id="pickup_temperature" name="pickup_temperature"
                                   placeholder="e.g., 4°C" value="{{ old('pickup_temperature') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Items Collected <span class="text-danger">*</span></label>
                            <ul class="mb-2">
                                @foreach($pickup->items as $item)
                                    <li>{{ $item->product->name ?? 'Unknown item' }} &times; {{ $item->quantity }}</li>
                                @endforeach
                            </ul>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="picked_items_confirmed" name="items_confirmed" value="1" required>
                                <label class="form-check-label" for="picked_items_confirmed">I have collected all the items and quantities above</label>
                            </div>
                            <div class="form-text">If anything is different, tick this and explain in Remarks.</div>
                        </div>

                        <div class="mb-0">
                            <label for="pickup_remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="pickup_remarks" name="pickup_remarks" rows="2">{{ old('pickup_remarks') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="pickedUpSubmit">Confirm Pickup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($can['received'])
    <div class="modal fade" id="receivedModal" tabindex="-1" aria-labelledby="receivedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success-subtle p-3">
                    <h5 class="modal-title" id="receivedModalLabel">Mark Pickup as Received</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('pickups.received', $pickup->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if($errors->received->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->received->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="alert alert-info">
                            <i class="ri-information-line me-1"></i>
                            This records <strong>{{ Auth::user()->username }}</strong> as receiving the items from {{ $pickup->despatcher_name ?? 'the despatcher' }}.
                        </div>

                        <div class="mb-3">
                            <label for="received_at" class="form-label">Received Date &amp; Time <span class="text-danger">*</span></label>
                            <input type="text" class="form-control js-datetime-now" id="received_at" name="received_at"
                                   value="{{ old('received_at') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="received_temperature" class="form-label">Temperature on Arrival <span class="text-muted">(Optional)</span></label>
                            <input type="text" class="form-control" id="received_temperature" name="received_temperature"
                                   placeholder="e.g., 4°C" value="{{ old('received_temperature') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Items Received <span class="text-danger">*</span></label>
                            <ul class="mb-2">
                                @foreach($pickup->items as $item)
                                    <li>{{ $item->product->name ?? 'Unknown item' }} &times; {{ $item->quantity }}</li>
                                @endforeach
                            </ul>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="received_items_confirmed" name="items_confirmed" value="1" required>
                                <label class="form-check-label" for="received_items_confirmed">I have received all the items and quantities above</label>
                            </div>
                            <div class="form-text">If anything is different, tick this and explain in Remarks.</div>
                        </div>

                        <div class="mb-0">
                            <label for="received_remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="received_remarks" name="received_remarks" rows="2">{{ old('received_remarks') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Confirm Received</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($can['cancel'])
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger-subtle p-3">
                    <h5 class="modal-title" id="cancelModalLabel">Cancel Pickup {{ $pickup->reference_no }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('pickups.cancel', $pickup->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if($errors->cancel->any())
                            <div class="alert alert-danger mb-3">{{ $errors->cancel->first() }}</div>
                        @endif
                        @if($pickup->status === \App\Models\Pickup::STATUS_ON_THE_WAY)
                            <div class="alert alert-warning">
                                <i class="ri-alert-line me-1"></i>The despatcher ({{ $pickup->despatcher_name }}) is already on the way.
                            </div>
                        @endif
                        <label for="cancel_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="3" required>{{ old('cancel_reason') }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep Pickup</button>
                        <button type="submit" class="btn btn-danger">Cancel Pickup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pad = n => String(n).padStart(2, '0');
    const nowString = () => {
        const d = new Date();
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    };

    // Date & time fields default to now when the modal opens (unless the
    // form came back with an old value after a validation error).
    document.querySelectorAll('.js-datetime-now').forEach(function (input) {
        let fp = null;
        if (typeof flatpickr !== 'undefined') {
            // No maxDate here. 'today' in flatpickr means today at 00:00, which
            // also caps today's time at midnight. The limit is set to the
            // current moment each time the modal opens instead (below).
            fp = flatpickr(input, {
                enableTime: true,
                time_24hr: false,
                dateFormat: 'Y-m-d H:i',
                altInput: true,
                altFormat: 'd M Y, h:i K',
                allowInput: false
            });
        }
        const modal = input.closest('.modal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function () {
                if (fp) {
                    // A pickup or receipt cannot be recorded in the future.
                    fp.set('maxDate', new Date());
                }
                if (!input.value) {
                    fp ? fp.setDate(nowString(), true, 'Y-m-d H:i') : (input.value = nowString());
                }
            });
        }
    });

    // Reopen the modal that failed validation.
    @if($errors->pickedUp->any())
        bootstrap.Modal.getOrCreateInstance(document.getElementById('pickedUpModal')).show();
    @elseif($errors->received->any())
        bootstrap.Modal.getOrCreateInstance(document.getElementById('receivedModal')).show();
    @elseif($errors->cancel->any())
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cancelModal')).show();
    @endif

    // Picked Up: compress photos in the browser before upload, same settings
    // as order photos (1280px, JPEG 0.7). HEIC that the browser cannot decode
    // is sent as it is.
    const photoForm = document.getElementById('pickedUpForm');
    if (photoForm) {
        const photoInput = document.getElementById('pickup_photos');
        const info = document.getElementById('pickupPhotosInfo');
        const submitBtn = document.getElementById('pickedUpSubmit');
        let compressed = false;

        const compress = file => new Promise(resolve => {
            if (!file.type.startsWith('image/')) return resolve(file);
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                const scale = Math.min(1, 1280 / Math.max(img.width, img.height));
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(img.width * scale);
                canvas.height = Math.round(img.height * scale);
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(url);
                canvas.toBlob(blob => {
                    if (!blob) return resolve(file);
                    const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                    resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', 0.7);
            };
            img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
            img.src = url;
        });

        photoInput.addEventListener('change', function () {
            compressed = false;
            const n = photoInput.files.length;
            if (n > 5) {
                alert('Please select up to 5 photos.');
                photoInput.value = '';
                return;
            }
            if (n) info.textContent = n + ' photo(s) selected. They will be compressed before upload.';
        });

        photoForm.addEventListener('submit', async function (e) {
            if (compressed) return;           // second pass: let it submit
            if (!photoForm.checkValidity()) return;  // let the browser show what is missing
            e.preventDefault();

            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';

            try {
                const dt = new DataTransfer();
                for (const file of photoInput.files) {
                    dt.items.add(await compress(file));
                }
                photoInput.files = dt.files;
            } catch (err) {
                // Older browsers without DataTransfer: upload the originals.
            }

            compressed = true;
            photoForm.submit();
        });
    }
});
</script>
@endsection