@extends('layouts.admin')

@section('title', 'Backups')

@section('content')
@php
    // Turns bytes into KB / MB (no "intl" PHP extension needed)
    $fileSize = fn ($bytes) => $bytes >= 1048576
        ? number_format($bytes / 1048576, 1) . ' MB'
        : number_format($bytes / 1024, 1) . ' KB';
@endphp

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-semibold">Backups</h1>
        <p class="text-sm text-slate-500">Copies of the database that can be used to restore records if something goes wrong</p>
    </div>
    <form method="POST" action="{{ route('backups.store') }}" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <button type="submit" :disabled="busy"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800 disabled:opacity-60">
            <i class="ti ti-database-export"></i>
            <span x-text="busy ? 'Backing up…' : 'Back up now'">Back up now</span>
        </button>
    </form>
</div>

@if ($isStale)
    <div class="mb-6 flex items-start gap-3 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
        <i class="ti ti-alert-triangle text-lg"></i>
        <p class="flex-1">
            {{ $latest ? 'The last backup is more than a day old.' : 'No backup has been made yet.' }}
            Click <strong>Back up now</strong> to create one.
        </p>
    </div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs text-slate-500">Last backup</p>
        <p class="text-lg font-semibold mt-1">{{ $latest ? $latest['date']->format('M d, Y h:i A') : '—' }}</p>
        <p class="text-xs text-slate-400 mt-1">{{ $latest ? $latest['date']->diffForHumans() : 'Never' }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs text-slate-500">Backups kept</p>
        <p class="text-lg font-semibold mt-1">{{ $backups->count() }}</p>
        <p class="text-xs text-slate-400 mt-1">Older backups are removed automatically</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs text-slate-500">Total size</p>
        <p class="text-lg font-semibold mt-1">{{ $fileSize($totalSize) }}</p>
        <p class="text-xs text-slate-400 mt-1">Database only (records, not program files)</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">Date & Time</th>
                <th class="px-4 py-3 text-left">File</th>
                <th class="px-4 py-3 text-right">Size</th>
                <th class="px-4 py-3 text-right">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($backups as $backup)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 whitespace-nowrap">
                        {{ $backup['date']->format('M d, Y h:i A') }}
                        @if ($loop->first)
                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Latest</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-500">{{ $backup['name'] }}</td>
                    <td class="px-4 py-3 text-right text-slate-500">{{ $fileSize($backup['size']) }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('backups.download', $backup['name']) }}" class="btn-action">
                            <i class="ti ti-download"></i> Download
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-slate-400">No backups yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<p class="mt-4 text-xs text-slate-500">
    <i class="ti ti-info-circle"></i>
    A backup is also made automatically every day. For extra safety, download the latest backup once a week and keep it on a flash drive or another computer.
</p>
@endsection