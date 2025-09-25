<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function dailySchedule(Request $r, ReportsService $rep)
    {
        $date = $r->input('date', now()->toDateString());

        return response()->json($rep->dailySchedule($date, $r->all()));
    }

    public function weeklySchedule(Request $r, ReportsService $rep)
    {
        $weekStart = $r->input('start', now()->startOfWeek()->toDateString());

        return response()->json($rep->weeklySchedule($weekStart, $r->all()));
    }

    public function productionByProfessional(Request $r, ReportsService $rep)
    {
        $start = $r->input('start', now()->startOfMonth()->toDateString());
        $end = $r->input('end', now()->toDateString());

        return response()->json($rep->productionByProfessional($start, $end));
    }

    public function monthlyComparison(Request $r, ReportsService $rep)
    {
        $months = (int) $r->input('months', 6);
        $until = $r->input('until'); // YYYY-MM

        return response()->json($rep->monthlyComparison($months, $until));
    }

    public function datasetAppointments(Request $r, ReportsService $rep)
    {
        $start = $r->input('start', now()->startOfMonth()->toDateString());
        $end = $r->input('end', now()->toDateString());
        $filters = $r->except(['start', 'end']);

        return response()->json($rep->datasetAppointments($start, $end, $filters));
    }

    public function datasetPayments(Request $r, ReportsService $rep)
    {
        $start = $r->input('start', now()->startOfMonth()->toDateString());
        $end = $r->input('end', now()->toDateString());
        $filters = $r->except(['start', 'end']);

        return response()->json($rep->datasetPayments($start, $end, $filters));
    }
}
