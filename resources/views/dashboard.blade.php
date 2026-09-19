<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Felipe Verallo Foundation College Inc. Accounting System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen antialiased">

    <!-- TOP NAVBAR -->
    <nav class="bg-blue-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <!-- 🆕 LOGO -->
                <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center p-1.5 shadow-md">
                    <img
                        src="{{ asset('images/frvfc-logo.png') }}"
                        alt="FRVFC Logo"
                        class="w-full h-full object-contain"
                    />
                </div>
                <h1 class="font-bold text-lg">Felipe Verallo Foundation College Inc. Accounting System</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-xs text-blue-200">Welcome,</p>
                    <p class="font-bold text-sm">{{ auth()->user()->name }}</p>
                </div>
                <span class="px-3 py-1 rounded-full bg-blue-700 text-xs font-bold uppercase">
                    {{ auth()->user()->role }}
                </span>
                <form method="POST" action="/logout" class="inline">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-xs font-semibold transition">
                        🚪 Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT (Vue) -->
    <div id="app" data-user-role="{{ auth()->user()->role ?? '' }}"></div>

</body>
</html>