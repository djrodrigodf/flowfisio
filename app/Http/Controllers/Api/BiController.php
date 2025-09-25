<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BiService;
use Illuminate\Http\Request;

class BiController extends Controller
{
    public function summary(Request $r, BiService $bi)
    {
        [$start,$end] = $this->dates($r);

        return response()->json($bi->summary($start, $end));
    }

    public function seriesRevenue(Request $r, BiService $bi)
    {
        [$start,$end] = $this->dates($r);

        return response()->json($bi->seriesRevenue($start, $end));
    }

    public function seriesAttendance(Request $r, BiService $bi)
    {
        [$start,$end] = $this->dates($r);

        return response()->json($bi->seriesAttendance($start, $end));
    }

    public function topTreatments(Request $r, BiService $bi)
    {
        [$start,$end] = $this->dates($r);

        return response()->json($bi->topTreatments($start, $end, (int) ($r->input('limit', 10))));
    }

    public function topProfessionals(Request $r, BiService $bi)
    {
        [$start,$end] = $this->dates($r);

        return response()->json($bi->topProfessionals($start, $end, (int) ($r->input('limit', 10))));
    }

    public function topInsurances(Request $r, BiService $bi)
    {
        [$start,$end] = $this->dates($r);

        return response()->json($bi->topInsurances($start, $end, (int) ($r->input('limit', 10))));
    }

    public function outstanding(Request $r, BiService $bi)
    {
        $end = $r->input('end', now()->toDateString());

        return response()->json($bi->outstandingByPatient($end, (int) ($r->input('limit', 20))));
    }

    private function dates(Request $r): array
    {
        $start = $r->input('start', now()->startOfMonth()->toDateString());
        $end = $r->input('end', now()->toDateString());

        return [$start, $end];
    }
}
