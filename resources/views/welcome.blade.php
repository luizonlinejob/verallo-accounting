<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FVFC Accounting System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-slate-100 min-h-screen antialiased">

    <div class="min-h-screen flex flex-col">
        <nav class="px-6 py-5">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white text-lg shadow-md">🎓</div>
                    <span class="font-bold text-slate-800 text-lg">Felipe Verallo Foundation College Inc. Accounting System</span>
                </div>
                <a href="/login" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-800 text-white text-sm font-semibold shadow-md hover:shadow-lg transition">
                    Sign In →
                </a>
            </div>
        </nav>

        <main class="flex-1 flex items-center justify-center px-6 py-12">
            <div class="max-w-3xl text-center">
                <div class="inline-block px-4 py-1.5 bg-blue-100 text-blue-800 rounded-full text-xs font-bold mb-6 uppercase tracking-wider">
                    ✨ FVFC School Financial Management Portal
                </div>

                <h1 class="text-5xl sm:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight mb-6">
                    Felipe Verallo Foundation 
                    <span class="bg-gradient-to-r from-blue-600 to-blue-900 bg-clip-text text-transparent">College Inc. Accounting Portal</span>
                </h1>

                <p class="text-lg text-slate-600 mb-10 max-w-xl mx-auto">
                    A streamlined platform for managing student fees, payments, and financial records — all in one place.
                </p>

                <a href="/login" class="inline-block px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-600 to-blue-800 text-white font-bold shadow-lg shadow-blue-500/30 hover:shadow-xl transition">
                    🔐 Sign In to Portal
                </a>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-20">
                    <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100">
                        <div class="text-3xl mb-3">📊</div>
                        <h3 class="font-bold text-slate-800 mb-1">Track Payments</h3>
                        <p class="text-sm text-slate-500">Monitor approved and pending payments in real-time.</p>
                    </div>
                    <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100">
                        <div class="text-3xl mb-3">🖨️</div>
                        <h3 class="font-bold text-slate-800 mb-1">Print SOA</h3>
                        <p class="text-sm text-slate-500">Generate statement of accounts instantly.</p>
                    </div>
                    <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100">
                        <div class="text-3xl mb-3">🛡️</div>
                        <h3 class="font-bold text-slate-800 mb-1">Role-Based Access</h3>
                        <p class="text-sm text-slate-500">Secure approvals with multi-level verification.</p>
                    </div>
                </div>
            </div>
        </main>

        <footer class="px-6 py-6 text-center text-xs text-slate-400">
            © {{ date('Y') }} FVFC Accounting System. All rights reserved. | Developed by: LPURAL
        </footer>
    </div>

</body>
</html>