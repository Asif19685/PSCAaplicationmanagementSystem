@extends('layouts.app')

@section('title', 'Dashboard & Real-Time Health Monitor')

@section('content')

    <!-- Dashboard Header Toolbar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-speedometer2 text-primary me-2"></i>Web Health & Performance Dashboard
            </h3>
            <p class="text-muted mb-0 small">Manual On-Demand Health Monitoring & Automated Diagnostic Testing Engine</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-accent d-flex align-items-center gap-2" id="btnDashboardBatch" onclick="triggerDashboardBatch(this)">
                <i class="bi bi-play-fill fs-5"></i>
                <span>Run All Checks Now</span>
            </button>
            <a href="{{ route('websites.create') }}" class="btn btn-psca d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle"></i>
                <span>Register Website</span>
            </a>
            <a href="{{ route('reports.create') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-plus"></i>
                <span>Generate Report</span>
            </a>
        </div>
    </div>

    <!-- 4 Key Metrics Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Websites -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card bg-gradient-navy">
                <div class="metric-label">Total Websites</div>
                <div class="metric-value mt-2" id="metricTotalSites">{{ $totalSites }}</div>
                <div class="small mt-2 opacity-75">
                    <i class="bi bi-check2-all me-1"></i> {{ $activeSites }} Active in System
                </div>
                <i class="bi bi-globe2 metric-icon"></i>
            </div>
        </div>

        <!-- Sites UP -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card bg-gradient-success">
                <div class="metric-label">Websites Operational (UP)</div>
                <div class="metric-value mt-2" id="metricSitesUp">{{ $sitesUp }}</div>
                <div class="small mt-2 opacity-75">
                    <i class="bi bi-shield-check me-1"></i> Passing Health Assertions
                </div>
                <i class="bi bi-check-circle-fill metric-icon"></i>
            </div>
        </div>

        <!-- Sites DOWN -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card bg-gradient-danger">
                <div class="metric-label">Websites In Alert / DOWN</div>
                <div class="metric-value mt-2" id="metricSitesDown">{{ $sitesDown }}</div>
                <div class="small mt-2 opacity-75">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Requiring Investigation
                </div>
                <i class="bi bi-x-octagon-fill metric-icon"></i>
            </div>
        </div>

        <!-- Average Response Time -->
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card bg-gradient-gold">
                <div class="metric-label">Avg Response Latency</div>
                <div class="metric-value mt-2" id="metricAvgLatency">{{ $avgResponseTime }}s</div>
                <div class="small mt-2 opacity-75">
                    <i class="bi bi-stopwatch me-1"></i> Network & Render SLA
                </div>
                <i class="bi bi-lightning-charge-fill metric-icon"></i>
            </div>
        </div>
    </div>

    <!-- Active Monitored Websites Table Card -->
    <div class="card card-custom mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary p-2 rounded-3"><i class="bi bi-hdd-network fs-6"></i></span>
                <h5 class="mb-0 fw-bold text-dark">Monitored Web Assets & Live Health Status</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-muted border px-3 py-2">Click "Test Now" for manual on-demand health check</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-custom table-hover align-middle mb-0" id="websitesTable">
                <thead>
                    <tr>
                        <th style="width: 130px;">Status</th>
                        <th>Website Name & Endpoint</th>
                        <th style="width: 140px;">Mode</th>
                        <th style="width: 140px;">Response Time</th>
                        <th style="width: 200px;">Last Monitored</th>
                        <th style="width: 220px;" class="text-end">Manual Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($websites as $site)
                        @php
                            $log = $site->latestLog;
                            $status = $log ? $log->status : 'pending';
                        @endphp
                        <tr id="siteRow-{{ $site->id }}">
                            <!-- Status Badge -->
                            <td class="status-cell">
                                @if(!$site->is_active)
                                    <span class="badge bg-secondary badge-status">INACTIVE</span>
                                @elseif(!$log)
                                    <span class="badge bg-secondary-subtle text-dark border badge-status">UNTESTED</span>
                                @elseif($status === 'up')
                                    <span class="badge bg-success badge-status"><i class="bi bi-check-circle me-1"></i> UP</span>
                                @elseif($status === 'login_failed')
                                    <span class="badge bg-warning text-dark badge-status"><i class="bi bi-lock me-1"></i> LOGIN FAIL</span>
                                @else
                                    <span class="badge bg-danger badge-status"><i class="bi bi-x-circle me-1"></i> DOWN</span>
                                @endif
                            </td>

                            <!-- Website Name & URL -->
                            <td>
                                <div class="fw-bold text-dark fs-6">{{ $site->name }}</div>
                                <div class="small">
                                    <a href="{{ $site->url }}" target="_blank" class="text-decoration-none text-muted">
                                        <i class="bi bi-link-45deg"></i> {{ Str::limit($site->url, 55) }}
                                    </a>
                                </div>
                            </td>

                            <!-- Mode -->
                            <td>
                                @if($site->requires_login)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-key-fill me-1"></i> Automated Login
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border">
                                        <i class="bi bi-activity me-1"></i> HTTP / Ping
                                    </span>
                                @endif
                            </td>

                            <!-- Response Time -->
                            <td class="latency-cell">
                                @if($log && $log->total_response_time_seconds > 0)
                                    <span class="fw-semibold text-primary font-monospace">{{ $log->total_response_time_seconds }}s</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>

                            <!-- Last Checked -->
                            <td class="last-checked-cell small text-muted">
                                @if($log)
                                    <div><i class="bi bi-clock me-1"></i> {{ $log->checked_at->format('d M Y, h:i:s A') }}</div>
                                @else
                                    <span>Not tested yet</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <!-- AJAX Single Test Now Button -->
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary btn-test-single d-flex align-items-center gap-1"
                                        onclick="runSingleSiteTest({{ $site->id }}, this)"
                                        title="Execute On-Demand Manual Health Check"
                                    >
                                        <i class="bi bi-play-circle"></i>
                                        <span>Test Now</span>
                                    </button>

                                    <!-- Inspect Evidence / Modal Button -->
                                    @if($log)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-dark btn-inspect"
                                            onclick="inspectLog({{ $log->id }})"
                                            title="View Snapshot Evidence & Logs"
                                        >
                                            <i class="bi bi-camera"></i>
                                        </button>
                                    @endif

                                    <!-- Edit Link -->
                                    <a href="{{ route('websites.edit', $site) }}" class="btn btn-sm btn-light border text-muted" title="Edit Site Configuration">
                                        <i class="bi bi-gear"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-globe2 fs-1 d-block mb-3 text-secondary opacity-50"></i>
                                    <h6 class="fw-bold">No Websites Registered Yet</h6>
                                    <p class="small mb-3">Add your internal and external web portals to begin health and performance monitoring.</p>
                                    <a href="{{ route('websites.create') }}" class="btn btn-psca btn-sm">
                                        <i class="bi bi-plus-circle me-1"></i> Register First Website
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Lower Section: Recent Logs Feed & Recent Batches Overview -->
    <div class="row g-4">
        <!-- Recent Logs Feed -->
        <div class="col-lg-7">
            <div class="card card-custom h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary-subtle text-dark p-2 rounded-3"><i class="bi bi-journal-check fs-6"></i></span>
                        <h6 class="mb-0 fw-bold text-dark">Recent Monitoring Checks Activity</h6>
                    </div>
                    <a href="{{ route('monitoring.logs') }}" class="btn btn-sm btn-outline-primary">
                        View All Logs <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Target Website</th>
                                <th>Latency</th>
                                <th>Time</th>
                                <th class="text-end">Evidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $rLog)
                                <tr>
                                    <td>
                                        @if($rLog->status === 'up')
                                            <span class="badge bg-success badge-status">UP</span>
                                        @elseif($rLog->status === 'login_failed')
                                            <span class="badge bg-warning text-dark badge-status">LOGIN FAIL</span>
                                        @else
                                            <span class="badge bg-danger badge-status">DOWN</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $rLog->website?->name ?? 'Deleted Site' }}</div>
                                    </td>
                                    <td class="font-monospace text-primary small">
                                        {{ $rLog->total_response_time_seconds }}s
                                    </td>
                                    <td class="small text-muted">
                                        {{ $rLog->checked_at->diffForHumans() }}
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="inspectLog({{ $rLog->id }})">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted small">No recent logs recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Batches Overview -->
        <div class="col-lg-5">
            <div class="card card-custom h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning-subtle text-warning p-2 rounded-3"><i class="bi bi-collection-play fs-6"></i></span>
                        <h6 class="mb-0 fw-bold text-dark">Recent Batch Executions</h6>
                    </div>
                    <a href="{{ route('monitoring.batches') }}" class="btn btn-sm btn-outline-primary">
                        All Batches <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentBatches as $batch)
                            <div class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <a href="{{ route('monitoring.batch.detail', $batch->batch_id) }}" class="fw-bold text-decoration-none text-primary font-monospace small">
                                        <i class="bi bi-folder-check me-1"></i> {{ $batch->batch_id }}
                                    </a>
                                    <span class="badge bg-light text-muted border small">{{ $batch->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between small text-muted mt-2">
                                    <div>
                                        <span class="badge bg-success-subtle text-success me-1">{{ $batch->successful }} UP</span>
                                        <span class="badge bg-danger-subtle text-danger">{{ $batch->failed }} Failed</span>
                                    </div>
                                    <div>
                                        Total: <strong class="text-dark">{{ $batch->total_websites }} Sites</strong>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted small">
                                No manual batches executed yet. Click "Run All Checks Now" to execute the first batch.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Manual On-Demand Single Website Test via AJAX
    function runSingleSiteTest(siteId, btn) {
        const row = document.getElementById(`siteRow-${siteId}`);
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testing...';
        btn.disabled = true;

        fetch(`/monitoring/test/${siteId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ force_screenshot: true })
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;

            if (data.success && data.log) {
                const log = data.log;

                // Update Status Cell
                const statusCell = row.querySelector('.status-cell');
                let badgeClass = 'bg-danger';
                let iconClass = 'bi-x-circle';
                let statusText = 'DOWN';

                if (log.status === 'up') {
                    badgeClass = 'bg-success';
                    iconClass = 'bi-check-circle';
                    statusText = 'UP';
                } else if (log.status === 'login_failed') {
                    badgeClass = 'bg-warning text-dark';
                    iconClass = 'bi-lock';
                    statusText = 'LOGIN FAIL';
                }

                statusCell.innerHTML = `<span class="badge ${badgeClass} badge-status"><i class="bi ${iconClass} me-1"></i> ${statusText}</span>`;

                // Update Latency Cell
                const latencyCell = row.querySelector('.latency-cell');
                latencyCell.innerHTML = `<span class="fw-semibold text-primary font-monospace">${log.response_time}s</span>`;

                // Update Last Checked Cell
                const lastCheckedCell = row.querySelector('.last-checked-cell');
                lastCheckedCell.innerHTML = `<div><i class="bi bi-clock me-1"></i> ${log.checked_at}</div>`;

                // Add or update inspect button
                let inspectBtn = row.querySelector('.btn-inspect');
                if (!inspectBtn) {
                    const actionContainer = row.querySelector('.d-inline-flex');
                    const newInspect = document.createElement('button');
                    newInspect.type = 'button';
                    newInspect.className = 'btn btn-sm btn-outline-dark btn-inspect';
                    newInspect.title = 'View Snapshot Evidence & Logs';
                    newInspect.innerHTML = '<i class="bi bi-camera"></i>';
                    newInspect.onclick = () => inspectLog(log.id);
                    actionContainer.insertBefore(newInspect, actionContainer.children[1]);
                } else {
                    inspectBtn.onclick = () => inspectLog(log.id);
                }

                // Show toast or prompt inspection
                if (log.status !== 'up') {
                    inspectLog(log.id);
                }
            }
        })
        .catch(err => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            alert('Failed to execute monitoring check. Please try again.');
        });
    }

    // Dashboard Batch Trigger
    function triggerDashboardBatch(btn) {
        triggerGlobalBatch(btn);
    }
</script>
@endpush
