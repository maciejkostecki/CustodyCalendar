<?php

namespace Tests\Feature;

use App\Models\SwapRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwapRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private const FATHER = ['email' => 'dad@example.com', 'name' => 'Dad', 'avatar' => ''];

    private const MOTHER = ['email' => 'mum@example.com', 'name' => 'Mum', 'avatar' => ''];

    private const STRANGER = ['email' => 'stranger@example.com', 'name' => 'Nobody', 'avatar' => ''];

    private function makeRequest(array $attrs): SwapRequest
    {
        return SwapRequest::create(array_merge([
            'date' => '2026-06-29',
            'requested_by_role' => 'mother',
            'from_role' => 'father',
            'to_role' => 'mother',
            'status' => SwapRequest::STATUS_PENDING,
            'comment' => null,
        ], $attrs));
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('custody.anchor_date', '2026-06-26');
        config()->set('custody.timezone', 'Europe/Warsaw');
        config()->set('custody.parents', [
            'father' => ['label' => 'Father', 'color' => '#2563eb', 'email' => 'dad@example.com'],
            'mother' => ['label' => 'Mother', 'color' => '#db2777', 'email' => 'mum@example.com'],
        ]);

        Carbon::setTestNow('2026-06-25'); // Thursday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_requires_session(): void
    {
        $this->postJson('/swap-requests', ['date' => '2026-06-29'])->assertStatus(401);
    }

    public function test_unknown_parent_is_forbidden(): void
    {
        $this->withSession(['user' => self::STRANGER])
            ->postJson('/swap-requests', ['date' => '2026-06-29'])
            ->assertStatus(403);
    }

    public function test_creates_pending_request_with_flipped_roles(): void
    {
        $response = $this->withSession(['user' => self::FATHER])
            ->postJson('/swap-requests', ['date' => '2026-06-29', 'comment' => 'please']);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'pending',
            'requested_by_role' => 'father',
            'from_role' => 'mother', // Monday default = mother
            'to_role' => 'father',
            'comment' => 'please',
        ]);

        $this->assertDatabaseHas('swap_requests', [
            'date' => '2026-06-29',
            'status' => 'pending',
            'active_date' => '2026-06-29',
        ]);
    }

    public function test_duplicate_pending_is_blocked_with_message(): void
    {
        $this->withSession(['user' => self::FATHER])
            ->postJson('/swap-requests', ['date' => '2026-06-29'])->assertStatus(201);

        $response = $this->withSession(['user' => self::FATHER])
            ->postJson('/swap-requests', ['date' => '2026-06-29']);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'A pending swap request already exists for this day.']);
        $this->assertSame(1, SwapRequest::count());
    }

    public function test_past_or_today_is_rejected(): void
    {
        $this->withSession(['user' => self::FATHER])
            ->postJson('/swap-requests', ['date' => '2026-06-25']) // today
            ->assertStatus(422);

        $this->withSession(['user' => self::FATHER])
            ->postJson('/swap-requests', ['date' => '2026-06-20']) // past
            ->assertStatus(422);
    }

    public function test_invalid_date_fails_validation(): void
    {
        $this->withSession(['user' => self::FATHER])
            ->postJson('/swap-requests', ['date' => 'not-a-date'])
            ->assertStatus(422);
    }

    public function test_calendar_flags_pending_day(): void
    {
        $this->withSession(['user' => self::FATHER])
            ->postJson('/swap-requests', ['date' => '2026-06-29'])->assertStatus(201);

        $days = $this->withSession(['user' => self::FATHER])->getJson('/calendar')->json('days');
        $day = collect($days)->firstWhere('date', '2026-06-29');

        $this->assertTrue($day['pending']);
    }

    public function test_calendar_reflects_approved_swap(): void
    {
        SwapRequest::create([
            'date' => '2026-06-29', // Monday default = mother
            'requested_by_role' => 'father', 'from_role' => 'mother', 'to_role' => 'father',
            'status' => SwapRequest::STATUS_APPROVED,
        ]);

        $days = $this->withSession(['user' => self::FATHER])->getJson('/calendar')->json('days');
        $day = collect($days)->firstWhere('date', '2026-06-29');

        $this->assertSame('father', $day['parent']);
        $this->assertFalse($day['pending']);
    }

    // --- index (incoming pending requests) ---

    public function test_index_requires_session(): void
    {
        $this->getJson('/swap-requests')->assertStatus(401);
    }

    public function test_index_forbids_non_parent(): void
    {
        $this->withSession(['user' => self::STRANGER])->getJson('/swap-requests')->assertStatus(403);
    }

    public function test_index_returns_only_incoming_pending(): void
    {
        // Incoming for father: proposed by mother, still pending.
        $incoming = $this->makeRequest(['date' => '2026-06-29', 'requested_by_role' => 'mother']);
        // Father's own proposal — not incoming for father.
        $this->makeRequest(['date' => '2026-06-30', 'requested_by_role' => 'father', 'from_role' => 'mother', 'to_role' => 'father']);
        // Resolved request — not pending.
        $this->makeRequest(['date' => '2026-07-01', 'requested_by_role' => 'mother', 'status' => SwapRequest::STATUS_APPROVED]);

        $response = $this->withSession(['user' => self::FATHER])->getJson('/swap-requests');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'requests');
        $this->assertSame($incoming->id, $response->json('requests.0.id'));
        $this->assertSame('2026-06-29', $response->json('requests.0.date'));
    }

    public function test_index_is_empty_when_none(): void
    {
        $this->withSession(['user' => self::MOTHER])
            ->getJson('/swap-requests')
            ->assertStatus(200)
            ->assertExactJson(['requests' => []]);
    }

    public function test_index_entry_shape(): void
    {
        $this->makeRequest([
            'date' => '2026-06-29',
            'requested_by_role' => 'mother',
            'from_role' => 'father',
            'comment' => 'swap please',
        ]);

        $entry = $this->withSession(['user' => self::FATHER])->getJson('/swap-requests')->json('requests.0');

        $this->assertSame('2026-06-29', $entry['date']);
        $this->assertSame('father', $entry['from_role']);
        $this->assertSame('Father', $entry['from_label']);
        $this->assertSame('#2563eb', $entry['from_color']);
        $this->assertSame('Mother', $entry['requested_by_label']);
        $this->assertSame('swap please', $entry['comment']);
        $this->assertArrayHasKey('created_at', $entry);
        $this->assertArrayHasKey('weekday', $entry);
    }
}
