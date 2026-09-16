@extends('layouts.app')

@section('title', 'Monitoring Batches Registry')

@section('content')

    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-collection-play text-primary me-2"></i>Monitoring Execution Batches
            </h3>
            <p class="text-muted mb-0 small">Audit trail of batch execution runs, overall success ratios, and triggering users</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-accent btn-sm" onclick="triggerGlobalBatch(this)">
                <i class="bi bi-play-circle-fill me-1"></i> Run New Batch
            </button>
        </div>
    </div>

    <!-- Batches Table Card -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-custom table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Batch Identifier</th>
                        <th style="width: 130px;">Trigger</th>
                        <th style="width: 140px;">Triggered By</th>
                        <th style="width: 120px;">Total Sites</th>
                        <th style="width: 180px;">Execution Result</th>
                        <th style="width: 200px;">Triggered At</th>
                        <th style="width: 120px;" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>
                                <a href="{{ route('monitoring.batch.detail', $batch->batch_id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                                    <i class="bi bi-folder-check me-1"></i> {{ $batch->batch_id }}
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border text-uppercase">{{ $batch->trigger_type }}</span>
                            </td>
                            <td class="small">
                                <i class="bi bi-person me-1 text-muted"></i> {{ $batch->user?->name ?? 'System' }}
                            </td>
                            <td>
                                <span class="fw-bold text-dark">{{ $batch->total_websites }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check-circle me-1"></i> {{ $batch->successful }} UP
                                    </span>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="bi bi-x-circle me-1"></i> {{ $batch->failed }} Failed
                                    </span>
                                </div>
                            </td>
                            <td class="small text-muted">
                                <i class="bi bi-clock me-1"></i> {{ $batch->created_at->format('d M Y, h:i:s A') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('monitoring.batch.detail', $batch->batch_id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Logs
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-folder-x fs-1 d-block mb-2 opacity-50"></i>
                                No batch runs found. Click "Run New Batch" to dispatch monitoring checks across all active websites.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $batches->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

@endsection
