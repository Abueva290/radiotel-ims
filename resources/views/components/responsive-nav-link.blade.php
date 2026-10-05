@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 rounded-lg text-start text-base font-medium text-slate-900 bg-slate-100 focus:outline-none focus:bg-slate-200 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 rounded-lg text-start text-base font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-50 focus:outline-none focus:bg-slate-50 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
