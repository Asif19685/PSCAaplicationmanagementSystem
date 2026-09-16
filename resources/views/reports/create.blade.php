@extends('layouts.app')

@section('title', 'Generate New Health Report')

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <i class="bi bi-file-earmark-plus text-primary me-2"></i>Generate SLA & Health Report
                    </h3>
                    <p class="text-muted mb-0 small">Select a date range to aggregate monitoring checks, uptime percentages, and response latencies</p>
                </div>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Reports
                </a>
            </div>

            <div class="card card-custom mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-sliders me-2"></i>Report Parameters
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('reports.store') }}">
                        @csrf

                        <!-- Report Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label fw-semibold">Report Title <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="title"
                                name="title"
                                value="{{ old('title', 'PSCA Web Portals Health Report - ' . date('F Y')) }}"
                                placeholder="e.g. Monthly Uptime & Performance Audit"
                                required
                            >
                        </div>

                        <!-- Quick Date Presets -->
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold">Quick Date Presets:</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreset('today')">Today</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreset('last7')">Last 7 Days</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreset('last30')">Last 30 Days</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreset('thisMonth')">This Month</button>
                            </div>
                        </div>

                        <!-- Date Range Inputs -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="period_from" class="form-label fw-semibold">Period From <span class="text-danger">*</span></label>
                                <input
                                    type="date"
                                    class="form-control"
                                    id="period_from"
                                    name="period_from"
                                    value="{{ old('period_from', date('Y-m-01')) }}"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="period_to" class="form-label fw-semibold">Period To <span class="text-danger">*</span></label>
                                <input
                                    type="date"
                                    class="form-control"
                                    id="period_to"
                                    name="period_to"
                                    value="{{ old('period_to', date('Y-m-d')) }}"
                                    required
                                >
                            </div>
                        </div>

                        <div class="alert alert-info border-0 rounded-3 small mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i> Generating a report will scan all recorded monitoring logs between the selected dates and generate structured metrics per website in the database.
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('reports.index') }}" class="btn btn-light border px-4">Cancel</a>
                            <button type="submit" class="btn btn-psca px-4">
                                <i class="bi bi-bar-chart-line-fill me-1"></i> Generate Report
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function setPreset(type) {
        const fromInput = document.getElementById('period_from');
        const toInput = document.getElementById('period_to');
        const today = new Date();

        function formatDate(d) {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        toInput.value = formatDate(today);

        if (type === 'today') {
            fromInput.value = formatDate(today);
        } else if (type === 'last7') {
            const d = new Date();
            d.setDate(d.getDate() - 7);
            fromInput.value = formatDate(d);
        } else if (type === 'last30') {
            const d = new Date();
            d.setDate(d.getDate() - 30);
            fromInput.value = formatDate(d);
        } else if (type === 'thisMonth') {
            const d = new Date(today.getFullYear(), today.getMonth(), 1);
            fromInput.value = formatDate(d);
        }
    }
</script>
@endpush
