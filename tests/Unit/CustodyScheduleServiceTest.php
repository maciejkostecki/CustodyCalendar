<?php

namespace Tests\Unit;

use App\Services\CustodyScheduleService;
use Carbon\Carbon;
use Tests\TestCase;

class CustodyScheduleServiceTest extends TestCase
{
    private CustodyScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CustodyScheduleService;

        config()->set('custody.anchor_date', '2026-06-26');
        config()->set('custody.parents', [
            'father' => ['label' => 'Father', 'color' => '#2563eb'],
            'mother' => ['label' => 'Mother', 'color' => '#db2777'],
        ]);
    }

    public function test_weekdays_have_fixed_parents(): void
    {
        // Week of Mon 2026-06-22.
        $this->assertSame('father', $this->service->custodialParentFor(Carbon::parse('2026-06-22'))); // Mon
        $this->assertSame('father', $this->service->custodialParentFor(Carbon::parse('2026-06-23'))); // Tue
        $this->assertSame('mother', $this->service->custodialParentFor(Carbon::parse('2026-06-24'))); // Wed
        $this->assertSame('mother', $this->service->custodialParentFor(Carbon::parse('2026-06-25'))); // Thu
    }

    public function test_anchor_weekend_is_father(): void
    {
        $this->assertSame('father', $this->service->custodialParentFor(Carbon::parse('2026-06-26'))); // Fri
        $this->assertSame('father', $this->service->custodialParentFor(Carbon::parse('2026-06-27'))); // Sat
        $this->assertSame('father', $this->service->custodialParentFor(Carbon::parse('2026-06-28'))); // Sun
    }

    public function test_following_weekend_alternates_to_mother(): void
    {
        $this->assertSame('mother', $this->service->custodialParentFor(Carbon::parse('2026-07-03'))); // Fri
        $this->assertSame('mother', $this->service->custodialParentFor(Carbon::parse('2026-07-04'))); // Sat
        $this->assertSame('mother', $this->service->custodialParentFor(Carbon::parse('2026-07-05'))); // Sun
    }

    public function test_weekend_alternates_back_to_father_two_weeks_on(): void
    {
        $this->assertSame('father', $this->service->custodialParentFor(Carbon::parse('2026-07-10'))); // Fri
    }

    public function test_weekend_before_anchor_alternates_correctly(): void
    {
        // One week before the anchor weekend → mother.
        $this->assertSame('mother', $this->service->custodialParentFor(Carbon::parse('2026-06-19'))); // Fri
    }

    public function test_window_is_21_days_starting_monday(): void
    {
        Carbon::setTestNow('2026-06-25'); // Thursday

        $days = $this->service->threeWeekSchedule();

        $this->assertCount(21, $days);
        $this->assertSame('2026-06-22', $days[0]['date']); // Monday of current week
        $this->assertSame('Mon', $days[0]['weekday']);
        $this->assertSame('2026-07-12', $days[20]['date']); // Sunday, 3 weeks later

        Carbon::setTestNow();
    }

    public function test_today_and_past_flags(): void
    {
        Carbon::setTestNow('2026-06-25'); // Thursday

        $days = collect($this->service->threeWeekSchedule())->keyBy('date');

        $this->assertTrue($days['2026-06-25']['isToday']);
        $this->assertFalse($days['2026-06-25']['isPast']);
        $this->assertTrue($days['2026-06-22']['isPast']);  // Monday, earlier this week
        $this->assertFalse($days['2026-06-22']['isToday']);
        $this->assertFalse($days['2026-06-26']['isPast']);  // tomorrow

        Carbon::setTestNow();
    }
}
