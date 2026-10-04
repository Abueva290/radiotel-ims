<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In · Radiotel IMS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-800 bg-slate-100">
<div class="min-h-screen flex">

    {{-- Left: brand panel --}}
    <div class="hidden lg:flex lg:w-1/2 bg-slate-800 text-white flex-col justify-between p-12">
        <div class="flex items-center gap-3">
            <x-app-logo class="w-11 h-11 ring-1 ring-slate-600 rounded-[14px]" />
            <div>
                <p class="font-semibold text-lg leading-tight">Radiotel</p>
                <p class="text-xs text-slate-400">IMS v1.0</p>
            </div>
        </div>

        <div class="max-w-md">
            <h1 class="text-4xl font-semibold leading-tight">
                Inventory & Sales<br>Management System
            </h1>
            <p class="mt-4 text-slate-300">
                Sales, inventory, repairs, receivables, and payables in one place for
                Radiotel Electronics Sales & Services Co.
            </p>

            <div class="mt-10 grid grid-cols-2 gap-3 text-sm">
                @foreach (['Sales & Invoicing', 'Inventory & Low-Stock Alerts', 'Repair & Service', 'Receivables & Payables'] as $feature)
                    <div class="flex items-center gap-2 rounded-lg bg-slate-700/60 px-3 py-2.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        {{ $feature }}
                    </div>
                @endforeach
            </div>
        </div>

        <p class="text-xs text-slate-400">
            Door 2, Tionko Bldg., Elpidio Quirino Ave., Poblacion District, Davao City
        </p>
    </div>

    {{-- Right: login form --}}
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-md">

            {{-- Logo for small screens --}}
            <div class="flex lg:hidden items-center gap-3 mb-8 justify-center">
                <x-app-logo class="w-11 h-11" />
                <div>
                    <p class="font-semibold text-lg leading-tight">Radiotel IMS</p>
                    <p class="text-xs text-slate-500">Inventory & Sales Management System</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
                <h2 class="text-2xl font-semibold">Welcome back</h2>
                <p class="text-sm text-slate-500 mt-1 mb-6">Sign in with your company account.</p>

                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-2.5 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium mb-1.5">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               required autofocus autocomplete="username" placeholder="name@radiotel.ph"
                               class="w-full rounded-lg border-slate-300 text-[15px] px-3.5 py-2.5 focus:border-slate-500 focus:ring-slate-500">
                        @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div x-data="{ show: false }">
                        <label for="password" class="block text-sm font-medium mb-1.5">Password</label>
                        <div class="relative">
                            <input id="password" :type="show ? 'text' : 'password'" type="password" name="password"
                                   required autocomplete="current-password" placeholder="Enter your password"
                                   class="w-full rounded-lg border-slate-300 text-[15px] px-3.5 py-2.5 pr-16 focus:border-slate-500 focus:ring-slate-500">
                            <button type="button" @click="show = !show"
                                    class="absolute inset-y-0 right-0 px-3 text-xs font-medium text-slate-500 hover:text-slate-800"
                                    x-text="show ? 'Hide' : 'Show'">Show</button>
                        </div>
                        @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600 shrink-0">
                            <input id="remember_me" type="checkbox" name="remember"
                                   class="rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                            Remember me
                        </label>
                        <span class="text-xs text-slate-400 text-right">Forgot password? Contact the Operational Manager.</span>
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 rounded-lg bg-slate-800 text-white text-[15px] font-medium hover:bg-slate-900">
                        Sign In
                    </button>
                </form>
            </div>

            <p class="text-center text-xs text-slate-400 mt-6">
                Radiotel Electronics Sales & Services Co. · IMS v1.0
            </p>
        </div>
    </div>
</div>
</body>
</html>