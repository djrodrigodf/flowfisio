<?php

// app/Services/ReportsService.php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportsService
{
    /* ========================= SCHEDULE ========================= */

    public function dailySchedule(string $date, array $filters = []): array
    {
        $start = Carbon::parse($date)->startOfDay();
        $end   = Carbon::parse($date)->endOfDay();

        $q = $this->baseAppointmentQuery($filters)
            ->whereBetween('appointments.start_at', [$start, $end])
            ->orderBy('appointments.start_at');

        return $this->mapAppointmentsRows($q->get());
    }

    public function weeklySchedule(string $weekStart, array $filters = []): array
    {
        $start = Carbon::parse($weekStart)->startOfDay();
        $end   = Carbon::parse($weekStart)->copy()->endOfWeek();

        $q = $this->baseAppointmentQuery($filters)
            ->whereBetween('appointments.start_at', [$start, $end])
            ->orderBy('appointments.start_at');

        return $this->mapAppointmentsRows($q->get());
    }

    /* ========================= KPIs OPERACIONAIS ========================= */

    // (renomear se quiser) produção por parceiro
    public function productionByPartner(string $start, string $end): array
    {
        $startAt = Carbon::parse($start)->startOfDay();
        $endAt   = Carbon::parse($end)->endOfDay();

        // Volume por status e valor de produção (appointments.price) por partner no período do ATENDIMENTO
        $vol = Appointment::whereBetween('start_at', [$startAt, $endAt])
            ->leftJoin('partners as p', 'p.id', '=', 'appointments.partner_id')
            ->groupBy('appointments.partner_id', 'p.name')
            ->selectRaw("
                appointments.partner_id as partner_id,
                COALESCE(p.name, CONCAT('partner#', appointments.partner_id)) as partner_name,
                SUM(CASE WHEN status='attended' THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN status='no_show' THEN 1 ELSE 0 END) as no_show,
                SUM(CASE WHEN status='canceled' THEN 1 ELSE 0 END) as canceled,
                COUNT(*) as total,
                COALESCE(SUM(CASE WHEN status='attended' THEN price ELSE 0 END),0) as production_value
            ")
            ->get();

        // Receita recebida por partner (somando applied_to_due por RECEBIMENTO)
        $rev = Payment::where('status', 'paid')
            ->whereBetween('received_at', [$startAt, $endAt])
            ->join('appointments as a', 'a.id', '=', 'payments.appointment_id')
            ->groupBy('a.partner_id')
            ->selectRaw('a.partner_id, COALESCE(SUM(payments.applied_to_due),0) as revenue_applied')
            ->pluck('revenue_applied', 'a.partner_id')
            ->map(fn ($v) => (float) $v);

        return $vol->map(function ($r) use ($rev) {
            $revenueApplied = (float) ($rev[$r->partner_id] ?? 0);
            $rate = $r->total ? round($r->attended / $r->total * 100, 2) : 0.0;

            return [
                'partner_id'       => (int) $r->partner_id,
                'partner_name'     => $r->partner_name,
                'attended'         => (int) $r->attended,
                'no_show'          => (int) $r->no_show,
                'canceled'         => (int) $r->canceled,
                'total'            => (int) $r->total,
                'attendance_rate'  => $rate,
                'production_value' => round((float) $r->production_value, 2),
                'revenue_applied'  => round($revenueApplied, 2),
            ];
        })->sortByDesc('production_value')->values()->all();
    }

    public function monthlyComparison(int $months = 6, ?string $untilMonth = null): array
    {
        $end = $untilMonth
            ? Carbon::parse($untilMonth.'-01')->endOfMonth()
            : now()->endOfMonth();

        $series = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = $end->copy()->subMonths($i)->startOfMonth();
            $monthEnd   = $end->copy()->subMonths($i)->endOfMonth();

            $att = Appointment::whereBetween('start_at', [$monthStart, $monthEnd])
                ->selectRaw("
                    SUM(CASE WHEN status='attended' THEN 1 ELSE 0 END) as attended,
                    SUM(CASE WHEN status='no_show' THEN 1 ELSE 0 END) as no_show,
                    COUNT(*) as total,
                    COALESCE(SUM(CASE WHEN status='attended' THEN price ELSE 0 END),0) as production_value
                ")->first();

            $pay = Payment::where('status', 'paid')
                ->whereBetween('received_at', [$monthStart, $monthEnd])
                ->selectRaw('COALESCE(SUM(applied_to_due),0) as applied, COALESCE(SUM(surcharge_amount),0) as surcharge')
                ->first();

            $series[] = [
                'month'             => $monthStart->format('Y-m'),
                'attended'          => (int) $att->attended,
                'no_show'           => (int) $att->no_show,
                'total'             => (int) $att->total,
                'attendance_rate'   => $att->total ? round($att->attended / $att->total * 100, 2) : 0,
                'production_value'  => round((float) $att->production_value, 2),
                'revenue_applied'   => round((float) $pay->applied, 2),
                'revenue_surcharge' => round((float) $pay->surcharge, 2),
            ];
        }

        return $series;
    }

    /* ========================= DATASETS DETALHADOS ========================= */

    public function datasetAppointments(string $start, string $end, array $filters = []): array
    {
        $q = $this->baseAppointmentQuery($filters)
            ->whereBetween('appointments.start_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()])
            ->orderBy('appointments.start_at');

        return $this->mapAppointmentsRows($q->get());
    }

    public function datasetPayments(string $startReceived, string $endReceived, array $filters = []): array
    {
        $start = Carbon::parse($startReceived)->startOfDay();
        $end   = Carbon::parse($endReceived)->endOfDay();

        $q = Payment::query()
            ->join('appointments as a', 'a.id', '=', 'payments.appointment_id')
            ->leftJoin('patients as pa', 'pa.id', '=', 'a.patient_id')
            ->leftJoin('partners as p', 'p.id', '=', 'a.partner_id')
            ->leftJoin('treatments as t', 't.id', '=', 'a.treatment_id')
            ->leftJoin('insurances as i', 'i.id', '=', 'a.insurance_id')
            ->leftJoin('locations as l', 'l.id', '=', 'a.location_id')
            ->leftJoin('rooms as r', 'r.id', '=', 'a.room_id')
            ->whereBetween('payments.received_at', [$start, $end]);

        // filtros
        if (!empty($filters['status']))       $q->where('payments.status', $filters['status']);
        if (!empty($filters['method']))       $q->where('payments.method', $filters['method']);
        if (!empty($filters['partner_id']))   $q->where('a.partner_id', $filters['partner_id']);
        if (!empty($filters['insurance_id'])) $q->where('a.insurance_id', $filters['insurance_id']);
        if (!empty($filters['location_id']))  $q->where('a.location_id', $filters['location_id']);
        if (!empty($filters['room_id']))      $q->where('a.room_id', $filters['room_id']);
        if (!empty($filters['min_amount']))   $q->where('payments.amount_paid', '>=', (float) $filters['min_amount']);

        $rows = $q->get([
            'payments.id as payment_id',
            'payments.status as payment_status',
            'payments.method',
            'payments.amount',
            'payments.amount_paid',
            'payments.applied_to_due',
            'payments.surcharge_amount',
            'payments.received_at',

            'a.id as appointment_id',
            'a.start_at','a.end_at','a.status as appointment_status',
            'a.price', // snapshot do preço

            'pa.name as patient_name',
            'p.name as partner_name',
            't.name as treatment_name',
            DB::raw('COALESCE(i.name,"PARTICULAR") as insurance_name'),
            DB::raw('COALESCE(i.code,"PART") as insurance_code'),
            'l.name as location_name',
            'r.name as room_name',
        ]);




        return $rows->map(function ($r) {
            return [
                'payment_id'         => (int) $r->payment_id,
                'payment_status'     => $r->payment_status,
                'method'             => $r->method,
                'amount'             => (float) $r->amount,
                'amount_paid'        => (float) $r->amount_paid,
                'applied_to_due'     => (float) $r->applied_to_due,
                'surcharge_amount'   => (float) $r->surcharge_amount,
                'received_at'        => optional($r->received_at)->format('Y-m-d H:i:s'),

                'appointment_id'     => (int) $r->appointment_id,
                'appointment_date'   => optional($r->start_at)->format('Y-m-d'),
                'start_time'         => optional($r->start_at)->format('H:i'),
                'end_time'           => optional($r->end_at)->format('H:i'),
                'appointment_status' => $r->appointment_status,
                'price'              => (float) $r->price,

                'patient'            => $r->patient_name,
                'partner'            => $r->partner_name,
                'treatment'          => $r->treatment_name,
                'insurance'          => $r->insurance_name,
                'insurance_code'     => $r->insurance_code,
                'location'           => $r->location_name,
                'room'               => $r->room_name,
            ];
        })->all();
    }

    /* ========================= HELPERS ========================= */

    private function baseAppointmentQuery(array $filters): Builder
    {
        $q = Appointment::query()
            ->leftJoin('patients as pa', 'pa.id', '=', 'appointments.patient_id')
            ->leftJoin('partners as p', 'p.id', '=', 'appointments.partner_id')
            ->leftJoin('treatments as t', 't.id', '=', 'appointments.treatment_id')
            ->leftJoin('insurances as i', 'i.id', '=', 'appointments.insurance_id')
            ->leftJoin('locations as l', 'l.id', '=', 'appointments.location_id')
            ->leftJoin('rooms as r', 'r.id', '=', 'appointments.room_id');

        if (!empty($filters['status']))        $q->where('appointments.status', $filters['status']);
        if (!empty($filters['partner_id']))    $q->where('appointments.partner_id', $filters['partner_id']);
        if (!empty($filters['patient_id']))    $q->where('appointments.patient_id', $filters['patient_id']);
        if (!empty($filters['treatment_id']))  $q->where('appointments.treatment_id', $filters['treatment_id']);
        if (!empty($filters['insurance_id']))  $q->where('appointments.insurance_id', $filters['insurance_id']);
        if (!empty($filters['location_id']))   $q->where('appointments.location_id', $filters['location_id']);
        if (!empty($filters['room_id']))       $q->where('appointments.room_id', $filters['room_id']);

        return $q->select([
            'appointments.*',
            'pa.name as patient_name',
            'p.name as partner_name',
            't.name as treatment_name',
            DB::raw('COALESCE(i.name,"PARTICULAR") as insurance_name'),
            DB::raw('COALESCE(i.code,"PART") as insurance_code'),
            'l.name as location_name',
            'r.name as room_name',
        ]);
    }

    private function mapAppointmentsRows($rows): array
    {

        return collect($rows)->map(function ($r) {
            return [
                'appointment_id' => (int) $r->id,
                'date'           => optional($r->start_at)->format('Y-m-d'),
                'start_time'     => optional($r->start_at)->format('H:i'),
                'end_time'       => optional($r->end_at)->format('H:i'),
                'status'         => $r->status,
                'patient'        => $r->patient_name,
                'partner'        => $r->partner_name,
                'treatment'      => $r->treatment_name,
                'insurance'      => $r->insurance_name,
                'insurance_code' => $r->insurance_code,
                'location'       => $r->location_name,
                'room'           => $r->room_name,
                // snapshot atual
                'price'          => (float) ($r->price ?? 0),
                'paid_total' =>  (float) ($r->paid_total ?? 0)
            ];
        })->all();
    }
}
