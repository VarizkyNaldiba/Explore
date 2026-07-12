<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - My Wallet</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 1.5rem;
        }
        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }
        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }
        .login-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-neon), var(--green-neon));
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo h1 {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #fff 0%, var(--primary-neon) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.25rem;
        }
        .login-logo span {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .error-alert {
            background: rgba(255, 23, 68, 0.1);
            border: 1px solid rgba(255, 23, 68, 0.2);
            color: var(--red-neon);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            line-height: 1.4;
        }
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.75rem;
            font-size: 0.85rem;
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
        }
        .remember-me input {
            cursor: pointer;
            accent-color: var(--primary-neon);
            width: 15px;
            height: 15px;
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card animate-card">
        
        <!-- Logo -->
        <div class="login-logo">
            <h1>My Wallet</h1>
            <span>Akses Dashboard Pengelola</span>
        </div>

        <!-- Global Error Messages -->
        @if ($errors->any())
            <div class="error-alert">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Form -->
        <form action="{{ url('/login') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="admin@wallet.com" required autofocus>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="••••••••••••" required>
            </div>

            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Ingat Saya</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 0.85rem;">Masuk Sekarang</button>
        </form>

        <div class="login-footer">
            &copy; {{ date('Y') }} My Wallet Bot. All rights reserved.
        </div>
    </div>
</div>

</body>
</html>
