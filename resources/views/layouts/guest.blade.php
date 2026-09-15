<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PSCA') }} - @yield('title', 'Welcome')</title>

    <!-- Google Fonts -->
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
            --psca-primary-dark: #102840;
            --psca-accent: #e8a020;
            --psca-border: #dce3ec;
            --psca-text-muted: #6b7a8d;
            --psca-bg-input: #f8fafc;
        }

        * { font-family: 'Inter', sans-serif; box-sizing: border-box; }

        html {
            background-color: #081220;
        }

        body {
            background-color: #081220;
            background-image: url('/images/psca_bg.jpg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            margin: 0;
            padding: 0;
        }

        /* Dark overlay on background image */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(140deg,
                rgba(8, 18, 32, 0.78) 0%,
                rgba(13, 33, 60, 0.70) 50%,
                rgba(8, 18, 32, 0.82) 100%);
            z-index: 0;
            pointer-events: none;
        }

        /* Ambient glow circles */
        .bg-circle-1 {
            position: fixed; top: -15%; right: -8%;
            width: 550px; height: 550px;
            background: radial-gradient(circle, rgba(232,160,32,0.12) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none; z-index: 0;
        }
        .bg-circle-2 {
            position: fixed; bottom: -15%; left: -8%;
            width: 450px; height: 450px;
            background: radial-gradient(circle, rgba(29,79,128,0.45) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none; z-index: 0;
        }
        .bg-dots {
            position: fixed; top: 12%; left: 4%;
            opacity: 0.1; pointer-events: none; z-index: 0;
        }
        .bg-dots-2 {
            position: fixed; bottom: 12%; right: 4%;
            opacity: 0.08; pointer-events: none; z-index: 0;
        }

        /* Auth wrapper */
        .auth-wrapper {
            position: relative; z-index: 10;
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }

        /* Auth card - clean without flashing zero-opacity animations */
        .auth-card {
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 30px 70px rgba(0,0,0,0.38), 0 10px 30px rgba(0,0,0,0.22);
            width: 100%;
            max-width: 470px;
            overflow: hidden;
        }

        /* Card header */
        .auth-card-header {
            background: linear-gradient(135deg, var(--psca-primary) 0%, var(--psca-primary-dark) 100%);
            padding: 36px 40px 42px;
            text-align: center;
            position: relative;
        }
        .auth-card-header::after {
            content: '';
            position: absolute;
            bottom: -1px; left: 0; right: 0;
            height: 32px;
            background: #fff;
            border-radius: 32px 32px 0 0;
        }

        .auth-logo-wrap {
            width: 80px; height: 80px;
            background: rgba(255,255,255,0.95);
            border: 3px solid rgba(232,160,32,0.7);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            box-shadow: 0 4px 20px rgba(232,160,32,0.3);
            padding: 8px;
        }
        .auth-logo-wrap svg { width: 42px; height: 42px; fill: #e8a020; }
        .auth-logo-wrap img { width: 58px; height: 58px; object-fit: contain; }

        .auth-card-header h1 {
            color: #fff;
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: -0.3px;
            margin-bottom: 4px;
        }
        .auth-card-header p {
            color: rgba(255,255,255,0.68);
            font-size: 0.85rem;
            margin-bottom: 0;
        }

        /* Card body */
        .auth-card-body {
            padding: 28px 40px 42px;
        }

        /* Form elements */
        .form-label {
            font-weight: 600;
            font-size: 0.78rem;
            color: var(--psca-primary);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 6px;
        }

        .form-control {
            border: 1.5px solid var(--psca-border);
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.9rem;
            color: #2d3748;
            background-color: var(--psca-bg-input);
            transition: all 0.25s ease;
        }
        .form-control:focus {
            border-color: var(--psca-primary);
            box-shadow: 0 0 0 3px rgba(26,58,92,0.12);
            background-color: #fff;
            outline: none;
        }
        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .input-icon-group { position: relative; }
        .input-icon-group .input-icon {
            position: absolute;
            left: 13px; top: 50%; transform: translateY(-50%);
            color: var(--psca-text-muted);
            font-size: 1rem;
            pointer-events: none;
        }
        .input-icon-group .form-control {
            padding-left: 38px;
        }
        .input-icon-group .pw-toggle {
            position: absolute;
            right: 13px; top: 50%; transform: translateY(-50%);
            background: none; border: none; padding: 0;
            color: var(--psca-text-muted);
            cursor: pointer; font-size: 1rem;
            transition: color 0.2s;
        }
        .input-icon-group .pw-toggle:hover { color: var(--psca-primary); }

        /* Submit button */
        .btn-psca {
            background: linear-gradient(135deg, var(--psca-primary) 0%, var(--psca-primary-dark) 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            transition: all 0.28s ease;
            box-shadow: 0 4px 16px rgba(26,58,92,0.35);
        }
        .btn-psca:hover {
            background: linear-gradient(135deg, var(--psca-primary-dark) 0%, #081926 100%);
            transform: translateY(-2px);
            box-shadow: 0 7px 22px rgba(26,58,92,0.45);
            color: #fff;
        }
        .btn-psca:active { transform: translateY(0); }

        /* Checkbox */
        .form-check-input {
            border: 1.5px solid var(--psca-border);
            border-radius: 5px;
            width: 17px; height: 17px;
        }
        .form-check-input:checked {
            background-color: var(--psca-primary);
            border-color: var(--psca-primary);
        }
        .form-check-label {
            font-size: 0.875rem;
            color: var(--psca-text-muted);
            font-weight: 500;
        }

        /* Links */
        .auth-link {
            color: var(--psca-primary);
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .auth-link:hover { color: var(--psca-accent); text-decoration: underline; }

        /* Status / alert */
        .auth-status {
            border-radius: 10px;
            font-size: 0.875rem;
            border-left: 4px solid #28a745;
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        /* Error messages */
        .text-danger-sm {
            font-size: 0.8rem;
            color: #dc3545;
            font-weight: 500;
            margin-top: 4px;
        }

        /* Footer */
        .auth-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 0.875rem;
            color: var(--psca-text-muted);
        }

        /* Divider */
        .form-divider {
            display: flex; align-items: center;
            color: var(--psca-text-muted);
            font-size: 0.8rem;
            margin: 18px 0;
        }
        .form-divider::before,.form-divider::after {
            content:''; flex:1; height:1px; background: var(--psca-border);
        }
        .form-divider span { padding: 0 12px; }

        @media (max-width: 576px) {
            .auth-card { border-radius: 16px; }
            .auth-card-header { padding: 28px 24px 36px; }
            .auth-card-body { padding: 22px 24px 36px; }
        }
    </style>

    @stack('styles')
</head>
<body>

    <!-- BG Decorations -->
    <div class="bg-circle-1"></div>
    <div class="bg-circle-2"></div>
    <div class="bg-dots">
        <svg width="130" height="130" viewBox="0 0 130 130" fill="none">
            @for($i=0;$i<5;$i++) @for($j=0;$j<5;$j++)
            <circle cx="{{ 10+$j*28 }}" cy="{{ 10+$i*28 }}" r="3.5" fill="white"/>
            @endfor @endfor
        </svg>
    </div>
    <div class="bg-dots-2">
        <svg width="110" height="110" viewBox="0 0 110 110" fill="none">
            @for($i=0;$i<4;$i++) @for($j=0;$j<4;$j++)
            <circle cx="{{ 10+$j*32 }}" cy="{{ 10+$i*32 }}" r="3" fill="white"/>
            @endfor @endfor
        </svg>
    </div>

    <!-- Auth Wrapper -->
    <div class="auth-wrapper">
        <div class="auth-card">

            <!-- Header -->
            <div class="auth-card-header">
                <div class="auth-logo-wrap">
                    <img
                        src="{{ asset('images/psca_logo.png') }}"
                        alt="PSCA Logo"
                        width="50"
                        height="50"
                        style="width:50px; height:50px; object-fit:contain;"
                        onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='block';"
                    />
                    <span style="display:none; color:#e8a020; font-size:1.6rem; font-weight:800; letter-spacing:1px;">PSCA</span>
                </div>
                <h1>{{ config('app.name', 'PSCA') }}</h1>
                <p>@yield('header-subtitle', 'Application Management System')</p>
            </div>

            <!-- Body -->
            <div class="auth-card-body">
                @yield('content')
            </div>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        document.querySelectorAll('.pw-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const inp = document.getElementById(this.dataset.target);
                const ic = this.querySelector('i');
                if (inp.type === 'password') {
                    inp.type = 'text';
                    ic.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    inp.type = 'password';
                    ic.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        });

        // Submit loading state
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = form.querySelector('.btn-psca');
                if (btn) {
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Please wait...';
                    btn.disabled = true;
                    setTimeout(() => { btn.disabled = false; }, 10000);
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
