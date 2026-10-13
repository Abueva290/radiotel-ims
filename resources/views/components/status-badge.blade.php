{{--
    One badge for every status in the system, so size, shape and colors are always the same.

    <x-status-badge status="in_progress" />                      known status -> label and color
    <x-status-badge tone="red" label="Overdue" />                custom label and color
    Tones: green, red, amber, blue, violet, slate (admin is dark)
--}}
@props(['status' => null, 'label' => null, 'tone' => null])

@php
    $known = [
        // money
        'unpaid'          => ['red',    'Unpaid'],
        'partial'         => ['amber',  'Partial'],
        'paid'            => ['green',  'Paid'],
        'overdue'         => ['red',    'Overdue'],
        'due_soon'        => ['amber',  'Due soon'],
        'current'         => ['slate',  'Current'],
        // repair workflow
        'for_assessment'  => ['amber',  'For Assessment'],
        'in_progress'     => ['blue',   'In Progress'],
        'completed'       => ['green',  'Completed'],
        'released'        => ['violet', 'Released'],
        // repair extras
        'in_warranty'     => ['green',  'In warranty'],
        'out_of_warranty' => ['slate',  'Out of warranty'],
        'factory_defect'  => ['red',    'Factory defect'],
        // inventory
        'In Stock'        => ['green',  'In Stock'],
        'Low Stock'       => ['amber',  'Low Stock'],
        'Out of Stock'    => ['red',    'Out of Stock'],
        // records and accounts
        'active'          => ['green',  'Active'],
        'archived'        => ['slate',  'Archived'],
        'disabled'        => ['red',    'Disabled'],
        'inactive'        => ['red',    'Disabled'],
        'pending'         => ['amber',  'Pending'],
        'latest'          => ['green',  'Latest'],
    ];

    [$knownTone, $knownLabel] = $known[$status] ?? ['slate', ucfirst(str_replace('_', ' ', (string) $status))];
    $tone  = $tone ?? $knownTone;
    $label = $label ?? $knownLabel;
@endphp

<span {{ $attributes->class(['sb', 'sb-' . $tone]) }}><i class="sb-dot"></i>{{ $label }}</span>