@extends('layouts.admin')

@section('title', 'Edit Customer')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-xl font-semibold mb-1">{{ $customer->name }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        {{ $customer->sales_count }} sale(s) · {{ $customer->repair_jobs_count }} repair job(s)
        @if ($customer->isArchived())
            <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">Archived</span>
        @endif
    </p>

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="bg-white rounded-xl border border-slate-200 p-6">
        @csrf
        @method('PUT')
        @include('customers._form')

        <div class="flex gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Save Changes</button>
            <a href="{{ route('customers.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Back</a>
        </div>
    </form>
</div>
@endsection