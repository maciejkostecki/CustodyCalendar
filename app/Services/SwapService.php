<?php

namespace App\Services;

use App\Exceptions\DuplicatePendingRequestException;
use App\Exceptions\NotTheReceivingParentException;
use App\Exceptions\PastDateNotAllowedException;
use App\Exceptions\RequestNotPendingException;
use App\Models\SwapRequest;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SwapService
{
    public function __construct(
        private CustodyScheduleService $schedule,
        private ParentResolver $parents,
    ) {}

    /**
     * Start of the current day in the configured custody timezone.
     */
    public function today(): CarbonInterface
    {
        return Carbon::now(config('custody.timezone'))->startOfDay();
    }

    /**
     * The effective custodial role for a date: the default schedule, overridden
     * by an approved swap's target role if one exists for that day.
     */
    public function effectiveRoleFor(CarbonInterface $date): string
    {
        $approved = SwapRequest::approved()
            ->whereDate('date', $date->toDateString())
            ->first();

        return $approved?->to_role ?? $this->schedule->custodialParentFor($date);
    }

    /**
     * Overlay approved swaps and pending markers onto a default schedule array.
     *
     * @param  array<int, array<string, mixed>>  $days
     * @return array<int, array<string, mixed>>
     */
    public function applyToSchedule(array $days): array
    {
        if ($days === []) {
            return $days;
        }

        $dates = array_column($days, 'date');
        $requests = SwapRequest::whereIn('date', $dates)
            ->whereIn('status', [SwapRequest::STATUS_APPROVED, SwapRequest::STATUS_PENDING])
            ->get();

        $approvedByDate = $requests->where('status', SwapRequest::STATUS_APPROVED)
            ->keyBy(fn (SwapRequest $r) => $r->date->toDateString());
        $pendingDates = $requests->where('status', SwapRequest::STATUS_PENDING)
            ->map(fn (SwapRequest $r) => $r->date->toDateString())
            ->flip();

        $parents = config('custody.parents');

        return array_map(function (array $day) use ($approvedByDate, $pendingDates, $parents) {
            if ($approvedByDate->has($day['date'])) {
                $role = $approvedByDate[$day['date']]->to_role;
                $day['parent'] = $role;
                $day['label'] = $parents[$role]['label'];
                $day['color'] = $parents[$role]['color'];
            }

            $day['pending'] = $pendingDates->has($day['date']);

            return $day;
        }, $days);
    }

    /**
     * Create a pending swap request for a future date.
     *
     * @throws PastDateNotAllowedException
     * @throws DuplicatePendingRequestException
     */
    public function propose(string $requesterRole, CarbonInterface $date, ?string $comment): SwapRequest
    {
        $date = $date->copy()->startOfDay();

        // Compare as calendar dates so timezone offsets can't shift the boundary.
        if ($date->toDateString() <= $this->today()->toDateString()) {
            throw new PastDateNotAllowedException;
        }

        $hasPending = SwapRequest::pending()
            ->whereDate('date', $date->toDateString())
            ->exists();

        if ($hasPending) {
            throw new DuplicatePendingRequestException;
        }

        $fromRole = $this->effectiveRoleFor($date);

        return SwapRequest::create([
            'date' => $date->toDateString(),
            'requested_by_role' => $requesterRole,
            'from_role' => $fromRole,
            'to_role' => $this->parents->otherRole($fromRole),
            'status' => SwapRequest::STATUS_PENDING,
            'comment' => $comment,
        ]);
    }

    /**
     * Approve a pending request on behalf of the receiving (deciding) parent.
     *
     * @throws RequestNotPendingException
     * @throws NotTheReceivingParentException
     */
    public function approve(SwapRequest $request, string $deciderRole, ?string $comment): SwapRequest
    {
        if ($request->status !== SwapRequest::STATUS_PENDING) {
            throw new RequestNotPendingException;
        }

        // Only the parent who received the proposal may decide on it.
        if ($deciderRole === $request->requested_by_role) {
            throw new NotTheReceivingParentException;
        }

        $request->update([
            'status' => SwapRequest::STATUS_APPROVED,
            'decision_comment' => $comment,
        ]);

        return $request;
    }
}
