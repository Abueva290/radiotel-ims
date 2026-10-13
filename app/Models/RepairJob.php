<?php

namespace App\Models;

use App\Support\Warranty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RepairJob extends Model
{
    public const SERVICE_FEE_PER_UNIT = 350;

    /** A job whose target date is this many days away (or less) is "Due Soon". */
    public const DUE_SOON_DAYS = 2;

    /** Existing statuses are kept; Waiting for Parts and Cancelled were added (panel revision 6). */
    public const STATUSES = [
        'for_assessment'    => 'For Assessment',
        'in_progress'       => 'In Progress',
        'waiting_for_parts' => 'Waiting for Parts',
        'completed'         => 'Completed',
        'released'          => 'Released',
        'cancelled'         => 'Cancelled',
    ];

    /** Work is not finished yet (these are the only jobs that can be overdue). */
    public const OPEN_STATUSES = ['for_assessment', 'in_progress', 'waiting_for_parts'];

    /** Not closed yet (the unit is still in the shop). */
    public const ACTIVE_STATUSES = ['for_assessment', 'in_progress', 'waiting_for_parts', 'completed'];

    /** Jobs in these statuses are billed (Completed creates the receivable, as before). */
    public const BILLED_STATUSES = ['completed', 'released'];

    /**
     * Allowed status changes. Anything else needs an authorized override with remarks.
     *   For Assessment → In Progress → Completed → Released
     *   In Progress ⇄ Waiting for Parts
     *   Cancelled only before the work is completed (nothing billed yet)
     */
    public const TRANSITIONS = [
        'for_assessment'    => ['in_progress', 'waiting_for_parts', 'cancelled'],
        'in_progress'       => ['waiting_for_parts', 'completed', 'cancelled'],
        'waiting_for_parts' => ['in_progress', 'cancelled'],
        'completed'         => ['released'],
        'released'          => [],
        'cancelled'         => [],
    ];

    /** Roles that can assign jobs and override the workflow. */
    public const SUPERVISOR_ROLES = ['admin', 'technical_head'];

    /** Roles that can be assigned to a job. */
    public const TECHNICIAN_ROLES = ['technician', 'technical_head'];

    protected $fillable = [
        'job_no', 'customer_id', 'assessed_by', 'assigned_to', 'assigned_by', 'assigned_at',
        'unit_model', 'units', 'assessment_notes', 'date_received', 'received_at',
        'target_completion_date', 'started_at', 'completed_at', 'released_at', 'cancelled_at',
        'service_fee', 'parts_cost', 'total_amount', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date_received' => 'date',
            'received_at' => 'datetime',
            'target_completion_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'released_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'assigned_at' => 'datetime',
            'service_fee' => 'decimal:2',
            'parts_cost' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    // total = (units x 350) + sum of parts used
    public function recalculateTotals(): void
    {
        $this->service_fee = $this->units * self::SERVICE_FEE_PER_UNIT;
        $this->parts_cost = $this->parts()->sum('subtotal');
        $this->total_amount = $this->service_fee + $this->parts_cost;
        $this->save();
    }

    public static function nextJobNo(): string
    {
        $year = now()->year;
        $prefix = "RS-{$year}-";
        $last = static::where('job_no', 'like', $prefix.'%')->orderByDesc('job_no')->value('job_no');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }

    // ---------------------------------------------------------------- relations

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /** The employee who received/assessed the unit when the job was created. */
    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /** The technician currently responsible for the job. */
    public function technician()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignments()
    {
        return $this->hasMany(RepairJobAssignment::class)->latest('assigned_at')->latest('id');
    }

    public function statusLogs()
    {
        return $this->hasMany(RepairJobStatusLog::class)->latest('changed_at')->latest('id');
    }

    /** Serial-numbered units in this job (named serialUnits because "units" is already the count column). */
    public function serialUnits()
    {
        return $this->hasMany(RepairJobUnit::class);
    }

    public function parts()
    {
        return $this->hasMany(RepairPartUsed::class);
    }

    public function receivable()
    {
        return $this->hasOne(Receivable::class);
    }

    public function defects()
    {
        return $this->hasMany(FactoryDefect::class);
    }

    // ---------------------------------------------------------------- scopes

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->whereIn('status', self::OPEN_STATUSES)
            ->whereNotNull('target_completion_date')
            ->whereDate('target_completion_date', '<', today());
    }

    public function scopeDueSoon(Builder $q): Builder
    {
        return $q->whereIn('status', self::OPEN_STATUSES)
            ->whereBetween('target_completion_date', [today()->toDateString(), today()->addDays(self::DUE_SOON_DAYS)->toDateString()]);
    }

    /** Still in the shop and at least one unit is within its warranty period today. */
    public function scopeUnderWarranty(Builder $q): Builder
    {
        return $q->whereIn('status', self::ACTIVE_STATUSES)
            ->whereHas('serialUnits', fn ($u) => $u->whereDate('warranty_end', '>=', today()));
    }

    /**
     * Every filter on the Repair & Service page. The dashboard KPI cards use the
     * same method, so a card's number always matches the list it opens.
     */
    public function scopeFilter(Builder $q, array $f): Builder
    {
        $status = $f['status'] ?? null;

        return $q
            ->when($status === 'open', fn ($q) => $q->whereIn('status', self::OPEN_STATUSES))
            ->when($status === 'active', fn ($q) => $q->whereIn('status', self::ACTIVE_STATUSES))
            // Work finished = has a completion time; finished_late = completed after its target date
            ->when($status === 'finished', fn ($q) => $q->whereNotNull('completed_at'))
            ->when($status === 'finished_late', fn ($q) => $q->whereNotNull('completed_at')->whereNotNull('target_completion_date')
                ->whereRaw('DATE(completed_at) > target_completion_date'))
            ->when($status && isset(self::STATUSES[$status]), fn ($q) => $q->where('status', $status))
            ->when(($f['technician'] ?? null) === 'unassigned', fn ($q) => $q->whereNull('assigned_to'))
            ->when(is_numeric($f['technician'] ?? null), fn ($q) => $q->where('assigned_to', $f['technician']))
            ->when($f['received_from'] ?? null, fn ($q, $v) => $q->whereDate('date_received', '>=', $v))
            ->when($f['received_to'] ?? null, fn ($q, $v) => $q->whereDate('date_received', '<=', $v))
            ->when($f['due_from'] ?? null, fn ($q, $v) => $q->whereDate('target_completion_date', '>=', $v))
            ->when($f['due_to'] ?? null, fn ($q, $v) => $q->whereDate('target_completion_date', '<=', $v))
            ->when(! empty($f['overdue']), fn ($q) => $q->overdue())
            ->when(($f['warranty'] ?? null) === 'active', fn ($q) => $q->underWarranty())
            ->when(trim($f['q'] ?? '') !== '', function ($q) use ($f) {
                $term = '%'.trim($f['q']).'%';
                $q->where(fn ($w) => $w
                    ->where('job_no', 'like', $term)
                    ->orWhere('unit_model', 'like', $term)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term))
                    ->orWhereHas('serialUnits', fn ($u) => $u->where('serial_no', 'like', $term)));
            });
    }

    // ---------------------------------------------------------------- workflow

    public function isLocked(): bool
    {
        return ! in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['released', 'cancelled'], true);
    }

    /** Next statuses allowed by the normal workflow. */
    public function nextStatuses(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    public function canMoveTo(string $status): bool
    {
        return in_array($status, $this->nextStatuses(), true);
    }

    /** Admin and Technical Head can do anything; a technician only on jobs assigned to them. */
    public function canBeWorkedOnBy(User $user): bool
    {
        if ($user->hasRole(...self::SUPERVISOR_ROLES)) {
            return true;
        }

        return $user->hasRole('technician') && (int) $this->assigned_to === (int) $user->id;
    }

    // ---------------------------------------------------------------- timeframe

    /** False for older jobs where only the date (not the time) was recorded. */
    public function hasReceivedTime(): bool
    {
        return $this->received_at !== null;
    }

    public function receivedAt(): Carbon
    {
        return $this->received_at ?? $this->date_received->copy()->startOfDay();
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true)
            && $this->target_completion_date
            && $this->target_completion_date->lt(today());
    }

    /** on_schedule | due_soon | overdue | no_target | finished_on_time | finished_late | not_recorded | cancelled */
    public function scheduleStatus(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if (! $this->target_completion_date) {
            return 'no_target';
        }

        if (! in_array($this->status, self::OPEN_STATUSES, true)) {
            $finished = $this->completed_at ?? $this->released_at;

            if (! $finished) {
                return 'not_recorded'; // finished before timestamps were tracked
            }

            return $finished->copy()->startOfDay()->gt($this->target_completion_date)
                ? 'finished_late'
                : 'finished_on_time';
        }

        if ($this->target_completion_date->lt(today())) {
            return 'overdue';
        }

        return today()->diffInDays($this->target_completion_date) <= self::DUE_SOON_DAYS ? 'due_soon' : 'on_schedule';
    }

    /** Days late (positive) or days left (negative) for open jobs. */
    public function daysPastTarget(): ?int
    {
        if (! $this->target_completion_date) {
            return null;
        }

        return (int) $this->target_completion_date->diffInDays(today(), false);
    }

    /** Planned turnaround in days (received date → target date). */
    public function plannedTurnaroundDays(): ?int
    {
        return $this->target_completion_date
            ? (int) $this->date_received->diffInDays($this->target_completion_date)
            : null;
    }

    /** Time from receiving the unit until the work was completed; null while unfinished. */
    public function completionDuration(): ?string
    {
        if (! $this->completed_at) {
            return null;
        }

        return self::humanDuration($this->receivedAt(), $this->completed_at);
    }

    /** Time since the unit was received; stops counting once the work is completed or cancelled. */
    public function elapsed(): ?string
    {
        $end = $this->completed_at ?? $this->cancelled_at
            ?? (in_array($this->status, self::OPEN_STATUSES, true) ? now() : null);

        // Closed before timestamps were tracked: the end time is unknown, so no duration is shown
        return $end ? self::humanDuration($this->receivedAt(), $end) : null;
    }

    public static function humanDuration(Carbon $from, Carbon $to): string
    {
        if ($to->lt($from)) {
            return '—';
        }

        $minutes = (int) $from->diffInMinutes($to);
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);

        if ($days > 0) {
            return $days.'d '.$hours.'h';
        }

        return $hours > 0 ? $hours.'h '.($minutes % 60).'m' : ($minutes % 60).'m';
    }

    // ---------------------------------------------------------------- warranty

    /** Best warranty state among the units in this job. */
    public function warrantyStatus(): string
    {
        $order = ['active' => 0, 'expiring_soon' => 1, 'expired' => 2, 'not_applicable' => 3];

        return $this->serialUnits
            ->map(fn ($u) => Warranty::status($u->warranty_start, $u->warranty_end))
            ->sortBy(fn ($s) => $order[$s])
            ->first() ?? 'not_applicable';
    }
}