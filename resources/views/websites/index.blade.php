@extends('layouts.app')

@section('title', 'Website Asset Management')

@section('content')

    <!-- Header & Action Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-globe2 text-primary me-2"></i>Monitored Web Portals & Asset Registry
            </h3>
            <p class="text-muted mb-0 small">Manage endpoints, encrypted credentials, form selectors, and test targets</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('websites.create') }}" class="btn btn-psca d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle"></i>
                <span>Register New Website</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card card-custom mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('websites.index') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name or URL..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses (Active & Inactive)</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Check Modes</option>
                        <option value="http" {{ request('type') === 'http' ? 'selected' : '' }}>HTTP / Ping</option>
                        <option value="login" {{ request('type') === 'login' ? 'selected' : '' }}>Automated Login</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    <a href="{{ route('websites.index') }}" class="btn btn-sm btn-light border" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Websites Table Card -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-custom table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Website Name & Target URL</th>
                        <th style="width: 150px;">Check Mode</th>
                        <th style="width: 140px;">Active State</th>
                        <th style="width: 140px;">Latest Health</th>
                        <th style="width: 160px;">Total Checks</th>
                        <th style="width: 240px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($websites as $site)
                        @php $lastLog = $site->latestLog; @endphp
                        <tr>
                            <td class="text-muted small">#{{ $site->id }}</td>
                            <td>
                                <div class="fw-bold text-dark fs-6">{{ $site->name }}</div>
                                <div class="small">
                                    <a href="{{ $site->url }}" target="_blank" class="text-decoration-none text-muted">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> {{ $site->url }}
                                    </a>
                                </div>
                                @if($site->expected_text)
                                    <div class="small text-muted mt-1">
                                        <span class="badge bg-light text-secondary border">Assert: "{{ Str::limit($site->expected_text, 35) }}"</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($site->requires_login)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-shield-lock me-1"></i> Form Login
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border">
                                        <i class="bi bi-activity me-1"></i> HTTP / Ping
                                    </span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('websites.toggle', $site) }}">
                                    @csrf
                                    @method('PATCH')
                                    @if($site->is_active)
                                        <button type="submit" class="badge bg-success-subtle text-success border border-success-subtle btn p-1 px-2" title="Click to Deactivate">
                                            <i class="bi bi-toggle-on me-1"></i> Active
                                        </button>
                                    @else
                                        <button type="submit" class="badge bg-secondary-subtle text-muted border btn p-1 px-2" title="Click to Activate">
                                            <i class="bi bi-toggle-off me-1"></i> Inactive
                                        </button>
                                    @endif
                                </form>
                            </td>
                            <td>
                                @if(!$lastLog)
                                    <span class="badge bg-light text-muted border badge-status">UNTESTED</span>
                                @elseif($lastLog->status === 'up')
                                    <span class="badge bg-success badge-status"><i class="bi bi-check-circle me-1"></i> UP</span>
                                @elseif($lastLog->status === 'login_failed')
                                    <span class="badge bg-warning text-dark badge-status"><i class="bi bi-lock me-1"></i> LOGIN FAIL</span>
                                @else
                                    <span class="badge bg-danger badge-status"><i class="bi bi-x-circle me-1"></i> DOWN</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $site->logs_count }} checks</span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <!-- Test Now -->
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="runSiteTestFromIndex({{ $site->id }}, this)"
                                        title="Trigger Manual Test"
                                    >
                                        <i class="bi bi-play-circle"></i> Test
                                    </button>

                                    <!-- Inspect Evidence -->
                                    @if($lastLog)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-dark"
                                            onclick="inspectLog({{ $lastLog->id }})"
                                            title="View Evidence & Console Logs"
                                        >
                                            <i class="bi bi-camera"></i>
                                        </button>
                                    @endif

                                    <!-- Edit -->
                                    <a href="{{ route('websites.edit', $site) }}" class="btn btn-sm btn-outline-secondary" title="Edit Site Details">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <!-- Delete -->
                                    <form method="POST" action="{{ route('websites.destroy', $site) }}" onsubmit="return confirm('Are you sure you want to delete website \'{{ $site->name }}\'?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Website">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-2 opacity-50"></i>
                                No websites matching the selected filters found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($websites->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $websites->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

@endsection

@push('scripts')
<script>
    function runSiteTestFromIndex(siteId, btn) {
        const orig = btn.innerHTML;
        btn.disabled = true;

        // Live elapsed timer
        let elapsed = 0;
        const timerInterval = setInterval(() => {
            elapsed++;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Testing... ${elapsed}s`;
        }, 1000);
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testing... 0s';

        // AbortController to allow up to 120 seconds (Chrome login takes 15-25s)
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 120000);

        fetch(`/monitoring/test/${siteId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ force_screenshot: true }),
            signal: controller.signal
        })
        .then(res => res.json())
        .then(data => {
            clearTimeout(timeoutId);
            clearInterval(timerInterval);
            btn.innerHTML = orig;
            btn.disabled = false;

            if (data.success && data.log) {
                inspectLog(data.log.id);
            }
        })
        .catch(err => {
            clearTimeout(timeoutId);
            clearInterval(timerInterval);
            btn.innerHTML = orig;
            btn.disabled = false;
            if (err.name === 'AbortError') {
                alert('Test timed out after 120 seconds.');
            } else {
                alert('Test execution failed: ' + err.message);
            }
        });
    }
</script>
@endpush
