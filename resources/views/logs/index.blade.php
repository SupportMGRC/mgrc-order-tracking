@extends('layouts.master')

@section('content')

    {{-- Safety net: keep any pagination icons a sane size --}}
    <style>
        .activity-log-pagination svg { width: 1rem; height: 1rem; }
        .activity-log-pagination .pagination { flex-wrap: wrap; }
    </style>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Activity Logs</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Activity Logs</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('logs.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Record, description or user">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All</option>
                        <option value="created" @selected(request('action')=='created')>Created</option>
                        <option value="updated" @selected(request('action')=='updated')>Updated</option>
                        <option value="deleted" @selected(request('action')=='deleted')>Deleted</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Module</label>
                    <select name="model" class="form-select">
                        <option value="">All</option>
                        @foreach($models as $m)
                            <option value="{{ $m }}" @selected(request('model')==$m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">User</label>
                    <select name="user_id" class="form-select">
                        <option value="">All</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected(request('user_id')==$u->id)>{{ $u->username }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label mb-1">From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label mb-1">To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-primary"><i class="ri-search-line align-bottom me-1"></i> Filter</button>
                    <a href="{{ route('logs.index') }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date / Time</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Record</th>
                            <th class="text-end">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                <td>{{ $log->user_name ?? 'System' }}</td>
                                <td><span class="text-muted">{{ $log->user_role ?? '-' }}</span></td>
                                <td><span class="badge bg-{{ $log->action_color }}">{{ ucfirst($log->action) }}</span></td>
                                <td>{{ $log->model_name }}</td>
                                <td>{{ $log->subject_label }}</td>
                                <td class="text-end">
                                    @if($log->changes)
                                        <a href="{{ route('logs.show', $log) }}" class="btn btn-sm btn-soft-info">View</a>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No activity recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 activity-log-pagination">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
@endsection
