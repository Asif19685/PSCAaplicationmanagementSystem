@extends('layouts.app')

@section('title', 'Batch Details: ' . $batch->batch_id)

@section('content')

    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="small text-muted mb-1">
                <a href="{{ route('monitoring.batches') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-arrow-left me-1"></i> Back to Batches
                </a>
            </div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-folder-check text-primary me-2"></i>Batch Execution: <span class="font-monospace">{{ $batch->batch_id }}</span>
            </h3>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-light text-dark border p-2 px-3 small">
                Triggered by: <strong>{{ $batch->user?->name ?? 'System' }}</strong> &middot; {{ $batch->created_at->format('d M Y, h:i:s A') }}
            </span>
        </div>
    </div>

    <!-- Batch Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-white border p-3 rounded-3 shadow-sm text-center">
                <div class="small text-muted text-uppercase fw-semibold">Total Sites Tested</div>
                <div class="fs-2 fw-bold text-dark mt-1">{{ $batch->total_websites }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success-subtle border border-success-subtle p-3 rounded-3 shadow-sm text-center">
                <div class="small text-success text-uppercase fw-semibold">Websites Operational (UP)</div>
                <div class="fs-2 fw-bold text-success mt-1">{{ $batch->successful }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger-subtle border border-danger-subtle p-3 rounded-3 shadow-sm text-center">
                <div class="small text-danger text-uppercase fw-semibold">Websites Failed / Down</div>
                <div class="fs-2 fw-bold text-danger mt-1">{{ $batch->failed }}</div>
            </div>
        </div>
    </div>

    <!-- Batch Results Table Card -->
    <div class="card card-custom">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold text-dark">Website Diagnostics in This Batch</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-custom table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 140px;">Status</th>
                        <th>Target Website</th>
                        <th style="width: 120px;">HTTP Code</th>
                        <th style="width: 150px;">Response Time</th>
                        <th style="width: 200px;">Checked Timestamp</th>
                        <th style="width: 130px;" class="text-end">Evidence</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                @if($log->status === 'up')
                                    <span class="badge bg-success badge-status"><i class="bi bi-check-circle me-1"></i> UP</span>
                                @elseif($log->status === 'login_failed')
                                    <span class="badge bg-warning text-dark badge-status"><i class="bi bi-lock me-1"></i> LOGIN FAIL</span>
                                @else
                                    <span class="badge bg-danger badge-status"><i class="bi bi-x-circle me-1"></i> DOWN</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $log->website?->name ?? 'Unknown' }}</div>
                                <div class="small text-muted">{{ $log->website?->url ?? '#' }}</div>
                                @if($log->error_message)
                                    <div class="text-danger small mt-1">
                                        <i class="bi bi-exclamation-circle me-1"></i> {{ $log->error_message }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($log->http_status_code)
                                    <span class="badge bg-light text-dark border font-monospace">{{ $log->http_status_code }}</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold font-monospace text-primary">{{ $log->total_response_time_seconds }}s</span>
                            </td>
                            <td class="small text-muted">
                                {{ $log->checked_at->format('d M Y, h:i:s A') }}
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-dark" onclick="inspectLog({{ $log->id }})">
                                    <i class="bi bi-camera me-1"></i> Inspect
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No individual logs found for this batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
