{{--
    One summary card in a report. On the Reports page ($cardLinks = true) the card is a link
    to the list behind its number; in the printable report and PDF it stays plain text.
    @include('reports.partials._card', ['label' => 'Transactions', 'value' => 13, 'url' => route(...)])
--}}
@php $isLink = ($cardLinks ?? false) && ! empty($url); @endphp
<td @if (! empty($style)) style="{{ $style }}" @endif class="{{ $isLink ? 'is-link' : '' }}">
    @if ($isLink)
        <a href="{{ $url }}" class="card-link" aria-label="{{ $label }}: {{ strip_tags((string) $value) }}. Open the list.">
            <div class="label">{{ $label }} <span class="card-arrow" aria-hidden="true">→</span></div>
            <div class="value">{{ $value }}</div>
        </a>
    @else
        <div class="label">{{ $label }}</div>
        <div class="value">{{ $value }}</div>
    @endif
</td>