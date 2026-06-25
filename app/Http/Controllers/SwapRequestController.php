<?php

namespace App\Http\Controllers;

use App\Exceptions\SwapProposalException;
use App\Services\ParentResolver;
use App\Services\SwapService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class SwapRequestController extends Controller
{
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
}
