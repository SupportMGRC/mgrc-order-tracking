@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Activity Detail</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('logs.index') }}">Activity Logs</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3"><strong>Date / Time</strong></div>
                <div class="col-md-9">{{ $activityLog->created_at->format('d M Y, h:i:s A') }}</div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3"><strong>User</strong></div>
                <div class="col-md-9">{{ $activityLog->user_name ?? 'System' }} ({{ $activityLog->user_role ?? '-' }})</div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3"><strong>Action</strong></div>
                <div class="col-md-9"><span class="badge bg-{{ $activityLog->action_color }}">{{ ucfirst($activityLog->action) }}</span></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3"><strong>Record</strong></div>
                <div class="col-md-9">{{ $activityLog->subject_label }}</div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3"><strong>IP Address</strong></div>
                <div class="col-md-9">{{ $activityLog->ip_address ?? '-' }}</div>
            </div>

            @if($activityLog->changes)
                <hr>
                <h5 class="mb-3">Changes</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Field</th>
                                <th>Old Value</th>
                                <th>New Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activityLog->changes as $field => $change)
                                <tr>
                                    <td><code>{{ $field }}</code></td>
                                    <td class="text-muted">{{ is_array($change['old'] ?? null) ? json_encode($change['old']) : ($change['old'] ?? '—') }}</td>
                                    <td>{{ is_array($change['new'] ?? null) ? json_encode($change['new']) : ($change['new'] ?? '—') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="mt-4">
                <a href="{{ route('logs.index') }}" class="btn btn-light">&larr; Back to logs</a>
            </div>
        </div>
    </div>
@endsection
