<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Warranty status is always calculated from the stored dates Ã¢â‚¬â€ never typed in Ã¢â‚¬â€
 * so an expired warranty can never show as active.
 */
class Warranty
{
    /** A warranty ending within this many days is "Expiring Soon". */
    public const EXPIRING_SOON_DAYS = 30;

    public static function status(?CarbonInterface $start, ?CarbonInterface $end): string
    {
        if (! $end) {
            return 'not_applicable';
        }

        $today = today();
        $end = Carbon::parse($end)->startOfDay();

        if ($end->lt($today)) {
            return 'expired';
        }

        return $today->diffInDays($end) <= self::EXPIRING_SOON_DAYS ? 'expiring_soon' : 'active';
    }

    /** Whole days left (0 on the last day), or null when expired / not applicable. */
    public static function daysLeft(?CarbonInterface $end): ?int
    {
        if (! $end) {
            return null;
        }

        $end = Carbon::parse($end)->startOfDay();

        return $end->lt(today()) ? null : (int) today()->diffInDays($end);
    }

    public static function covered(?CarbonInterface $end): bool
    {
        return in_array(self::status(null, $end), ['active', 'expiring_soon'], true);
    }

    /** "12 days left", "5 months left", "Last day today" */
    public static function remainingText(?CarbonInterface $end): ?string
    {
        $days = self::daysLeft($end);

        if ($days === null) {
            return null;
        }
        if ($days === 0) {
            return 'Last day today';
        }
        if ($days < 60) {
            return $days.' '.str('day')->plural($days).' left';
        }

        $months = (int) today()->diffInMonths(Carbon::parse($end)->startOfDay());

        return $months.' '.str('month')->plural($months).' left';
    }
}
