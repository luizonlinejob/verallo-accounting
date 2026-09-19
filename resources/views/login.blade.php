<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In - FVFC Accounting System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-blue-50 min-h-screen antialiased">

    <div class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <!-- LOGO -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-2xl bg-white shadow-lg shadow-blue-500/20 mb-4 p-3 border border-slate-100">
                    <img
                        src="{{ asset('images/frvfc-logo.png') }}"
                        alt="FRVFC Logo"
                        class="w-full h-full object-contain"
                    />
                </div>
                <h1 class="text-2xl font-bold text-slate-800">Welcome Back</h1>
                <p class="text-sm text-slate-500 mt-1">Sign in to your account</p>
            </div>

            <!-- LOGIN CARD -->
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 p-8">
                <div id="login-app"></div>
            </div>

            <!-- FOOTER -->
            <p class="text-center text-xs text-slate-400 mt-6">
                © {{ date('Y') }} FVFC Accounting System | Developed by: LPURAL
            </p>
        </div>
    </div>

</body>
</html>