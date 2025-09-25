<?php

namespace App\Console\Commands;

use App\Models\Payout;
use App\Models\Professional;
use App\Services\PayoutService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;

class PayoutsTest extends Command
{
    protected $signature = 'payouts:test
        {--pro= : ID ou email do profissional (padrão: ana@flowfisio.test)}
        {--start= : Data inicial (Y-m-d). Opcional com --month}
        {--end= : Data final (Y-m-d). Opcional com --month}
        {--month= : Mês no formato YYYY-MM (ex.: 2025-02). Define start/end automaticamente}
        {--require-paid : Incluir somente appointments financeiramente pagos}
        {--limit= : Limitar quantidade de atendimentos na geração}
        {--generate : Gerar/atualizar payout aberto para o período}
        {--list : Listar candidatos elegíveis (não gera)}
        {--show : Mostrar resumo do payout aberto/mais recente do período}
        {--approve : Aprovar o payout aberto}
        {--pay : Marcar payout aprovado/aberto como pago}
        {--cancel : Cancelar payout ABERTO (Desfaz itens e marca appointments)}
        {--adj= : Adicionar ajuste. Formato: valor;tipo;motivo  (ex.: 100;bonus;\"bônus produção\") }';

    protected $description = 'Gera/lista/aprova/paga/cancela repasses e adiciona ajustes para testes.';

    public function handle(PayoutService $svc): int
    {
        $proKey = $this->option('pro') ?? 'ana@flowfisio.test';
        $pro = $this->resolveProfessional($proKey);
        if (! $pro) {
            $this->error('Profissional não encontrado.');

            return self::FAILURE;
        }

        [$startDate, $endDate] = $this->resolvePeriod($this->option('month'), $this->option('start'), $this->option('end'));
        if (! $startDate || ! $endDate) {
            $this->error('Informe --month=YYYY-MM ou --start/--end.');

            return self::FAILURE;
        }

        $requirePaid = (bool) $this->option('require-paid');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->line('');
        $this->info("Profissional: {$pro->name} (ID {$pro->id})");
        $this->info("Período: {$startDate} → {$endDate}");
        $this->comment($requirePaid ? 'Filtro: somente financeiramente pagos' : 'Filtro: todos atendidos');

        if ($this->option('list')) {
            $cands = $svc->eligibleAppointments($pro->id, $startDate, $endDate, $requirePaid);
            $this->printEligible($cands->take(20)->all(), $cands->count());

            return self::SUCCESS;
        }

        $payout = $this->findOpenForPeriod($pro->id, $startDate, $endDate);

        if ($this->option('generate')) {
            $payout = $svc->generate($pro->id, $startDate, $endDate, $requirePaid, $limit);
            $this->info("Payout OPEN #{$payout->id} gerado/atualizado.");
            $this->printSummary($payout);
        }

        if ($this->option('adj')) {
            if (! $payout) {
                $payout = $this->findOpenForPeriod($pro->id, $startDate, $endDate);
            }
            if (! $payout) {
                $this->error('Não há payout OPEN no período. Use --generate primeiro.');

                return self::FAILURE;
            }

            [$val, $type, $reason] = $this->parseAdj($this->option('adj'));
            $adj = $svc->addAdjustment($payout->id, $val, $type, $reason, null);
            $this->info("Ajuste adicionado: {$adj->amount} ({$adj->type}) - {$adj->reason}");
            $payout->refresh();
            $this->printSummary($payout);
        }

        if ($this->option('approve')) {
            if (! $payout) {
                $this->error('Não há payout OPEN para aprovar. Use --generate.');

                return self::FAILURE;
            }
            $payout = $svc->approve($payout->id);
            $this->info("Payout #{$payout->id} APROVADO.");
            $this->printSummary($payout);
        }

        if ($this->option('pay')) {
            // tenta primeiro open, senão approved
            if (! $payout) {
                $payout = Payout::where('professional_id', $pro->id)
                    ->where('period_start', $startDate)->where('period_end', $endDate)
                    ->whereIn('status', ['open', 'approved'])->latest()->first();
            }
            if (! $payout) {
                $this->error('Não há payout OPEN/APPROVED para pagar.');

                return self::FAILURE;
            }

            $payout = $svc->markPaid($payout->id);
            $this->info("Payout #{$payout->id} PAGO.");
            $this->printSummary($payout);
        }

        if ($this->option('cancel')) {
            if (! $payout) {
                $this->error('Não há payout OPEN para cancelar.');

                return self::FAILURE;
            }
            $payout = $svc->cancelOpen($payout->id);
            $this->info("Payout #{$payout->id} CANCELADO e desvinculado.");
            $this->printSummary($payout);
        }

        if ($this->option('show') || (! $this->option('generate') && ! $this->option('approve') && ! $this->option('pay') && ! $this->option('cancel') && ! $this->option('adj'))) {
            // mostrar o open/approved/paid mais recente do período
            $payout = Payout::where('professional_id', $pro->id)
                ->where('period_start', $startDate)->where('period_end', $endDate)
                ->orderByRaw("FIELD(status,'open','approved','paid','canceled')")->first();
            if (! $payout) {
                $this->warn('Nenhum payout encontrado para o período.');

                return self::SUCCESS;
            }
            $this->printSummary($payout);
        }

        return self::SUCCESS;
    }

    // ---------- helpers ----------

    private function resolveProfessional(string $key): ?Professional
    {
        if (ctype_digit($key)) {
            return Professional::find((int) $key);
        }

        return Professional::where('email', $key)->orWhere('id', $key)->first();
    }

    private function resolvePeriod(?string $month, ?string $start, ?string $end): array
    {
        if ($month) {
            try {
                $d = Carbon::parse($month.'-01');

                return [$d->startOfMonth()->toDateString(), $d->endOfMonth()->toDateString()];
            } catch (\Throwable $e) {
                return [null, null];
            }
        }
        if ($start && $end) {
            try {
                return [Carbon::parse($start)->toDateString(), Carbon::parse($end)->toDateString()];
            } catch (\Throwable $e) {
                return [null, null];
            }
        }

        return [null, null];
    }

    private function printEligible(array $rows, int $total): void
    {
        $out = array_map(function ($a) {
            return [
                '#'.$a->id,
                $a->start_at->format('Y-m-d H:i'),
                $a->treatment?->name ?? ('treat#'.$a->treatment_id),
                number_format($a->payout_value_snapshot, 2, ',', '.'),
                $a->financial_status,
            ];
        }, $rows);

        (new Table($this->output))
            ->setHeaders(['Appt', 'Data', 'Tratamento', 'Repasse (snap)', 'FinStatus'])
            ->setRows($out)
            ->render();

        if ($total > count($rows)) {
            $this->line('... (mostrando primeiros '.count($rows).' de '.$total.')');
        }
    }

    private function findOpenForPeriod(int $proId, string $start, string $end): ?Payout
    {
        return Payout::where('professional_id', $proId)
            ->where('period_start', $start)->where('period_end', $end)
            ->where('status', 'open')
            ->latest()->first();
    }

    private function parseAdj(string $arg): array
    {
        // formato: valor;tipo;motivo
        $parts = array_map('trim', explode(';', $arg, 3));
        $val = (float) ($parts[0] ?? 0);
        $type = $parts[1] ?? 'correction';
        $reason = $parts[2] ?? null;
        if ($val == 0) {
            throw new \InvalidArgumentException('Ajuste com valor zero não é permitido.');
        }

        return [$val, $type, $reason];
    }

    private function printSummary(Payout $p): void
    {
        $this->line('');
        $this->info("Payout #{$p->id} | {$p->period_start->toDateString()} → {$p->period_end->toDateString()} | Prof#{$p->professional_id} | Status={$p->status}");
        $this->line('GROSS='.number_format($p->gross_total, 2, ',', '.').'  ADJ='.number_format($p->adjustments_total, 2, ',', '.').'  NET='.number_format($p->net_total, 2, ',', '.').($p->paid_at ? '  PAID_AT='.$p->paid_at->format('Y-m-d H:i') : ''));

        $items = $p->items()->with('appointment')->orderBy('service_date')->limit(15)->get();
        $rows = [];
        foreach ($items as $it) {
            $rows[] = [
                '#'.$it->appointment_id,
                $it->service_date->format('Y-m-d'),
                $it->treatment?->name ?? ('treat#'.$it->treatment_id),
                number_format($it->payout_value, 2, ',', '.'),
            ];
        }

        (new Table($this->output))
            ->setHeaders(['Appt', 'Data', 'Tratamento', 'Repasse'])
            ->setRows($rows)
            ->render();

        $this->line('Itens: '.$p->items()->count().'  |  Ajustes: '.$p->adjustments()->count());
    }
}
