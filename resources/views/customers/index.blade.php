@extends('layouts.admin')

@section('title', 'Customers')

@section('content')
<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">Customer Management</h1>
        <p class="text-sm text-slate-500">Customer records used in sales and repair transactions</p>
    </div>
    <a href="{{ route('customers.create') }}"
       class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ Add Customer</a>
</div>

<div class="flex items-center justify-between mb-4">
    <div class="flex gap-2">
        <a href="{{ route('customers.index') }}"
           class="px-3 py-1.5 rounded-lg text-sm {{ ! $showArchived ? 'bg-slate-700 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            Active ({{ $activeCount }})
        </a>
        <a href="{{ route('customers.index', ['archived' => 1]) }}"
           class="px-3 py-1.5 rounded-lg text-sm {{ $showArchived ? 'bg-slate-700 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            Archived ({{ $archivedCount }})
        </a>
    </div>

    <form method="GET">
        @if ($showArchived)<input type="hidden" name="archived" value="1">@endif
        <input type="text" name="search" value="{{ $search }}" placeholder="Search customer..."
               class="w-64 rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">ID</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-left">Contact Person</th>
                <th class="px-4 py-3 text-left">Phone</th>
                <th class="px-4 py-3 text-center">Sales</th>
                <th class="px-4 py-3 text-center">Repairs</th>
                <th class="px-4 py-3 text-right">Balance</th>
                <th class="px-4 py-3 text-right"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-400">C{{ str_pad($customer->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-3 font-medium">{{ $customer->name }}</td>
                    <td class="px-4 py-3">{{ $customer->contact_person ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $customer->phone ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">{{ $customer->sales_count }}</td>
                    <td class="px-4 py-3 text-center">{{ $customer->repair_jobs_count }}</td>
                    <td class="px-4 py-3 text-right font-medium">₱{{ number_format($customer->receivables_sum_balance ?? 0, 2) }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex gap-2">
                            <a href="{{ route('customers.edit', $customer) }}" class="btn-action">Edit</a>

                            <form method="POST" action="{{ route('customers.archive', $customer) }}"
                                  onsubmit="return confirm(@js(($showArchived ? 'Restore ' : 'Archive ') . $customer->name . '?'))">
                                @csrf
                                @method('PATCH')
                                <button class="{{ $showArchived ? 'btn-action-success' : 'btn-action-warning' }}">
                                    {{ $showArchived ? 'Restore' : 'Archive' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                        {{ $showArchived ? 'No archived customers.' : 'No customers found.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<p class="text-xs text-slate-400 mt-3">
    Archiving hides a customer from new transactions. Past sales, repairs, and receivables are kept.
</p>
@endsection