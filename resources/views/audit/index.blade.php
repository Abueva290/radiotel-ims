@extends('layouts.admin')

@section('title', 'Audit Trail')

@section('content')
@php
    $input = 'rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $actionBadge = [
        'created' => 'bg-green-100 text-green-700',
        'updated' => 'bg-blue-100 text-blue-700',
        'deleted' => 'bg-red-100 text-red-700',
        'login'   => 'bg-slate-100 text-slate-700',
        'logout'  => 'bg-slate-100 text-slate-500',
    ];
    // Where to open each kind of record (only for tables that have their own page)
    $links = [
        'sales'             => fn ($id) => route('sales.show', $id),
        'repair_jobs'       => fn ($id) => route('repairs.show', $id),
        'supplier_invoices' => fn ($id) => route('payables.show', $id),
        'products'          => fn ($id) => route('inventory.edit', $id),
        'customers'         => fn ($id) => route('customers.edit', $id),
        'users'             => fn ($id) => route('users.edit', $id),
    ];
    $hasFilters = collect($filters)->filter()->isNotEmpty();
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">Audit Trail</h1>
        <p class="text-sm text-slate-500">Record of who did what in the system, and when</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 px-5 py-3 text-right">
        <p class="text-xs text-slate-500">Actions today</p>
        <p class="text-xl font-semibold">{{ $today }}</p>
    </div>
</div>

<form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs text-slate-500 mb-1">User</label>
        <select name="user_id" class="{{ $input }}">
            <option value="">All users</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected(($filters['user_id'] ?? '') == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">Action</label>
        <select name="action" class="{{ $input }}">
            <option value="">All actions</option>
            @foreach ($actions as $a)
                <option value="{{ $a }}" @selected(($filters['action'] ?? '') === $a)>{{ ucfirst($a) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">Record type</label>
        <select name="table" class="{{ $input }}">
            <option value="">All records</option>
            @foreach ($tables as $key => $label)
                <option value="{{ $key }}" @selected(($filters['table'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">From</label>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="{{ $input }}">
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">To</label>
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="{{ $input }}">
    </div>
    <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Filter</button>
    @if ($hasFilters)
        <a href="{{ route('audit.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Clear</a>
    @endif
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">Date & Time</th>
                <th class="px-4 py-3 text-left">User</th>
                <th class="px-4 py-3 text-center">Action</th>
                <th class="px-4 py-3 text-left">Record</th>
                <th class="px-4 py-3 text-left">Record ID</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $log->created_at->format('M d, Y h:i:s A') }}</td>
                    <td class="px-4 py-3">{{ $log->user?->name ?? 'System' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $actionBadge[$log->action] ?? 'bg-slate-100 text-slate-700' }}">
                            {{ ucfirst($log->action) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $tables[$log->table_affected] ?? $log->table_affected }}</td>
                    <td class="px-4 py-3">
                        @if ($log->record_id && isset($links[$log->table_affected]) && $log->action !== 'deleted')
                            <a href="{{ $links[$log->table_affected]($log->record_id) }}" class="text-slate-700 underline hover:text-slate-900">#{{ $log->record_id }}</a>
                        @else
                            <span class="text-slate-500">{{ $log->record_id ? '#' . $log->record_id : '—' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                        {{ $hasFilters ? 'No activity matches these filters.' : 'No activity recorded yet.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $logs->links() }}
</div>
@endsection