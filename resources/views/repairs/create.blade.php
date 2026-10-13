@extends('layouts.admin')

@section('title', 'New Repair Job')

@section('content')
<div class="max-w-3xl">
    <h1 class="text-xl font-semibold mb-6">New Repair Job</h1>

    <form method="POST" action="{{ route('repairs.store') }}" class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6">
        @csrf

        @include('repairs._form-fields')

        <div class="flex gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Create Job</button>
            <a href="{{ route('repairs.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Cancel</a>
        </div>
    </form>
</div>
@endsection