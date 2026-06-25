<?php

namespace App\Http\Controllers;

use App\Services\CustodyScheduleService;
use App\Services\SwapService;
use Illuminate\Support\Facades\Session;

class CalendarController extends Controller
{
    public function index(CustodyScheduleService $schedule, SwapService $swaps)
    {
        if (! Session::has('user')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $days = $swaps->applyToSchedule($schedule->threeWeekSchedule());

        return response()->json([
            'days' => $days,
            'parents' => config('custody.parents'),
        ]);
    }
}
