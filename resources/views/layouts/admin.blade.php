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
    <style>
        /* ---- Status badge: one size, shape and palette everywhere ---- */
        .sb { display: inline-flex; align-items: center; gap: .4rem; padding: .2rem .65rem; border-radius: 999px;
              font-size: .75rem; font-weight: 600; line-height: 1.25rem; white-space: nowrap; border: 1px solid transparent; }
        .sb-dot { width: .4rem; height: .4rem; border-radius: 999px; background: currentColor; display: inline-block; }
        .sb-green  { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .sb-red    { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .sb-amber  { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .sb-blue   { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
        .sb-violet { background: #ede9fe; color: #5b21b6; border-color: #ddd6fe; }
        .sb-slate  { background: #f1f5f9; color: #334155; border-color: #e2e8f0; }
        .sb-dark   { background: #334155; color: #fff; border-color: #334155; }

        /* ---- Clickable KPI card ---- */
        a.kpi { display: block; color: inherit; text-decoration: none; position: relative;
                transition: border-color .15s, box-shadow .15s, transform .15s; }
        a.kpi:hover { border-color: #64748b; box-shadow: 0 4px 14px rgba(15, 23, 42, .08); transform: translateY(-1px); }
        a.kpi:focus-visible { outline: 2px solid #334155; outline-offset: 2px; }
        a.kpi::after { content: '\2192'; position: absolute; top: .9rem; right: 1rem; color: #94a3b8; font-size: .95rem; }
        a.kpi:hover::after { color: #334155; }
        a.kpi.kpi-on { border-color: #334155; box-shadow: 0 0 0 1px #334155; }

        /* ---- Filter chips (status tabs) ---- */
        .chips { display: flex; flex-wrap: wrap; gap: .5rem; }
        .chip { display: inline-flex; align-items: center; gap: .45rem; padding: .4rem .85rem; border-radius: 999px;
                font-size: .8125rem; font-weight: 500; color: #475569; background: #fff; border: 1px solid #e2e8f0; text-decoration: none; }
        .chip:hover { background: #f1f5f9; }
        .chip.on { background: #334155; color: #fff; border-color: #334155; }
        .chip small { font-size: .7rem; padding: 0 .4rem; border-radius: 999px; background: #e2e8f0; color: #475569; line-height: 1.1rem; }
        .chip.on small { background: rgba(255, 255, 255, .22); color: #fff; }

        /* ---- Confirmation dialog ---- */
        .cf-back { position: fixed; inset: 0; z-index: 90; display: none; align-items: center; justify-content: center;
                   padding: 1rem; background: rgba(15, 23, 42, .45); }
        .cf-back.open { display: flex; }
        .cf-box { width: 100%; max-width: 26rem; background: #fff; border-radius: 1rem; padding: 1.5rem;
                  box-shadow: 0 20px 50px rgba(15, 23, 42, .25); }
        .cf-icon { width: 2.75rem; height: 2.75rem; border-radius: 999px; display: flex; align-items: center; justify-content: center;
                   font-size: 1.4rem; background: #fef3c7; color: #b45309; margin-bottom: .9rem; }
        .cf-box.danger .cf-icon { background: #fee2e2; color: #b91c1c; }
        .cf-title { font-size: 1.05rem; font-weight: 600; color: #0f172a; }
        .cf-text { margin-top: .4rem; font-size: .9rem; color: #475569; line-height: 1.45; }
        .cf-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.4rem; }
        .cf-btn { padding: .55rem 1.1rem; border-radius: .6rem; font-size: .9rem; font-weight: 500; border: 0; cursor: pointer; }
        .cf-cancel { background: #f1f5f9; color: #334155; }
        .cf-cancel:hover { background: #e2e8f0; }
        .cf-ok { background: #334155; color: #fff; }
        .cf-ok:hover { background: #1e293b; }
        .cf-box.danger .cf-ok { background: #dc2626; }
        .cf-box.danger .cf-ok:hover { background: #b91c1c; }
    </style>
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
            ['label' => 'Suppliers',        'icon' => 'truck',            'route' => 'suppliers.index',   'roles' => ['admin', 'secretary']],
            ['label' => 'Inventory',        'icon' => 'package',          'route' => 'inventory.index',   'roles' => ['admin', 'staff']],
            ['label' => 'Repair & Service', 'icon' => 'tool',             'route' => 'repairs.index',     'roles' => ['admin', 'technical_head', 'technician']],
            ['label' => 'Factory Defects',  'icon' => 'alert-octagon',    'route' => 'defects.index',     'roles' => ['admin', 'technical_head', 'technician', 'staff']],
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

<div class="flex min-h-screen" x-data="{ nav: false }" @keydown.window.escape="nav = false">

    {{-- Dark background behind the menu on phones; tap it to close the menu --}}
    <div x-show="nav" x-transition.opacity @click="nav = false" style="display: none"
         class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden"></div>

    {{-- Sidebar: slides in on phones and tablets, always visible on laptops and desktops --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-64 shrink-0 bg-white border-r border-slate-200 flex flex-col h-screen
                  -translate-x-full transition-transform duration-200 lg:sticky lg:top-0 lg:translate-x-0"
           :class="nav && '!translate-x-0 shadow-xl'">
        <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-100">
            <x-app-logo class="w-10 h-10" />
            <div class="flex-1">
                <p class="font-semibold leading-tight">Radiotel</p>
                <p class="text-xs text-slate-400">Inventory & Sales · v1.0</p>
            </div>
            <button type="button" @click="nav = false" class="lg:hidden p-1 text-slate-400 hover:text-slate-700" aria-label="Close menu">
                <i class="ti ti-x text-xl"></i>
            </button>
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
        <header class="sticky top-0 z-30 flex items-center justify-between gap-3 px-4 sm:px-6 lg:px-8 h-16 bg-white border-b border-slate-200">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" @click="nav = true" class="lg:hidden -ml-1 p-1.5 rounded-lg text-slate-600 hover:bg-slate-100" aria-label="Open menu">
                    <i class="ti ti-menu-2 text-2xl"></i>
                </button>
                <p class="text-sm text-slate-400 truncate">
                    <span class="hidden sm:inline">Radiotel <span class="mx-1">/</span></span>
                    <span class="font-semibold text-slate-700">@yield('title')</span>
                </p>
            </div>
            <div class="flex items-center gap-4 shrink-0">
                <span class="hidden md:inline-flex items-center gap-1.5 text-sm text-slate-500">
                    <i class="ti ti-calendar text-base"></i> {{ now()->format('l, M d, Y') }}
                </span>
                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-xs font-medium text-slate-600">{{ $roleLabels[$user->role] }}</span>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8 max-w-[1600px]">
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

        /* ---- Confirmation dialog ---- */
        .cf-back { position: fixed; inset: 0; z-index: 90; display: none; align-items: center; justify-content: center;
                   padding: 1rem; background: rgba(15, 23, 42, .45); }
        .cf-back.open { display: flex; }
        .cf-box { width: 100%; max-width: 26rem; background: #fff; border-radius: 1rem; padding: 1.5rem;
                  box-shadow: 0 20px 50px rgba(15, 23, 42, .25); }
        .cf-icon { width: 2.75rem; height: 2.75rem; border-radius: 999px; display: flex; align-items: center; justify-content: center;
                   font-size: 1.4rem; background: #fef3c7; color: #b45309; margin-bottom: .9rem; }
        .cf-box.danger .cf-icon { background: #fee2e2; color: #b91c1c; }
        .cf-title { font-size: 1.05rem; font-weight: 600; color: #0f172a; }
        .cf-text { margin-top: .4rem; font-size: .9rem; color: #475569; line-height: 1.45; }
        .cf-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.4rem; }
        .cf-btn { padding: .55rem 1.1rem; border-radius: .6rem; font-size: .9rem; font-weight: 500; border: 0; cursor: pointer; }
        .cf-cancel { background: #f1f5f9; color: #334155; }
        .cf-cancel:hover { background: #e2e8f0; }
        .cf-ok { background: #334155; color: #fff; }
        .cf-ok:hover { background: #1e293b; }
        .cf-box.danger .cf-ok { background: #dc2626; }
        .cf-box.danger .cf-ok:hover { background: #b91c1c; }
        </div>
    </div>
</div>
<script>
    (function () {
        var back = document.getElementById('cf-back');
        var box = document.getElementById('cf-box');
        var pending = null;

        function close() { back.classList.remove('open'); pending = null; }

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form.dataset || !form.dataset.confirm || form.dataset.confirmed === '1') return;
            e.preventDefault();
            e.stopPropagation();
            pending = { form: form, submitter: e.submitter || null };
            document.getElementById('cf-title').textContent = form.dataset.confirmTitle || 'Please confirm';
            document.getElementById('cf-text').textContent = form.dataset.confirm;
            document.getElementById('cf-ok').textContent = form.dataset.confirmButton || 'Confirm';
            box.classList.toggle('danger', form.dataset.confirmTone === 'danger');
            back.classList.add('open');
            document.getElementById('cf-ok').focus();
        }, true);

        document.getElementById('cf-ok').addEventListener('click', function () {
            if (!pending) return;
            var form = pending.form, submitter = pending.submitter;
            form.dataset.confirmed = '1';
            close();
            if (form.requestSubmit) { form.requestSubmit(submitter && submitter.form === form ? submitter : undefined); }
            else { form.submit(); }
            setTimeout(function () { form.dataset.confirmed = ''; }, 0);
        });
        document.getElementById('cf-cancel').addEventListener('click', close);
        back.addEventListener('click', function (e) { if (e.target === back) close(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    })();
</script>
</body>
</html>