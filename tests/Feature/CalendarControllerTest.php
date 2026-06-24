<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    private const USER = [
        'email' => 'test@example.com',
        'name' => 'Test',
        'avatar' => '',
    ];

    public function test_calendar_returns_401_when_no_session(): void
    {
        $response = $this->getJson('/calendar');

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Unauthenticated']);
    }

    public function test_calendar_returns_21_days_and_parents_with_session(): void
    {
        $response = $this->withSession(['user' => self::USER])->getJson('/calendar');

        $response->assertStatus(200);
        $response->assertJsonCount(21, 'days');
        $response->assertJsonStructure([
            'days' => [['date', 'weekday', 'parent', 'label', 'color', 'isToday', 'isPast']],
            'parents' => ['father' => ['label', 'color'], 'mother' => ['label', 'color']],
        ]);
    }

    public function test_calendar_window_starts_monday_and_flags_today(): void
    {
        Carbon::setTestNow('2026-06-25'); // Thursday

        $response = $this->withSession(['user' => self::USER])->getJson('/calendar');

        $days = $response->json('days');
        $this->assertSame('2026-06-22', $days[0]['date']); // Monday of current week

        $today = collect($days)->firstWhere('date', '2026-06-25');
        $this->assertTrue($today['isToday']);
        $this->assertSame('father', $today['parent']); // Thursday = father

        $anchorFri = collect($days)->firstWhere('date', '2026-06-26');
        $this->assertSame('father', $anchorFri['parent']); // anchor weekend = father

        Carbon::setTestNow();
    }
}
