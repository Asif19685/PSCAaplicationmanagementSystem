@extends('layouts.guest')

@section('title', 'Register')
@section('header-subtitle', 'Create your new account')

@section('content')

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger rounded-3 py-2 px-3 mb-3" style="font-size:0.85rem; border-left: 4px solid #dc3545;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Name --}}
        <div class="mb-3">
            <label for="name" class="form-label">
                <i class="bi bi-person-fill me-1"></i> Full Name
            </label>
            <div class="input-icon-group">
                <i class="bi bi-person input-icon"></i>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Enter your full name"
                    class="form-control {{ $errors->get('name') ? 'is-invalid' : '' }}"
                />
            </div>
            @if ($errors->get('name'))
                <div class="text-danger-sm mt-1">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first('name') }}
                </div>
            @endif
        </div>

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
                    autocomplete="new-password"
                    placeholder="Create a strong password"
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

        {{-- Confirm Password --}}
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">
                <i class="bi bi-shield-lock-fill me-1"></i> Confirm Password
            </label>
            <div class="input-icon-group">
                <i class="bi bi-shield-lock input-icon"></i>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm your password"
                    class="form-control {{ $errors->get('password_confirmation') ? 'is-invalid' : '' }}"
                    style="padding-right: 40px;"
                />
                <button type="button" class="pw-toggle" data-target="password_confirmation" tabindex="-1">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            @if ($errors->get('password_confirmation'))
                <div class="text-danger-sm mt-1">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first('password_confirmation') }}
                </div>
            @endif
        </div>

        {{-- Submit --}}
        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-psca">
                <i class="bi bi-person-plus-fill me-2"></i>Create Account
            </button>
        </div>

        {{-- Login link --}}
        <div class="auth-footer">
            Already have an account?
            <a href="{{ route('login') }}" class="auth-link ms-1">Sign in here</a>
        </div>

    </form>

@endsection
