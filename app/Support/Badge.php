<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * One place that decides the label and colour of every status badge,
 * so the same status looks the same on every page, table and report.
 *
 * Usage in Blade:  <x-badge type="repair" :value="$job->status" />
 */
class Badge
{
    /** Tailwind classes per colour tone (kept in app/ so Tailwind scans them). */
    public const TONES = [
        'gray' => 'bg-slate-100 text-slate-700 ring-slate-500/20',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'purple' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
        'teal' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
        'green' => 'bg-green-50 text-green-700 ring-green-600/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/30',
        'red' => 'bg-red-50 text-red-700 ring-red-600/20',
        'dark' => 'bg-slate-700 text-white ring-slate-700',
    ];

    /** type => [value => [label, tone]] */
    public const MAP = [
        // Repair & service workflow
        'repair' => [
            'for_assessment' => ['For Assessment', 'amber'],
            'in_progress' => ['In Progress', 'blue'],
            'waiting_for_parts' => ['Waiting for Parts', 'orange'],
            'completed' => ['Completed', 'teal'],
            'released' => ['Released', 'green'],
            'cancelled' => ['Cancelled', 'gray'],
        ],
        // Service timeframe (computed from target date and timestamps)
        'schedule' => [
            'on_schedule' => ['On Schedule', 'green'],
            'due_soon' => ['Due Soon', 'amber'],
            'overdue' => ['Overdue', 'red'],
            'no_target' => ['No Target Date', 'gray'],
            'finished_on_time' => ['Finished On Time', 'green'],
            'finished_late' => ['Finished Late', 'orange'],
            'not_recorded' => ['Not Recorded', 'gray'],
            'cancelled' => ['Cancelled', 'gray'],
        ],
        // Sales, receivables, payables (overdue is computed from the due date)
        'payment' => [
            'unpaid' => ['Unpaid', 'amber'],
            'partial' => ['Partial', 'blue'],
            'paid' => ['Paid', 'green'],
            'overdue' => ['Overdue', 'red'],
        ],
        // Product stock level
        'stock' => [
            'in_stock' => ['In Stock', 'green'],
            'low_stock' => ['Low Stock', 'amber'],
            'out_of_stock' => ['Out of Stock', 'red'],
            'discontinued' => ['Discontinued', 'gray'],
        ],
        // A single serialized unit
        'unit' => [
            'in_stock' => ['In Stock', 'green'],
            'sold' => ['Sold', 'blue'],
            'under_inspection' => ['Under Inspection', 'purple'],
            'defective' => ['Defective', 'red'],
            'returned_to_supplier' => ['Returned to Supplier', 'orange'],
            'written_off' => ['Written Off', 'gray'],
        ],
        'warranty' => [
            // "Within" the period only: actual coverage still depends on the warranty conditions
            'active' => ['Within Warranty', 'green'],
            'expiring_soon' => ['Warranty Ending Soon', 'amber'],
            'expired' => ['Warranty Expired', 'red'],
            'not_applicable' => ['No Warranty Info', 'gray'],
        ],
        'defect' => [
            'reported' => ['Reported', 'amber'],
            'under_inspection' => ['Under Inspection', 'purple'],
            'confirmed' => ['Confirmed Factory Defect', 'red'],
            'returned_to_supplier' => ['Returned to Supplier', 'orange'],
            'repaired' => ['Repaired', 'teal'],
            'replaced' => ['Replaced', 'blue'],
            'resolved' => ['Resolved', 'green'],
        ],
        'cause' => [
            'unverified' => ['Not Yet Determined', 'gray'],
            'factory_defect' => ['Factory Defect', 'red'],
            'customer_damage' => ['Customer-Caused Damage', 'orange'],
            'wear_and_tear' => ['Normal Wear & Tear', 'amber'],
            'other' => ['Other Issue', 'gray'],
        ],
        'account' => [
            'active' => ['Active', 'green'],
            'disabled' => ['Disabled', 'red'],
        ],
        'record' => [
            'active' => ['Active', 'green'],
            'archived' => ['Archived', 'amber'],
        ],
        'role' => [
            'admin' => ['Admin / Operational Manager', 'dark'],
            'secretary' => ['Secretary', 'blue'],
            'technical_head' => ['Technical Head', 'purple'],
            'technician' => ['Technician', 'teal'],
            'staff' => ['Staff', 'gray'],
        ],
        'audit' => [
            'created' => ['Created', 'green'],
            'updated' => ['Updated', 'blue'],
            'deleted' => ['Deleted', 'red'],
            'login' => ['Login', 'gray'],
            'logout' => ['Logout', 'gray'],
            'backup' => ['Backup', 'purple'],
            'download' => ['Download', 'teal'],
        ],
    ];

    /** @return array{0:string,1:string} [label, tone] */
    public static function resolve(string $type, ?string $value): array
    {
        return self::MAP[$type][$value] ?? [$value ? ucwords(str_replace('_', ' ', $value)) : '—', 'gray'];
    }

    public static function label(string $type, ?string $value): string
    {
        return self::resolve($type, $value)[0];
    }

    public static function options(string $type): array
    {
        return array_map(fn ($v) => $v[0], self::MAP[$type] ?? []);
    }

    public static function classes(string $tone): string
    {
        return self::TONES[$tone] ?? self::TONES['gray'];
    }

    /**
     * Plain-CSS version for printable reports and PDFs (DomPDF has no Tailwind):
     *   {!! \App\Support\Badge::tag('repair', $job->status) !!}
     */
    public static function tag(string $type, ?string $value): string
    {
        [$label, $tone] = self::resolve($type, $value);

        return '<span class="tag t-'.e($tone).'">'.e($label).'</span>';
    }

    /**
     * Payment status shown to users: an unpaid/partial balance past its due date is "Overdue".
     */
    public static function paymentStatus(?string $status, ?CarbonInterface $dueDate): string
    {
        if ($status !== 'paid' && $dueDate && $dueDate->copy()->startOfDay()->lt(today())) {
            return 'overdue';
        }

        return $status ?? 'unpaid';
    }
}