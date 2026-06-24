<?php

namespace App\Http\Controllers;

use App\Services\CustodyScheduleService;
use Illuminate\Support\Facades\Session;

class CalendarController extends Controller
{
    public function index(CustodyScheduleService $schedule)
    {
        if (! Session::has('user')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'days' => $schedule->threeWeekSchedule(),
            'parents' => config('custody.parents'),
        ]);
    }
}
