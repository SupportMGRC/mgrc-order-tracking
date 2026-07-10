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
                @php
                    // Normalise date-like values to a clean YYYY-MM-DD HH:MM:SS for display.
                    $fmtVal = function ($v) {
                        if (is_array($v)) { return json_encode($v); }
                        if ($v === null || $v === '') { return '—'; }
                        $str = (string) $v;
                        // Detect ISO / datetime-ish strings and reformat.
                        if (preg_match('/\\d{4}-\\d{2}-\\d{2}[ T]\\d{2}:\\d{2}/', $str)) {
                            try { return \Carbon\Carbon::parse($str)->format('Y-m-d H:i:s'); }
                            catch (\Throwable $e) { return $str; }
                        }
                        return $str;
                    };
                @endphp
                <hr>
                <h5 class="mb-3">{{ $activityLog->action === 'deleted' ? 'Deleted Record Snapshot' : 'Changes' }}</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Field</th>
                                <th>{{ $activityLog->action === 'deleted' ? 'Value (before deletion)' : 'Old Value' }}</th>
                                <th>{{ $activityLog->action === 'deleted' ? 'Status' : 'New Value' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activityLog->changes as $field => $change)
                                <tr>
                                    <td><code>{{ $field }}</code></td>
                                    <td class="text-muted">{{ $fmtVal($change['old'] ?? null) }}</td>
                                    <td>{{ $fmtVal($change['new'] ?? null) }}</td>
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
