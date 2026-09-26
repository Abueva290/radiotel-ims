@extends('layouts.admin')

@section('title', 'Add User')

@section('content')
@php $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500'; @endphp

<div class="max-w-2xl">
    <h1 class="text-xl font-semibold mb-6">Add User</h1>

    <form method="POST" action="{{ route('users.store') }}" class="bg-white rounded-xl border border-slate-200 p-6">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="{{ $input }}">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="name@radiotel.ph" class="{{ $input }}">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Role</label>
                <select name="role" class="{{ $input }}">
                    @foreach (\App\Models\User::ROLES as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', 'staff') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Temporary Password</label>
                <div class="flex gap-2" x-data="{ pw: @js(old('password', '')) }">
                    <input type="text" name="password" x-model="pw" class="{{ $input }}">
                    <button type="button" class="btn-action"
                            @click="pw = 'Rt-' + Math.random().toString(36).slice(2, 8) + Math.floor(Math.random() * 90 + 10)">
                        Generate
                    </button>
                </div>
                @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-slate-400 mt-1">
                    Give this to the employee. They will be required to change it on first login.
                </p>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Create Account</button>
            <a href="{{ route('users.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Cancel</a>
        </div>
    </form>
</div>
@endsection