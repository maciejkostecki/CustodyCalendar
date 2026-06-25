<?php

namespace App\Http\Controllers;

use App\Exceptions\SwapProposalException;
use App\Models\SwapRequest;
use App\Services\ParentResolver;
use App\Services\SwapService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class SwapRequestController extends Controller
{
    /**
     * List the pending swap requests addressed to the logged-in parent
     * (i.e. proposed by the other parent and awaiting this parent's decision).
     */
    public function index(ParentResolver $parents)
    {
        if (! Session::has('user')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $role = $parents->roleForEmail(Session::get('user')['email'] ?? '');
        if ($role === null) {
            return response()->json(['error' => 'Your account is not a recognised parent.'], 403);
        }

        $config = config('custody.parents');

        $requests = SwapRequest::pending()
            ->where('requested_by_role', '!=', $role)
            ->orderBy('date')
            ->get()
            ->map(fn (SwapRequest $r) => [
                'id' => $r->id,
                'date' => $r->date->toDateString(),
                'weekday' => $r->date->isoFormat('ddd'),
                'from_role' => $r->from_role,
                'from_label' => $config[$r->from_role]['label'],
                'from_color' => $config[$r->from_role]['color'],
                'requested_by_role' => $r->requested_by_role,
                'requested_by_label' => $config[$r->requested_by_role]['label'],
                'comment' => $r->comment,
                'created_at' => $r->created_at->toIso8601String(),
            ]);

        return response()->json(['requests' => $requests]);
    }

    public function store(Request $request, ParentResolver $parents, SwapService $swaps)
    {
        if (! Session::has('user')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $role = $parents->roleForEmail(Session::get('user')['email'] ?? '');
        if ($role === null) {
            return response()->json(['error' => 'Your account is not a recognised parent.'], 403);
        }

        // Validate manually: JSON auto-rendering is scoped to api/* routes
        // (bootstrap/app.php), so this session-gated web route returns JSON itself.
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date_format:Y-m-d'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $swap = $swaps->propose($role, Carbon::parse($validated['date']), $validated['comment'] ?? null);
        } catch (SwapProposalException $e) {
            return response()->json(['error' => $e->getMessage()], $e->status());
        }

        return response()->json($swap, 201);
    }

    public function approve(Request $request, SwapRequest $swapRequest, ParentResolver $parents, SwapService $swaps)
    {
        if (! Session::has('user')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $role = $parents->roleForEmail(Session::get('user')['email'] ?? '');
        if ($role === null) {
            return response()->json(['error' => 'Your account is not a recognised parent.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $swap = $swaps->approve($swapRequest, $role, $validator->validated()['comment'] ?? null);
        } catch (SwapProposalException $e) {
            return response()->json(['error' => $e->getMessage()], $e->status());
        }

        return response()->json($swap);
    }
}
