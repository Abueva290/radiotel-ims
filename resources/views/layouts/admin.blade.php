<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Radiotel IMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
@php
    $user = auth()->user();
    $roleLabels = [
        'admin' => 'Operational Manager',
        'secretary' => 'Secretary',
        'technical_head' => 'Technical Head',
        'staff' => 'Staff',
    ];
    $nav = [
        ['label' => 'Dashboard',        'route' => 'dashboard',         'roles' => ['admin', 'secretary', 'technical_head', 'staff']],
        ['label' => 'Sales',            'route' => 'sales.index',       'roles' => ['admin', 'secretary']],
        ['label' => 'Inventory',        'route' => 'inventory.index',   'roles' => ['admin', 'staff']],
        ['label' => 'Repair & Service', 'route' => 'repairs.index',     'roles' => ['admin', 'technical_head']],
        ['label' => 'Receivables',      'route' => 'receivables.index', 'roles' => ['admin', 'secretary']],
        ['label' => 'Payables',         'route' => 'payables.index',    'roles' => ['admin', 'secretary']],
        ['label' => 'Reports',          'route' => 'reports.index',     'roles' => ['admin']],
        ['label' => 'User Management',  'route' => 'users.index',       'roles' => ['admin']],
    ];
@endphp

<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="w-60 bg-white border-r border-slate-200 flex flex-col">
        <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-100">
            <div class="w-9 h-9 rounded-lg bg-slate-700 text-white flex items-center justify-center text-sm font-bold">RT</div>
            <div>
                <p class="font-semibold leading-tight">Radiotel</p>
                <p class="text-xs text-slate-400">IMS v1.0</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1">
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Main Menu</p>
            @foreach ($nav as $item)
                @if (in_array($user->role, $item['roles']))
                    <a href="{{ route($item['route']) }}"
                       class="block px-3 py-2 rounded-lg text-sm
                              {{ request()->routeIs(explode('.', $item['route'])[0] . '*') ? 'bg-slate-100 font-semibold text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="px-4 py-4 border-t border-slate-100">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-slate-700 text-white flex items-center justify-center text-xs font-bold">
                    {{ collect(explode(' ', $user->name))->map(fn ($w) => $w[0])->take(2)->implode('') }}
                </div>
                <div>
                    <p class="text-sm font-medium leading-tight">{{ $user->name }}</p>
                    <p class="text-xs text-slate-400">{{ $roleLabels[$user->role] }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-left px-3 py-2 rounded-lg text-sm bg-slate-100 hover:bg-slate-200">Sign Out</button>
            </form>
        </div>
    </aside>

    {{-- Main content --}}
    <main class="flex-1">
        <header class="flex items-center justify-between px-8 py-4 bg-white border-b border-slate-200">
            <p class="text-sm text-slate-400">Radiotel / <span class="font-semibold text-slate-700">@yield('title')</span></p>
            <p class="text-sm text-slate-400">{{ now()->format('M d, Y') }}</p>
        </header>

        <div class="p-8">
            @if (session('success'))
                <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>
</body>
</html>