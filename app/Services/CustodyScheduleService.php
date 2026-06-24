<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class CustodyScheduleService
{
    /**
     * Number of days shown in the calendar: the current week plus the next two.
     */
    private const WINDOW_DAYS = 21;

    /**
     * Determine the custodial parent role ('father' | 'mother') for a date.
     *
     * Mon/Tue = father, Wed/Thu = mother. Fri/Sat/Sun form a weekend block that
     * alternates every week, anchored so the anchor week's weekend = father.
     */
    public function custodialParentFor(CarbonInterface $date): string
    {
        $dayOfWeek = $date->dayOfWeekIso; // 1 (Mon) .. 7 (Sun)

        return match (true) {
            $dayOfWeek <= 2 => 'father',                 // Mon, Tue
            $dayOfWeek <= 4 => 'mother',                 // Wed, Thu
            default => $this->weekendParent($date), // Fri, Sat, Sun
        };
    }

    /**
     * Resolve the alternating weekend block's parent for a Fri/Sat/Sun date.
     */
    private function weekendParent(CarbonInterface $date): string
    {
        $anchorFriday = Carbon::parse(config('custody.anchor_date'))->startOfDay();

        // The Friday of the date's own week owns its Sat/Sun.
        $friday = $date->dayOfWeekIso === CarbonInterface::FRIDAY
            ? $date->copy()->startOfDay()
            : $date->copy()->startOfDay()->previous(CarbonInterface::FRIDAY);

        $weeks = (int) floor($anchorFriday->diffInDays($friday, false) / 7);

        return $weeks % 2 === 0 ? 'father' : 'mother';
    }

    /**
     * Build the 21-day schedule starting from Monday of the current week.
     *
     * @return array<int, array{date:string, weekday:string, parent:string, label:string, color:string, isToday:bool, isPast:bool}>
     */
    public function threeWeekSchedule(?CarbonInterface $today = null): array
    {
        $today = ($today ? $today->copy() : Carbon::now())->startOfDay();
        $start = $today->copy()->startOfWeek(CarbonInterface::MONDAY);
        $parents = config('custody.parents');

        $days = [];
        for ($i = 0; $i < self::WINDOW_DAYS; $i++) {
            $date = $start->copy()->addDays($i);
            $parent = $this->custodialParentFor($date);

            $days[] = [
                'date' => $date->toDateString(),
                'weekday' => $date->isoFormat('ddd'),
                'parent' => $parent,
                'label' => $parents[$parent]['label'],
                'color' => $parents[$parent]['color'],
                'isToday' => $date->isSameDay($today),
                'isPast' => $date->lessThan($today),
            ];
        }

        return $days;
    }
}
