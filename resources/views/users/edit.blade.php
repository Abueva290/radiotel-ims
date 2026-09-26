@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
@php $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500'; @endphp

<h1 class="text-xl font-semibold mb-1">{{ $user->name }}</h1>
<p class="text-sm text-slate-500 mb-6">
    Last login: {{ $user->last_login_at?->format('M d, Y h:i A') ?? 'Never' }}
    @if ($user->must_change_password)
        <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">Pending password change</span>
    @endif
</p>

<div class="grid grid-cols-3 gap-6">
    <form method="POST" action="{{ route('users.update', $user) }}"
          class="col-span-2 bg-white rounded-xl border border-slate-200 p-6">
        @csrf
        @method('PUT')
        <h2 class="font-semibold mb-4">Account Details</h2>

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="{{ $input }}">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="{{ $input }}">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Role</label>
                <select name="role" class="{{ $input }}">
                    @foreach (\App\Models\User::ROLES as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Save Changes</button>
            <a href="{{ route('users.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Back</a>
        </div>
    </form>

    <form method="POST" action="{{ route('users.password', $user) }}"
          class="bg-white rounded-xl border border-slate-200 p-6 self-start">
        @csrf
        @method('PUT')
        <h2 class="font-semibold">Reset Password</h2>
        <p class="text-sm text-slate-500 mb-4">For employees who forgot their password.</p>

        <div x-data="{ pw: '' }">
            <label class="block text-sm font-medium mb-1">New Temporary Password</label>
            <input type="text" name="password" x-model="pw" class="{{ $input }}">
            <button type="button" class="btn-action mt-2"
                    @click="pw = 'Rt-' + Math.random().toString(36).slice(2, 8) + Math.floor(Math.random() * 90 + 10)">
                Generate
            </button>
        </div>

        <button class="w-full mt-4 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Set Temporary Password</button>
        <p class="text-xs text-slate-400 mt-2">They will be required to change it on next login.</p>
    </form>
</div>
@endsection