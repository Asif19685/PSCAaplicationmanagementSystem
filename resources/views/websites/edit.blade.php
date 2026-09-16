@extends('layouts.app')

@section('title', 'Edit Website: ' . $website->name)

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Web Asset Configuration
                    </h3>
                    <p class="text-muted mb-0 small">Updating settings for <strong>{{ $website->name }}</strong> (#{{ $website->id }})</p>
                </div>
                <a href="{{ route('websites.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
            </div>

            <form method="POST" action="{{ route('websites.update', $website) }}">
                @csrf
                @method('PUT')

                <!-- Basic Configuration Card -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-globe me-2"></i>General Target Endpoint
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">Website / Application Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $website->name) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="url" class="form-label fw-semibold">Target URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="url" name="url" value="{{ old('url', $website->url) }}" required>
                            </div>

                            <div class="col-md-8">
                                <label for="expected_text" class="form-label fw-semibold">Expected Confirmation Text (Optional Assertion)</label>
                                <input type="text" class="form-control" id="expected_text" name="expected_text" value="{{ old('expected_text', $website->expected_text) }}">
                                <small class="text-muted">The monitor asserts that this text must appear on the page DOM.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold d-block">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $website->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="is_active">Active Monitoring</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Authentication & Automated Login Testing Card -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-shield-lock me-2"></i>Automated Form Login Testing (Encrypted)
                        </h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="requires_login" name="requires_login" value="1" {{ old('requires_login', $website->requires_login) ? 'checked' : '' }} onchange="toggleLoginFields()">
                            <label class="form-check-label fw-bold" for="requires_login">Requires Authentication</label>
                        </div>
                    </div>
                    <div class="card-body p-4" id="loginFieldsContainer" style="{{ old('requires_login', $website->requires_login) ? '' : 'display:none;' }}">
                        <div class="alert alert-info border-0 rounded-3 mb-4 small">
                            <i class="bi bi-info-circle-fill me-2"></i> Password is encrypted securely. Leave the password field blank to retain the current password.
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="login_url" class="form-label fw-semibold">Login Page URL</label>
                                <input type="url" class="form-control" id="login_url" name="login_url" value="{{ old('login_url', $website->login_url) }}" placeholder="https://example.gov.pk/login">
                            </div>

                            <div class="col-md-6">
                                <label for="username" class="form-label fw-semibold">Login Username / Email</label>
                                <input type="text" class="form-control" id="username" name="username" value="{{ old('username', $website->decrypted_username) }}" placeholder="admin@psca.gop.pk">
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">Login Password (Leave empty to keep existing)</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="•••••••• (Unchanged)">
                            </div>

                            <div class="col-md-4">
                                <label for="username_field" class="form-label fw-semibold">Username Field Selector</label>
                                <input type="text" class="form-control" id="username_field" name="username_field" value="{{ old('username_field', $website->username_field) }}" placeholder="#email or #username">
                            </div>

                            <div class="col-md-4">
                                <label for="password_field" class="form-label fw-semibold">Password Field Selector</label>
                                <input type="text" class="form-control" id="password_field" name="password_field" value="{{ old('password_field', $website->password_field) }}" placeholder="#password">
                            </div>

                            <div class="col-md-4">
                                <label for="submit_button" class="form-label fw-semibold">Submit Button Selector</label>
                                <input type="text" class="form-control" id="submit_button" name="submit_button" value="{{ old('submit_button', $website->submit_button) }}" placeholder="#submit or button[type='submit']">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end gap-2 mb-5">
                    <a href="{{ route('websites.index') }}" class="btn btn-light border px-4">Cancel</a>
                    <button type="submit" class="btn btn-psca px-4">
                        <i class="bi bi-check-circle me-1"></i> Update Website Settings
                    </button>
                </div>

            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function toggleLoginFields() {
        const check = document.getElementById('requires_login');
        const container = document.getElementById('loginFieldsContainer');
        if (check.checked) {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    }
</script>
@endpush
