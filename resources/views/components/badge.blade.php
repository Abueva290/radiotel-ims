{{--
    Status badge used on every page so colours and labels stay consistent.
      <x-badge type="repair" :value="$job->status" />
      <x-badge tone="green">Latest</x-badge>
--}}
@props(['type' => null, 'value' => null, 'tone' => null, 'size' => 'sm', 'dot' => false])

@php
    [$label, $resolvedTone] = $type ? \App\Support\Badge::resolve($type, $value) : [null, 'gray'];
    $tone = $tone ?? $resolvedTone;
    $sizeCls = $size === 'md' ? 'px-2.5 py-1 text-xs' : 'px-2 py-0.5 text-xs';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full font-medium whitespace-nowrap ring-1 ring-inset leading-5 {$sizeCls} " . \App\Support\Badge::classes($tone)]) }}>
    @if ($dot)<span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>@endif
    {{ $slot->isEmpty() ? $label : $slot }}
</span>