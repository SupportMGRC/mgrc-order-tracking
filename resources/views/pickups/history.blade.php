@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Pickup History</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Menu</a></li>
                        <li class="breadcrumb-item active">Pickup History</li>
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
        $activeRange = request('date_range', 'all');
        // Filters carried across tab links so switching status keeps them.
        $filters = [
            'search'     => request('search'),
            'date_range' => request('date_range'),
            'date_from'  => request('date_from'),
            'date_to'    => request('date_to'),
        ];
        $tabIcons = [
            'new'        => 'ri-add-circle-line',
            'on_the_way' => 'ri-truck-line',
            'picked_up'  => 'ri-hand-heart-line',
            'received'   => 'ri-checkbox-circle-line',
            'cancelled'  => 'ri-close-circle-line',
        ];
    @endphp

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Pickup History</h5>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('pickups.create') }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> New Pickup
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form action="{{ route('pickups.index') }}" method="GET">
                        @if($status !== 'all')
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif
                        <div class="row g-2 align-items-center">
                            <div class="col-lg-5 col-12">
                                <div class="search-box">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search reference, customer, requested by..."
                                        value="{{ request('search') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <select class="form-select" name="date_range" id="dateRangeSelect">
                                    <option value="all" @selected($activeRange === 'all')>Pickup Date: All Time</option>
                                    <option value="today" @selected($activeRange === 'today')>Pickup Date: Today</option>
                                    <option value="weekly" @selected($activeRange === 'weekly')>Pickup Date: This Week</option>
                                    <option value="monthly" @selected($activeRange === 'monthly')>Pickup Date: This Month</option>
                                    <option value="custom" @selected($activeRange === 'custom')>Pickup Date: Custom range...</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-sm-6">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="ri-equalizer-fill align-bottom me-1"></i>Filter
                                    </button>
                                    <a href="{{ route('pickups.index', ['status' => $status]) }}" class="btn btn-light" title="Clear all filters">
                                        <i class="ri-close-line align-bottom"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="row g-2 mt-1 {{ $activeRange === 'custom' ? '' : 'd-none' }}" id="customRangeRow">
                            <div class="col-lg-3 col-sm-6">
                                <input type="text" class="form-control" name="date_from" id="dateFrom" placeholder="From" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <input type="text" class="form-control" name="date_to" id="dateTo" placeholder="To" value="{{ request('date_to') }}">
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body pt-0">
                    <ul class="nav nav-tabs nav-tabs-custom nav-primary gap-1 flex-nowrap overflow-auto mb-3" role="tablist" style="white-space: nowrap;">
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-3 {{ $status === 'all' ? 'active' : '' }}"
                               href="{{ route('pickups.index', array_merge($filters, ['status' => 'all'])) }}">
                                <i class="ri-list-check me-1 align-bottom"></i> All
                                <span class="badge bg-dark align-middle ms-1">{{ $allCount }}</span>
                            </a>
                        </li>
                        @foreach(\App\Models\Pickup::STATUSES as $key => $label)
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-3 {{ $status === $key ? 'active' : '' }}"
                               href="{{ route('pickups.index', array_merge($filters, ['status' => $key])) }}">
                                <i class="{{ $tabIcons[$key] ?? 'ri-circle-line' }} me-1 align-bottom"></i> {{ $label }}
                                <span class="badge bg-{{ \App\Models\Pickup::STATUS_BADGES[$key] }} align-middle ms-1">{{ $counts[$key] ?? 0 }}</span>
                            </a>
                        </li>
                        @endforeach
                    </ul>

                    <div class="table-responsive table-card mb-2">
                        <table class="table table-nowrap align-middle">
                            <thead class="text-muted table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Requested By</th>
                                    <th>Despatcher</th>
                                    <th>Pickup Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pickups as $pickup)
                                <tr>
                                    <td>
                                        <a href="{{ route('pickups.show', $pickup->id) }}" class="fw-semibold">{{ $pickup->reference_no }}</a>
                                        @if($pickup->time_sensitive)
                                            <br><span class="badge bg-danger-subtle text-danger"><i class="ri-time-line me-1"></i>Time Sensitive</span>
                                        @endif
                                    </td>
                                    <td>{{ $pickup->customer->name ?? '-' }}</td>
                                    <td>
                                        <ul class="list-unstyled mb-0">
                                            @foreach($pickup->items as $item)
                                                <li>{{ $item->product->name ?? 'Unknown item' }} &times; {{ $item->quantity }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td>{{ $pickup->requested_by ?? '-' }}</td>
                                    <td>{{ $pickup->despatcher_name ?? '-' }}</td>
                                    <td>
                                        {{ $pickup->pickup_date->format('d M, Y') }}
                                        <br><small class="text-muted">{{ $pickup->pickup_time->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $pickup->statusBadge() }} text-uppercase">{{ $pickup->statusLabel() }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('pickups.show', $pickup->id) }}" class="btn btn-sm btn-soft-primary">
                                            <i class="ri-eye-line align-bottom"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No pickups found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        {{ $pickups->links('vendor.pagination.bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rangeSelect = document.getElementById('dateRangeSelect');
    const customRow = document.getElementById('customRangeRow');

    rangeSelect.addEventListener('change', function () {
        customRow.classList.toggle('d-none', rangeSelect.value !== 'custom');
    });

    if (typeof flatpickr !== 'undefined') {
        flatpickr('#dateFrom', { dateFormat: 'Y-m-d', allowInput: true });
        flatpickr('#dateTo', { dateFormat: 'Y-m-d', allowInput: true });
    }
});
</script>
@endsection
