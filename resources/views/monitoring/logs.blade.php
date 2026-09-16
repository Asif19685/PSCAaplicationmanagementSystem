@extends('layouts.app')

@section('title', 'Monitoring Logs & Diagnostic History')

@section('content')

    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-journal-text text-primary me-2"></i>Monitoring Checks History & Logs
            </h3>
            <p class="text-muted mb-0 small">Audit trail of automated and manual diagnostic assertions, performance, and failure snapshots</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-accent btn-sm" onclick="triggerGlobalBatch(this)">
                <i class="bi bi-play-circle-fill me-1"></i> Run Batch Now
            </button>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card card-custom mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('monitoring.logs') }}" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Target Website</label>
                    <select name="website_id" class="form-select form-select-sm">
                        <option value="">All Websites</option>
                        @foreach($websites as $w)
                            <option value="{{ $w->id }}" {{ request('website_id') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Health Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="up" {{ request('status') === 'up' ? 'selected' : '' }}>UP (Operational)</option>
                        <option value="down" {{ request('status') === 'down' ? 'selected' : '' }}>DOWN (Error)</option>
                        <option value="login_failed" {{ request('status') === 'login_failed' ? 'selected' : '' }}>Login Failed</option>
                        <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Assertion Error</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Batch Identifier</label>
                    <input type="text" name="batch_id" class="form-control form-control-sm" placeholder="e.g. BATCH_2026..." value="{{ request('batch_id') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table Card -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-custom table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 70px;">Log ID</th>
                        <th style="width: 140px;">Status</th>
                        <th>Target Website</th>
                        <th style="width: 110px;">HTTP Code</th>
                        <th style="width: 130px;">Response Time</th>
                        <th style="width: 180px;">Batch ID</th>
                        <th style="width: 200px;">Checked Timestamp</th>
                        <th style="width: 130px;" class="text-end">Evidence</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-muted small font-monospace">#{{ $log->id }}</td>
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
                                <div class="fw-bold text-dark">{{ $log->website?->name ?? 'Unknown Site' }}</div>
                                <div class="small text-muted text-truncate" style="max-width: 280px;">
                                    {{ $log->website?->url ?? '#' }}
                                </div>
                                @if($log->error_message)
                                    <div class="text-danger small mt-1 text-truncate" style="max-width: 320px;" title="{{ $log->error_message }}">
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
                            <td>
                                @if($log->batch_id)
                                    <a href="{{ route('monitoring.batch.detail', $log->batch_id) }}" class="small font-monospace text-decoration-none">
                                        {{ Str::limit($log->batch_id, 18) }}
                                    </a>
                                @else
                                    <span class="badge bg-light text-muted border small">Single Check</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                <i class="bi bi-calendar3 me-1"></i> {{ $log->checked_at->format('d M Y, h:i:s A') }}
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1" onclick="inspectLog({{ $log->id }})">
                                    <i class="bi bi-camera"></i> Inspect
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-50"></i>
                                No monitoring logs found matching your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

@endsection
