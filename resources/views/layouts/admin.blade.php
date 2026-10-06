<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Radiotel IMS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Apply the saved text size right away (before the page shows)
        try {
            const saved = localStorage.getItem('ims-font-size');
            if (saved) document.documentElement.style.fontSize = saved + 'px';
        } catch (e) {}

        function fontSizer() {
            const sizes = [14, 15, 16, 17, 18, 19, 20];
            let current = 3; // 17px default
            try {
                const found = sizes.indexOf(parseInt(localStorage.getItem('ims-font-size')));
                if (found >= 0) current = found;
            } catch (e) {}

            return {
                open: false,
                sizes,
                i: current,
                apply() {
                    const px = this.sizes[this.i];
                    document.documentElement.style.fontSize = px + 'px';
                    try { localStorage.setItem('ims-font-size', px); } catch (e) {}
                },
                reset() {
                    this.i = 3;
                    this.apply();
                },
            };
        }
    </script>
</head>
<body class="bg-slate-100 text-slate-800 antialiased font-sans">
@php
    $user = auth()->user();
    $roleLabels = [
        'admin'          => 'Operational Manager',
        'secretary'      => 'Secretary',
        'technical_head' => 'Technical Head',
        'technician'     => 'Technician',
        'staff'          => 'Staff',
    ];
    $all = ['admin', 'secretary', 'technical_head', 'technician', 'staff'];
    $sections = [
        'Overview' => [
            ['label' => 'Dashboard',        'icon' => 'layout-dashboard', 'route' => 'dashboard',         'roles' => $all],
        ],
        'Operations' => [
            ['label' => 'Sales',            'icon' => 'receipt',          'route' => 'sales.index',       'roles' => ['admin', 'secretary']],
            ['label' => 'Customers',        'icon' => 'users',            'route' => 'customers.index',   'roles' => ['admin', 'secretary']],
            ['label' => 'Inventory',        'icon' => 'package',          'route' => 'inventory.index',   'roles' => ['admin', 'staff']],
            ['label' => 'Repair & Service', 'icon' => 'tool',             'route' => 'repairs.index',     'roles' => ['admin', 'technical_head', 'technician']],
        ],
        'Finance' => [
            ['label' => 'Receivables',      'icon' => 'cash',             'route' => 'receivables.index', 'roles' => ['admin', 'secretary']],
            ['label' => 'Payables',         'icon' => 'file-invoice',     'route' => 'payables.index',    'roles' => ['admin', 'secretary']],
        ],
        'Administration' => [
            ['label' => 'Reports',          'icon' => 'chart-bar',        'route' => 'reports.index',     'roles' => ['admin']],
            ['label' => 'User Management',  'icon' => 'user-cog',         'route' => 'users.index',       'roles' => ['admin']],
            ['label' => 'Audit Trail',      'icon' => 'history',          'route' => 'audit.index',       'roles' => ['admin']],
            ['label' => 'Backups',          'icon' => 'database',         'route' => 'backups.index',     'roles' => ['admin']],
        ],
    ];
    $initials = collect(explode(' ', $user->name))->map(fn ($w) => $w[0])->take(2)->implode('');
    // Modals show their own errors, so the page banner skips them
    $showErrorBanner = $errors->any() && ! old('_form');
@endphp

<div class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside class="w-64 shrink-0 bg-white border-r border-slate-200 flex flex-col sticky top-0 h-screen">
        <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-100">
            <x-app-logo class="w-10 h-10" />
            <div>
                <p class="font-semibold leading-tight">Radiotel</p>
                <p class="text-xs text-slate-400">Inventory & Sales · v1.0</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-3">
            @unless ($user->must_change_password)
                @foreach ($sections as $title => $items)
                    @php
                        // Show only menu items for this role whose page exists
                        $visible = array_filter($items, fn ($i) => in_array($user->role, $i['roles'])
                            && \Illuminate\Support\Facades\Route::has($i['route']));
                    @endphp
                    @if (count($visible))
                        <p class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400 first:pt-0">{{ $title }}</p>
                        <div class="space-y-0.5">
                            @foreach ($visible as $item)
                                @php $active = request()->routeIs(explode('.', $item['route'])[0] . '*'); @endphp
                                <a href="{{ route($item['route']) }}"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors
                                          {{ $active ? 'bg-slate-800 text-white font-medium' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                    <i class="ti ti-{{ $item['icon'] }} text-lg {{ $active ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            @endunless
        </nav>

        <div class="p-3 border-t border-slate-100">
            <div class="flex items-center gap-3 px-2 py-2 mb-1">
                <div class="w-9 h-9 rounded-full bg-slate-700 text-white flex items-center justify-center text-xs font-bold shrink-0">{{ $initials }}</div>
                <div class="min-w-0">
                    <p class="text-sm font-medium leading-tight truncate">{{ $user->name }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ $roleLabels[$user->role] }}</p>
                </div>
            </div>

            {{-- Text size setting --}}
            <div class="relative" x-data="fontSizer()" @click.outside="open = false">
                <button type="button" @click="open = !open"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">
                    <i class="ti ti-typography text-lg text-slate-400"></i>
                    <span class="flex-1 text-left">Text Size</span>
                    <span class="text-xs text-slate-400" x-text="sizes[i] + 'px'"></span>
                </button>

                <div x-show="open" x-transition style="display: none"
                     class="absolute bottom-full left-0 mb-2 w-72 bg-white rounded-xl border border-slate-200 shadow-lg p-5 z-50">
                    <p class="text-sm font-medium mb-4">Text size</p>
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-semibold text-slate-500">A</span>
                        <div class="flex-1">
                            <input type="range" min="0" :max="sizes.length - 1" step="1"
                                   x-model.number="i" @input="apply()" class="w-full accent-slate-700">
                            <div class="flex justify-between px-0.5 mt-1">
                                <template x-for="(s, n) in sizes" :key="n">
                                    <span class="w-2 h-2 rounded-full" :class="n <= i ? 'bg-slate-700' : 'bg-slate-300'"></span>
                                </template>
                            </div>
                        </div>
                        <span class="text-2xl font-semibold text-slate-700">A</span>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button type="button" @click="reset()" class="text-xs text-slate-400 hover:text-slate-700">Reset to default</button>
                    </div>
                </div>
            </div>

            <a href="{{ route('password.change') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('password.change') ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-600 hover:bg-slate-100' }}">
                <i class="ti ti-lock text-lg text-slate-400"></i> Change Password
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50">
                    <i class="ti ti-logout text-lg"></i> Sign Out
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <main class="flex-1 min-w-0">
        <header class="sticky top-0 z-30 flex items-center justify-between px-8 h-16 bg-white border-b border-slate-200">
            <p class="text-sm text-slate-400">
                Radiotel <span class="mx-1">/</span> <span class="font-semibold text-slate-700">@yield('title')</span>
            </p>
            <div class="flex items-center gap-4">
                <span class="hidden md:inline-flex items-center gap-1.5 text-sm text-slate-500">
                    <i class="ti ti-calendar text-base"></i> {{ now()->format('l, M d, Y') }}
                </span>
                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-xs font-medium text-slate-600">{{ $roleLabels[$user->role] }}</span>
            </div>
        </header>

        <div class="p-8 max-w-[1600px]">
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                     class="mb-6 flex items-start gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                    <i class="ti ti-circle-check text-lg"></i>
                    <p class="flex-1">{{ session('success') }}</p>
                    <button type="button" @click="show = false" class="text-green-600 hover:text-green-800" aria-label="Dismiss">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            @endif

            @if ($showErrorBanner)
                <div class="mb-6 flex items-start gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    <i class="ti ti-alert-circle text-lg"></i>
                    <ul class="flex-1 space-y-1">
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