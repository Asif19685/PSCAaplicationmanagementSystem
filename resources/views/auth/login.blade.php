@extends('layouts.guest')

@section('title', 'Login')
@section('header-subtitle', 'Sign in to your account')

@section('content')

    {{-- Session Status --}}
    @if (session('status'))
        <div class="auth-status">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger rounded-3 py-2 px-3 mb-3" style="font-size:0.85rem; border-left: 4px solid #dc3545;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-3">
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
                    autocomplete="username"
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

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label">
                <i class="bi bi-lock-fill me-1"></i> Password
            </label>
            <div class="input-icon-group">
                <i class="bi bi-lock input-icon"></i>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="form-control {{ $errors->get('password') ? 'is-invalid' : '' }}"
                    style="padding-right: 40px;"
                />
                <button type="button" class="pw-toggle" data-target="password" tabindex="-1">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            @if ($errors->get('password'))
                <div class="text-danger-sm mt-1">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first('password') }}
                </div>
            @endif
        </div>

        {{-- Remember Me + Forgot Password --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                <label class="form-check-label" for="remember_me">Remember me</label>
            </div>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="auth-link">
                    Forgot password?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-psca">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </div>

        {{-- Register link --}}
        @if (Route::has('register'))
        <div class="auth-footer">
            Don't have an account?
            <a href="{{ route('register') }}" class="auth-link ms-1">Register Here</a>
        </div>
        @endif

    </form>

@endsection
