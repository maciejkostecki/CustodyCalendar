<?php

namespace Tests\Unit;

use App\Exceptions\DuplicatePendingRequestException;
use App\Exceptions\NotTheReceivingParentException;
use App\Exceptions\PastDateNotAllowedException;
use App\Exceptions\RequestNotPendingException;
use App\Models\SwapRequest;
use App\Services\SwapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwapServiceTest extends TestCase
{
    use RefreshDatabase;

    private SwapService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SwapService::class);

        config()->set('custody.anchor_date', '2026-06-26');
        config()->set('custody.timezone', 'Europe/Warsaw');
        config()->set('custody.parents', [
            'father' => ['label' => 'Father', 'color' => '#2563eb', 'email' => 'dad@example.com'],
            'mother' => ['label' => 'Mother', 'color' => '#db2777', 'email' => 'mum@example.com'],
        ]);

        Carbon::setTestNow('2026-06-25');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_effective_role_uses_default_schedule_without_approved_swap(): void
    {
        // Monday 2026-06-29 = mother by default.
        $this->assertSame('mother', $this->service->effectiveRoleFor(Carbon::parse('2026-06-29')));
    }

    public function test_effective_role_reflects_approved_swap(): void
    {
        SwapRequest::create([
            'date' => '2026-06-29',
            'requested_by_role' => 'father',
            'from_role' => 'mother',
            'to_role' => 'father',
            'status' => SwapRequest::STATUS_APPROVED,
        ]);

        $this->assertSame('father', $this->service->effectiveRoleFor(Carbon::parse('2026-06-29')));
    }

    public function test_propose_creates_pending_request_with_flipped_roles(): void
    {
        $request = $this->service->propose('father', Carbon::parse('2026-06-29'), 'please');

        $this->assertSame(SwapRequest::STATUS_PENDING, $request->status);
        $this->assertSame('father', $request->requested_by_role);
        $this->assertSame('mother', $request->from_role); // Mon default = mother
        $this->assertSame('father', $request->to_role);
        $this->assertSame('please', $request->comment);
        $this->assertSame('2026-06-29', $request->active_date->toDateString());
    }

    public function test_propose_rejects_today_and_past(): void
    {
        $this->expectException(PastDateNotAllowedException::class);
        $this->service->propose('father', Carbon::parse('2026-06-25'), null); // today
    }

    public function test_propose_rejects_duplicate_pending(): void
    {
        $this->service->propose('father', Carbon::parse('2026-06-29'), null);

        $this->expectException(DuplicatePendingRequestException::class);
        $this->service->propose('mother', Carbon::parse('2026-06-29'), null);
    }

    public function test_apply_to_schedule_sets_pending_and_overrides_approved(): void
    {
        SwapRequest::create([
            'date' => '2026-06-29',
            'requested_by_role' => 'father', 'from_role' => 'mother', 'to_role' => 'father',
            'status' => SwapRequest::STATUS_APPROVED,
        ]);
        SwapRequest::create([
            'date' => '2026-06-30',
            'requested_by_role' => 'father', 'from_role' => 'mother', 'to_role' => 'father',
            'status' => SwapRequest::STATUS_PENDING,
        ]);

        $days = [
            ['date' => '2026-06-29', 'parent' => 'mother', 'label' => 'Mother', 'color' => '#db2777'],
            ['date' => '2026-06-30', 'parent' => 'mother', 'label' => 'Mother', 'color' => '#db2777'],
        ];

        $result = $this->service->applyToSchedule($days);

        // Approved swap overrides the custodial parent.
        $this->assertSame('father', $result[0]['parent']);
        $this->assertSame('Father', $result[0]['label']);
        $this->assertFalse($result[0]['pending']);

        // Pending request only flags the day; its marker colour is the
        // proposed (to_role) parent, opposite the current custodial colour.
        $this->assertSame('mother', $result[1]['parent']);
        $this->assertTrue($result[1]['pending']);
        $this->assertSame('#2563eb', $result[1]['pending_color']); // to_role = father
        $this->assertNull($result[0]['pending_color']); // approved, not pending
    }

    public function test_approve_sets_status_comment_and_frees_active_date(): void
    {
        $request = $this->service->propose('mother', Carbon::parse('2026-06-29'), null);
        $this->assertSame('2026-06-29', $request->active_date->toDateString());

        $approved = $this->service->approve($request, 'father', 'ok by me');

        $this->assertSame(SwapRequest::STATUS_APPROVED, $approved->status);
        $this->assertSame('ok by me', $approved->decision_comment);
        $this->assertNull($approved->fresh()->active_date); // freed for the unique-pending constraint
    }

    public function test_approve_rejects_non_pending(): void
    {
        $request = $this->service->propose('mother', Carbon::parse('2026-06-29'), null);
        $this->service->approve($request, 'father', null);

        $this->expectException(RequestNotPendingException::class);
        $this->service->approve($request, 'father', null);
    }

    public function test_approve_rejects_when_decider_is_requester(): void
    {
        $request = $this->service->propose('mother', Carbon::parse('2026-06-29'), null);

        $this->expectException(NotTheReceivingParentException::class);
        $this->service->approve($request, 'mother', null);
    }

    public function test_reject_sets_status_comment_and_frees_active_date(): void
    {
        $request = $this->service->propose('mother', Carbon::parse('2026-06-29'), null);

        $rejected = $this->service->reject($request, 'father', 'not this time');

        $this->assertSame(SwapRequest::STATUS_REJECTED, $rejected->status);
        $this->assertSame('not this time', $rejected->decision_comment);
        $this->assertNull($rejected->fresh()->active_date);
    }

    public function test_reject_rejects_non_pending(): void
    {
        $request = $this->service->propose('mother', Carbon::parse('2026-06-29'), null);
        $this->service->reject($request, 'father', null);

        $this->expectException(RequestNotPendingException::class);
        $this->service->reject($request, 'father', null);
    }

    public function test_reject_rejects_when_decider_is_requester(): void
    {
        $request = $this->service->propose('mother', Carbon::parse('2026-06-29'), null);

        $this->expectException(NotTheReceivingParentException::class);
        $this->service->reject($request, 'mother', null);
    }
}
