<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BiService
{
    /**
     * Resumo "cards" do período (datas inclusivas, Y-m-d).
     */
    public function summary(string $start, string $end): array
    {
        $startAt = Carbon::parse($start)->startOfDay();
        $endAt   = Carbon::parse($end)->endOfDay();

        // Contagens por status no período (pela data do atendimento)
        $statusCounts = Appointment::whereBetween('start_at', [$startAt, $endAt])
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');




        $attended   = (int) ($statusCounts['attended'] ?? 0);
        $scheduled  = (int) ($statusCounts['scheduled'] ?? 0);
        $rescheduled= (int) ($statusCounts['rescheduled'] ?? 0);
        $canceled   = (int) ($statusCounts['canceled'] ?? 0);
        $noShow     = (int) ($statusCounts['no_show'] ?? 0);
        $totalAppts = array_sum($statusCounts->toArray());

        // Receita (pagamentos confirmados) no período pela data de RECEBIMENTO
        $pay = Payment::where('status', 'paid')
            ->whereBetween('received_at', [$startAt, $endAt])
            ->selectRaw('
                COALESCE(SUM(amount_paid),0) as revenue,
                COALESCE(SUM(surcharge_amount),0) as surcharge
            ')
            ->first();


        $revenue   = (float) $pay->revenue;
        $surcharge = (float) $pay->surcharge;

        // Soma do valor de atendimentos concluídos no período (produção) => usa appointments.price
        $sumAttendedPrice = (float) Appointment::where('status', 'attended')
            ->whereBetween('start_at', [$startAt, $endAt])
            ->sum('price');



        // Pacientes únicos atendidos no período
        $uniquePatients = (int) Appointment::where('status', 'attended')
            ->whereBetween('start_at', [$startAt, $endAt])
            ->distinct('patient_id')->count('patient_id');

        // Em aberto (saldo): para os atendimentos do período, considera
        // outstanding = GREATEST(appointment.price - pagos_ate_endAt, 0)
        $paymentsUntilEnd = DB::table('payments')
            ->where('status', 'paid')
            ->where('received_at', '<=', $endAt)
            ->groupBy('appointment_id')
            ->selectRaw('appointment_id, COALESCE(SUM(applied_to_due),0) as paid_until_end');

        $outstanding = (float) Appointment::whereBetween('start_at', [$startAt, $endAt])
            ->leftJoinSub($paymentsUntilEnd, 'pp', 'pp.appointment_id', '=', 'appointments.id')
            ->selectRaw('COALESCE(SUM(GREATEST(appointments.price - COALESCE(pp.paid_until_end, 0), 0)), 0) as due_sum')
            ->value('due_sum');

        // Conversões de pré-cadastro -> paciente criados no período
        $converted = (int) Patient::whereNotNull('pre_registration_id')
            ->whereBetween('created_at', [$startAt, $endAt])
            ->count();

        return [
            'counts' => [
                'attended'              => $attended,
                'scheduled'             => $scheduled,
                'rescheduled'           => $rescheduled,
                'canceled'              => $canceled,
                'no_show'               => $noShow,
                'total'                 => $totalAppts,
                'unique_patients'       => $uniquePatients,
                'converted_from_prereg' => $converted,
            ],
            'money' => [
                'revenue_paid'     => round($revenue, 2),
                'surcharge_extra'  => round($surcharge, 2),
                'attended_value'   => round($sumAttendedPrice, 2),
                'outstanding'      => round($outstanding, 2),
            ],
        ];
    }

    /**
     * Série diária de receita (aplicado e juros) pela data de recebimento.
     */
    public function seriesRevenue(string $start, string $end): array
    {
        $rows = Payment::where('status', 'paid')
            ->whereBetween('received_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()])
            ->selectRaw('DATE(received_at) as d,
                         COALESCE(SUM(amount_paid),0) as amount_paid,
                         COALESCE(SUM(surcharge_amount),0) as surcharge')
            ->groupBy('d')
            ->orderBy('d')
            ->get();


        return $rows->map(fn ($r) => [
            'date'      => $r->d,
            'applied'   => (float) $r->amount_paid,
            'surcharge' => (float) $r->surcharge,
            'total'     => (float) $r->applied + (float) $r->surcharge,
        ])->all();
    }

    /**
     * Série diária de presença pela data do atendimento.
     */
    public function seriesAttendance(string $start, string $end): array
    {
        $rows = Appointment::whereBetween('start_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()])
            ->selectRaw("DATE(start_at) as d,
                         SUM(CASE WHEN status='attended' THEN 1 ELSE 0 END) as attended,
                         SUM(CASE WHEN status='no_show' THEN 1 ELSE 0 END) as no_show,
                         COUNT(*) as total")
            ->groupBy('d')->orderBy('d')->get();

        return $rows->map(function ($r) {
            $att  = (int) $r->attended;
            $tot  = (int) $r->total;
            $rate = $tot ? round($att / $tot * 100, 2) : 0.0;

            return [
                'date'             => $r->d,
                'attended'         => (int) $r->attended,
                'no_show'          => (int) $r->no_show,
                'total'            => (int) $r->total,
                'attendance_rate'  => $rate,
            ];
        })->all();
    }

    /**
     * Top N tratamentos por receita aplicada e por volume de atendidos.
     */
    public function topTreatments(string $start, string $end, int $limit = 10): array
    {
        $startAt = Carbon::parse($start)->startOfDay();
        $endAt   = Carbon::parse($end)->endOfDay();

        // Por receita (soma de applied_to_due ligados aos appts desse tratamento)
        $qRev = DB::table('payments as p')
            ->join('appointments as a', 'a.id', '=', 'p.appointment_id')
            ->join('treatments as t', 't.id', '=', 'a.treatment_id')
            ->where('p.status', 'paid')
            ->whereBetween('p.received_at', [$startAt, $endAt])
            ->groupBy('t.id', 't.name')
            ->selectRaw('t.id, t.name, COALESCE(SUM(p.applied_to_due),0) as revenue')
            ->orderByDesc('revenue')->limit($limit)->get();

        // Por volume atendido
        $qVol = Appointment::where('status', 'attended')
            ->whereBetween('start_at', [$startAt, $endAt])
            ->join('treatments as t', 't.id', '=', 'appointments.treatment_id')
            ->groupBy('t.id', 't.name')
            ->selectRaw('t.id, t.name, COUNT(*) as qty')
            ->orderByDesc('qty')->limit($limit)->get();

        return [
            'by_revenue' => $qRev->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'revenue' => (float) $r->revenue])->all(),
            'by_volume'  => $qVol->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'qty' => (int) $r->qty])->all(),
        ];
    }

    /**
     * Top N parceiros (ex-profissionais) por receita aplicada e atendimentos concluídos.
     */
    public function topPartners(string $start, string $end, int $limit = 10): array
    {
        $startAt = Carbon::parse($start)->startOfDay();
        $endAt   = Carbon::parse($end)->endOfDay();

        $rev = DB::table('payments as p')
            ->join('appointments as a', 'a.id', '=', 'p.appointment_id')
            ->join('partners as pr', 'pr.id', '=', 'a.partner_id')
            ->where('p.status', 'paid')
            ->whereBetween('p.received_at', [$startAt, $endAt])
            ->groupBy('pr.id', 'pr.name')
            ->selectRaw('pr.id, pr.name, COALESCE(SUM(p.applied_to_due),0) as revenue')
            ->orderByDesc('revenue')->limit($limit)->get();

        $prod = Appointment::where('status', 'attended')
            ->whereBetween('start_at', [$startAt, $endAt])
            ->join('partners as pr', 'pr.id', '=', 'appointments.partner_id')
            ->groupBy('pr.id', 'pr.name')
            ->selectRaw('pr.id, pr.name, COUNT(*) as qty')
            ->orderByDesc('qty')->limit($limit)->get();

        return [
            'by_revenue' => $rev->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'revenue' => (float) $r->revenue])->all(),
            'by_volume'  => $prod->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'qty' => (int) $r->qty])->all(),
        ];
    }

    /**
     * Top N convênios por receita aplicada e volume atendido.
     */
    public function topInsurances(string $start, string $end, int $limit = 10): array
    {
        $startAt = Carbon::parse($start)->startOfDay();
        $endAt   = Carbon::parse($end)->endOfDay();

        $rev = DB::table('payments as p')
            ->join('appointments as a', 'a.id', '=', 'p.appointment_id')
            ->leftJoin('insurances as i', 'i.id', '=', 'a.insurance_id')
            ->where('p.status', 'paid')
            ->whereBetween('p.received_at', [$startAt, $endAt])
            ->groupBy('i.id', 'i.name', 'i.code')
            ->selectRaw('i.id, COALESCE(i.name, "PARTICULAR") as name, COALESCE(i.code,"PART") as code, COALESCE(SUM(p.applied_to_due),0) as revenue')
            ->orderByDesc('revenue')->limit($limit)->get();

        $vol = Appointment::where('status', 'attended')
            ->whereBetween('start_at', [$startAt, $endAt])
            ->leftJoin('insurances as i', 'i.id', '=', 'appointments.insurance_id')
            ->groupBy('i.id', 'i.name', 'i.code')
            ->selectRaw('i.id, COALESCE(i.name, "PARTICULAR") as name, COALESCE(i.code,"PART") as code, COUNT(*) as qty')
            ->orderByDesc('qty')->limit($limit)->get();

        return [
            'by_revenue' => $rev->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'code' => $r->code, 'revenue' => (float) $r->revenue])->all(),
            'by_volume'  => $vol->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'code' => $r->code, 'qty' => (int) $r->qty])->all(),
        ];
    }

    /**
     * Ranking de devedores (saldo em aberto) até a data fim.
     * due = GREATEST( SUM(price por paciente em appts <= endAt) - SUM(applied_to_due pagos <= endAt), 0 )
     */
    public function outstandingByPatient(string $end, int $limit = 20): array
    {
        $endAt = Carbon::parse($end)->endOfDay();

        // total de price por paciente (até fim)
        $pricePerPatient = Appointment::where('start_at', '<=', $endAt)
            ->groupBy('patient_id')
            ->selectRaw('patient_id, COALESCE(SUM(price),0) as total_price');

        // total pago aplicado por paciente (até fim)
        $paidPerPatient = DB::table('payments as p')
            ->join('appointments as a', 'a.id', '=', 'p.appointment_id')
            ->where('p.status', 'paid')
            ->where('p.received_at', '<=', $endAt)
            ->groupBy('a.patient_id')
            ->selectRaw('a.patient_id, COALESCE(SUM(p.applied_to_due),0) as total_paid');

        $rows = DB::query()
            ->fromSub($pricePerPatient, 'pp')
            ->leftJoinSub($paidPerPatient, 'pd', 'pd.patient_id', '=', 'pp.patient_id')
            ->selectRaw('pp.patient_id, GREATEST(pp.total_price - COALESCE(pd.total_paid,0), 0) as due')
            ->havingRaw('due > 0')
            ->orderByDesc('due')
            ->limit($limit)
            ->get();

        $patientNames = Patient::whereIn('id', $rows->pluck('patient_id'))->pluck('name', 'id');

        return $rows->map(fn ($r) => [
            'patient_id'   => $r->patient_id,
            'patient_name' => $patientNames[$r->patient_id] ?? ('#'.$r->patient_id),
            'due'          => (float) $r->due,
        ])->all();
    }
}
