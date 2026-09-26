@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
@php
    $roleBadge = [
        'admin'          => 'bg-slate-700 text-white',
        'secretary'      => 'bg-blue-100 text-blue-700',
        'technical_head' => 'bg-purple-100 text-purple-700',
        'staff'          => 'bg-slate-100 text-slate-700',
    ];
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">User Management</h1>
        <p class="text-sm text-slate-500">Employee accounts and role-based access</p>
    </div>
    <a href="{{ route('users.create') }}"
       class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ Add User</a>
</div>

<div class="grid grid-cols-4 gap-4 mb-6">
    @foreach (\App\Models\User::ROLES as $role => $label)
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-2xl font-semibold">{{ $counts[$role] ?? 0 }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $label }}</p>
        </div>
    @endforeach
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">ID</th>
                <th class="px-4 py-3 text-left">Full Name</th>
                <th class="px-4 py-3 text-left">Role</th>
                <th class="px-4 py-3 text-left">Email</th>
                <th class="px-4 py-3 text-left">Last Login</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $u)
                <tr class="border-b border-slate-50 hover:bg-slate-50 {{ $u->isActive() ? '' : 'opacity-60' }}">
                    <td class="px-4 py-3 text-slate-400">U{{ str_pad($u->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-700 text-white flex items-center justify-center text-xs font-bold">
                                {{ collect(explode(' ', $u->name))->map(fn ($w) => $w[0])->take(2)->implode('') }}
                            </div>
                            <div>
                                <p class="font-medium">
                                    {{ $u->name }}
                                    @if ($u->is(auth()->user()))<span class="text-xs text-slate-400">(you)</span>@endif
                                </p>
                                @if ($u->must_change_password)
                                    <p class="text-xs text-amber-600">Pending password change</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs font-medium {{ $roleBadge[$u->role] }}">
                            {{ \App\Models\User::ROLES[$u->role] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-500">{{ $u->email }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $u->last_login_at?->format('Y-m-d h:i A') ?? 'Never' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $u->isActive() ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ ucfirst($u->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex gap-2">
                            <a href="{{ route('users.edit', $u) }}" class="btn-action">Edit</a>
                            @unless ($u->is(auth()->user()))
                                <form method="POST" action="{{ route('users.status', $u) }}"
                                      onsubmit="return confirm(@js(($u->isActive() ? 'Disable ' : 'Enable ') . $u->name . '?'))">
                                    @csrf
                                    @method('PATCH')
                                    <button class="{{ $u->isActive() ? 'btn-action-warning' : 'btn-action-success' }}">
                                        {{ $u->isActive() ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<p class="text-xs text-slate-400 mt-3">
    Disabled users cannot log in. Their past transactions stay on record.
</p>
@endsection