$code = @'
@extends('layouts.admin')

@section('title', 'Change Password')

@section('content')
@php $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500'; @endphp

<div class="max-w-md">
    @if ($forced)
        <div class="mb-6 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            <p class="font-semibold">Please set your own password</p>
            <p class="mt-1">You are using a temporary password from the administrator. Change it before continuing.</p>
        </div>
    @endif

    <h1 class="text-xl font-semibold mb-6">Change Password</h1>

    <form method="POST" action="{{ route('password.change.store') }}" class="bg-white rounded-xl border border-slate-200 p-6">
        @csrf
        @method('PUT')

        <label class="block text-sm font-medium mb-1">{{ $forced ? 'Temporary Password' : 'Current Password' }}</label>
        <input type="password" name="current_password" class="{{ $input }}" autocomplete="current-password">
        @error('current_password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

        <label class="block text-sm font-medium mb-1 mt-4">New Password</label>
        <input type="password" name="password" class="{{ $input }}" autocomplete="new-password">
        @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        <p class="text-xs text-slate-400 mt-1">At least 8 characters.</p>

        <label class="block text-sm font-medium mb-1 mt-4">Confirm New Password</label>
        <input type="password" name="password_confirmation" class="{{ $input }}" autocomplete="new-password">

        <button class="w-full mt-6 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Save New Password</button>
    </form>
</div>
@endsection
'@
[IO.File]::WriteAllText("$PWD\resources\views\auth\change-password.blade.php", $code)