@extends('layouts.guest')

@section('title', 'Forgot Password')
@section('header-subtitle', 'Reset your account password')

@section('content')

    {{-- Session Status --}}
    @if (session('status'))
        <div class="auth-status">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
        </div>
    @endif

    <p class="text-center mb-4" style="font-size:0.88rem; color:#6b7a8d; line-height:1.6;">
        Forgot your password? No problem — enter your email and we'll send you a reset link.
    </p>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger rounded-3 py-2 px-3 mb-3" style="font-size:0.85rem; border-left: 4px solid #dc3545;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-4">
            <label for="email" class="form-label">
                <i class="bi bi-envelope-fill me-1"></i> Email Address
            </label>
            <div class="input-icon-group">
                <i class="bi bi-envelope input-icon"></i>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    placeholder="you@example.com"
                    class="form-control {{ $errors->get('email') ? 'is-invalid' : '' }}"
                />
            </div>
            @if ($errors->get('email'))
                <div class="text-danger-sm mt-1">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first('email') }}
                </div>
            @endif
        </div>

        {{-- Submit --}}
        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-psca">
                <i class="bi bi-send-fill me-2"></i>Email Password Reset Link
            </button>
        </div>

        <div class="auth-footer">
            Remember your password?
            <a href="{{ route('login') }}" class="auth-link ms-1">Back to Sign In</a>
        </div>
    </form>

@endsection
