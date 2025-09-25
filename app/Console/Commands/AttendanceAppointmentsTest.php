<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AttendanceAppointmentsTest extends Command
{
    protected $signature = 'appointments:attendance-test
        {--date= : Data inicial Y-m-d (padrão: hoje)}
        {--days=1 : Janela em dias}
        {--limit=20 : Quantidade máxima a processar}
        {--noshow=20 : Percentual (0-100) marcado como no_show}
        {--checkin-only : Apenas registrar check-in (não muda status)}
        {--user-id= : Usuário para confirmar}';

    protected $description = 'Simula presença: check-in (manual) e marca attended/no_show em lote.';

    public function handle(AttendanceService $svc): int
    {
        $start = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : now()->startOfDay();
        $days = (int) ($this->option('days') ?? 1);
        $end = $start->copy()->addDays($days)->endOfDay();

        $limit = (int) ($this->option('limit') ?? 20);
        $pctNoShow = max(0, min(100, (int) $this->option('noshow')));
        $checkinOnly = (bool) $this->option('checkin-only');
        $userId = $this->option('user-id') ? (int) $this->option('user-id') : null;

        $appts = Appointment::whereBetween('start_at', [$start, $end])
            ->whereIn('status', ['scheduled', 'rescheduled'])
            ->orderBy('start_at')
            ->limit($limit)
            ->get();

        if ($appts->isEmpty()) {
            $this->warn('Nenhum appointment elegível no período.');

            return self::SUCCESS;
        }

        $ok = 0;
        $nos = 0;
        $att = 0;
        foreach ($appts as $a) {
            try {
                // check-in (manual, confirmado)
                $svc->checkIn($a->id, 'manual', true, $userId, 'Teste presença');

                if (! $checkinOnly) {
                    if (mt_rand(1, 100) <= $pctNoShow) {
                        $svc->markNoShow($a->id, 'Ausência simulada', $userId);
                        $nos++;
                    } else {
                        $svc->markAttended($a->id, $userId);
                        $att++;
                    }
                }

                $ok++;
                $this->line("OK #{$a->id}: ".($checkinOnly ? 'check-in' : ($a->status === 'no_show' ? 'no_show' : 'attended')));
            } catch (\Throwable $e) {
                $this->warn("FAIL #{$a->id}: ".$e->getMessage());
            }
        }

        $this->info("Resumo: processados={$ok} | attended={$att} | no_show={$nos} | janela {$start->toDateString()} → {$end->toDateString()}");

        return self::SUCCESS;
    }
}
