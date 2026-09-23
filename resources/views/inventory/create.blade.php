
@extends('layouts.admin')

@section('title', 'Add Product')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-xl font-semibold mb-6">Add Product</h1>

    <form method="POST" action="{{ route('inventory.store') }}" class="bg-white rounded-xl border border-slate-200 p-6">
        @csrf
        @include('inventory._form')

        <div class="flex gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Save Product</button>
            <a href="{{ route('inventory.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Cancel</a>
        </div>
    </form>
</div>
@endsection