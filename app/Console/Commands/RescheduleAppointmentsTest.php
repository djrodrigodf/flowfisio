<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Professional;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RescheduleAppointmentsTest extends Command
{
    protected $signature = 'appointments:reschedule-test
        {--pro= : ID ou email do profissional (padrão: ana@flowfisio.test)}
        {--date= : Data inicial Y-m-d (padrão: hoje)}
        {--days=7 : Janela em dias}
        {--count=5 : Quantos reagendar}
        {--reason=Teste de reagendamento}
        {--recalc : Recalcular preço/repasse}
        {--same-day : Reagendar no MESMO dia (senão busca dias seguintes)}';

    protected $description = 'Reagenda alguns appointments de teste, registrando histórico.';

    public function handle(AppointmentService $svc, AvailabilityService $avail): int
    {
        $proKey = $this->option('pro') ?? 'ana@flowfisio.test';
        $pro = ctype_digit($proKey)
            ? Professional::find((int) $proKey)
            : Professional::where('email', $proKey)->first();

        if (! $pro) {
            $this->error('Profissional não encontrado.');

            return self::FAILURE;
        }

        $start = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $days = (int) ($this->option('days') ?? 7);
        $end = $start->copy()->addDays($days);

        $appts = Appointment::where('professional_id', $pro->id)
            ->whereBetween('start_at', [$start, $end])
            ->whereIn('status', ['scheduled', 'rescheduled'])
            ->orderBy('start_at')
            ->limit((int) $this->option('count'))
            ->get();

        if ($appts->isEmpty()) {
            $this->warn('Nenhum appointment elegível no período.');

            return self::SUCCESS;
        }

        $this->info("Encontrados {$appts->count()} appointments. Tentando reagendar...");

        $ok = 0;
        $fail = 0;

        foreach ($appts as $a) {
            // Busca slots: mesmo dia ou próximos 7 dias
            $from = $a->start_at->copy();
            $to = $this->option('same-day') ? $from->copy() : $from->copy()->addDays(7);

            $slots = $avail->getAvailability(
                $a->professional_id,
                $from->toDateString(),
                $to->toDateString(),
                $a->room_id,
                $a->id // ignore o próprio
            );

            // pega um slot diferente do atual (ex.: o próximo da lista)
            $target = collect($slots)->first(fn ($s) => $s['start'] !== $a->start_at->toDateTimeString() ||
                $s['end'] !== $a->end_at->toDateTimeString()
            );

            if (! $target) {
                $this->warn("Sem slot alternativo para #{$a->id} ({$a->start_at}).");
                $fail++;

                continue;
            }

            try {
                $res = $svc->reschedule([
                    'appointment_id' => $a->id,
                    'new_start_at' => $target['start'],
                    'new_end_at' => $target['end'],
                    'new_room_id' => $target['room_id'] ?? $a->room_id,
                    'reason' => $this->option('reason'),
                    'user_id' => null,
                    'recalc' => (bool) $this->option('recalc'),
                ]);
                $this->line("OK #{$res->id}: {$a->start_at} → {$res->start_at}");
                $ok++;
            } catch (\Throwable $e) {
                $this->warn("FAIL #{$a->id}: ".$e->getMessage());
                $fail++;
            }
        }

        $this->info("Resumo: OK={$ok} | FAIL={$fail}");

        return self::SUCCESS;
    }
}
