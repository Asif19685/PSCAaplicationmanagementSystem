<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'PSCA') }} Health & Monitoring System</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --psca-primary: #1a3a5c;
            --psca-primary-dark: #0f253d;
            --psca-primary-light: #254e7a;
            --psca-accent: #e8a020;
            --psca-accent-dark: #c5820f;
            --psca-bg: #f4f7fb;
            --psca-card-bg: #ffffff;
            --psca-border: #e2e8f0;
            --psca-text-dark: #1e293b;
            --psca-text-muted: #64748b;
        }

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            box-sizing: border-box;
        }

        body {
            background-color: var(--psca-bg);
            color: var(--psca-text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navigation Bar */
        .psca-navbar {
            background: linear-gradient(135deg, var(--psca-primary) 0%, var(--psca-primary-dark) 100%);
            box-shadow: 0 4px 20px rgba(15, 37, 61, 0.25);
            padding: 0.75rem 1rem;
            border-bottom: 2px solid var(--psca-accent);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: #ffffff !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            font-size: 0.92rem;
            padding: 0.5rem 0.9rem !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.12);
        }

        .nav-link.active {
            color: #ffffff !important;
            background: rgba(232, 160, 32, 0.22);
            border-bottom: 2px solid var(--psca-accent);
        }

        /* Custom Cards */
        .card-custom {
            background: var(--psca-card-bg);
            border: 1px solid var(--psca-border);
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-custom:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.07);
        }

        /* Metric Cards */
        .metric-card {
            border-radius: 14px;
            border: none;
            padding: 1.4rem;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .metric-card .metric-icon {
            font-size: 2.5rem;
            position: absolute;
            right: 18px;
            bottom: 12px;
            opacity: 0.22;
        }

        .metric-card .metric-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .metric-card .metric-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            opacity: 0.9;
        }

        .bg-gradient-navy {
            background: linear-gradient(135deg, #1a3a5c 0%, #0f253d 100%);
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #198754 0%, #115c38 100%);
        }

        .bg-gradient-danger {
            background: linear-gradient(135deg, #dc3545 0%, #991e2a 100%);
        }

        .bg-gradient-gold {
            background: linear-gradient(135deg, #e8a020 0%, #b87609 100%);
        }

        /* PSCA Primary Button */
        .btn-psca {
            background: linear-gradient(135deg, var(--psca-primary) 0%, var(--psca-primary-dark) 100%);
            color: #fff;
            border: none;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.55rem 1.2rem;
            transition: all 0.2s ease;
        }

        .btn-psca:hover {
            background: linear-gradient(135deg, var(--psca-primary-light) 0%, var(--psca-primary) 100%);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26, 58, 92, 0.3);
        }

        .btn-accent {
            background: linear-gradient(135deg, var(--psca-accent) 0%, var(--psca-accent-dark) 100%);
            color: #fff;
            border: none;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.55rem 1.2rem;
            transition: all 0.2s ease;
        }

        .btn-accent:hover {
            background: linear-gradient(135deg, #f5b033 0%, var(--psca-accent) 100%);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(232, 160, 32, 0.4);
        }

        /* Table Styling */
        .table-custom {
            margin-bottom: 0;
        }

        .table-custom th {
            background-color: #f8fafc;
            color: var(--psca-text-muted);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--psca-border);
            padding: 12px 16px;
        }

        .table-custom td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid var(--psca-border);
            font-size: 0.9rem;
        }

        .badge-status {
            font-size: 0.8rem;
            padding: 0.4em 0.75em;
            font-weight: 600;
            border-radius: 6px;
            letter-spacing: 0.3px;
        }

        .footer {
            margin-top: auto;
            background: #ffffff;
            border-top: 1px solid var(--psca-border);
            padding: 1rem 0;
            font-size: 0.85rem;
            color: var(--psca-text-muted);
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark psca-navbar sticky-top">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/psca_logo.png') }}" alt="PSCA" width="36" height="36" class="rounded-circle bg-white p-1" onerror="this.style.display='none';">
                <span>{{ config('app.name', 'PSCA') }} <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem; vertical-align: middle;">MONITOR</span></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('websites.*') ? 'active' : '' }}" href="{{ route('websites.index') }}">
                            <i class="bi bi-globe2 me-1"></i> Websites
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('monitoring.logs') ? 'active' : '' }}" href="{{ route('monitoring.logs') }}">
                            <i class="bi bi-journal-text me-1"></i> Monitoring Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('monitoring.batches*') ? 'active' : '' }}" href="{{ route('monitoring.batches') }}">
                            <i class="bi bi-collection-play me-1"></i> Batches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i> Health Reports
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <!-- Quick Manual Batch Run Button -->
                    <button type="button" class="btn btn-accent btn-sm" id="btnHeaderBatch" onclick="triggerGlobalBatch(this)">
                        <i class="bi bi-play-circle-fill me-1"></i> Run Batch Now
                    </button>

                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span>{{ Auth::user()->name ?? 'User' }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i> Profile Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i> Log Out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container-fluid px-lg-4 py-4">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                <strong><i class="bi bi-x-octagon-fill me-2"></i> Please correct the errors below:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Global Evidence / Screenshot / Log Modal -->
    <div class="modal fade" id="evidenceModal" tabindex="-1" aria-labelledby="evidenceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="evidenceModalLabel">
                        <i class="bi bi-camera-fill text-warning"></i> Monitoring Inspection & Evidence Viewer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="evidenceModalBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2">Loading inspection evidence...</p>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer text-center">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start">
                    <strong>Punjab Safe Cities Authority (PSCA)</strong> &copy; {{ date('Y') }} &middot; Web Health & Performance Monitoring System
                </div>
                <div class="col-md-6 text-md-end text-muted">
                    <span class="badge bg-secondary-subtle text-secondary border">Manual On-Demand Engine</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Global CSRF Token Setup for AJAX
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Global Evidence Modal Function
        function inspectLog(logId) {
            const modalEl = document.getElementById('evidenceModal');
            const modal = new bootstrap.Modal(modalEl);
            const modalBody = document.getElementById('evidenceModalBody');

            modalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">Loading inspection evidence & snapshot...</p>
                </div>
            `;
            modal.show();

            fetch(`/monitoring/logs/${logId}/modal`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                let badgeClass = 'bg-danger';
                if (data.status === 'up') badgeClass = 'bg-success';
                else if (data.status === 'login_failed') badgeClass = 'bg-warning text-dark';

                let consoleErrorsHtml = '<p class="text-muted small mb-0">No JavaScript console errors recorded.</p>';
                if (data.console_errors && data.console_errors.length > 0) {
                    consoleErrorsHtml = `
                        <div class="bg-dark text-light p-3 rounded-3 font-monospace small" style="max-height: 200px; overflow-y: auto;">
                            <pre class="mb-0 text-danger">${JSON.stringify(data.console_errors, null, 2)}</pre>
                        </div>
                    `;
                }

                let screenshotHtml = '<div class="alert alert-secondary text-center">No screenshot captured for this check.</div>';
                if (data.screenshot_url) {
                    screenshotHtml = `
                        <div class="text-center bg-dark p-2 rounded-3 border">
                            <img src="${data.screenshot_url}" class="img-fluid rounded border shadow-sm" alt="Evidence Snapshot" style="max-height: 420px; object-fit: contain;">
                            <div class="mt-2">
                                <a href="${data.screenshot_url}" target="_blank" class="btn btn-outline-light btn-sm">
                                    <i class="bi bi-arrows-fullscreen me-1"></i> View Full Resolution Screenshot
                                </a>
                            </div>
                        </div>
                    `;
                }

                modalBody.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                        <div>
                            <h5 class="mb-1 text-primary fw-bold">${data.website_name}</h5>
                            <a href="${data.website_url}" target="_blank" class="text-muted small text-decoration-none">
                                <i class="bi bi-box-arrow-up-right me-1"></i> ${data.website_url}
                            </a>
                        </div>
                        <div>
                            <span class="badge ${badgeClass} fs-6 px-3 py-2 text-uppercase">${data.status}</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-4">
                            <div class="card bg-light border-0 p-3 rounded-3 text-center">
                                <small class="text-muted text-uppercase fw-semibold">HTTP Status</small>
                                <span class="fs-5 fw-bold text-dark">${data.http_status_code ? data.http_status_code : 'N/A'}</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card bg-light border-0 p-3 rounded-3 text-center">
                                <small class="text-muted text-uppercase fw-semibold">Response Time</small>
                                <span class="fs-5 fw-bold text-primary">${data.response_time}s</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card bg-light border-0 p-3 rounded-3 text-center">
                                <small class="text-muted text-uppercase fw-semibold">Checked At</small>
                                <span class="small fw-semibold text-dark d-block mt-1">${data.checked_at}</span>
                            </div>
                        </div>
                    </div>

                    ${data.error_message ? `
                        <div class="alert alert-danger rounded-3 mb-4">
                            <strong><i class="bi bi-exclamation-triangle-fill me-2"></i> Error Details:</strong>
                            <p class="mb-0 mt-1">${data.error_message}</p>
                        </div>
                    ` : ''}

                    <div class="mb-4">
                        <h6 class="fw-bold text-dark"><i class="bi bi-camera me-1"></i> Screenshot & DOM Snapshot</h6>
                        ${screenshotHtml}
                    </div>

                    <div>
                        <h6 class="fw-bold text-dark"><i class="bi bi-terminal me-1"></i> JS Console & Exception Logs</h6>
                        ${consoleErrorsHtml}
                    </div>
                `;
            })
            .catch(err => {
                modalBody.innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i> Failed to load inspection details.
                    </div>
                `;
            });
        }

        // Global Batch Trigger
        function triggerGlobalBatch(btn) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Running Batch...';
            btn.disabled = true;

            fetch('{{ route("monitoring.batch.run") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({})
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert(data.message);
                window.location.reload();
            })
            .catch(err => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                alert('Batch execution failed. Please try again.');
            });
        }
    </script>

    @stack('scripts')
</body>
</html>
