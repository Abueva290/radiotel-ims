@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="rounded-xl bg-slate-700 text-white px-6 py-5">
        <p class="text-lg font-semibold">Good day, {{ explode(' ', auth()->user()->name)[0] }}!</p>
        <p class="text-sm text-slate-300">Here's an overview of Radiotel operations today.</p>
    </div>
@endsection