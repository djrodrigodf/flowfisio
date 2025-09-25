<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;

class PaymentsTest extends Command
{
    protected $signature = 'payments:test
        {--aid= : ID do appointment (se não passar, pega o último com valor > 0)}
        {--pay= : Valor a cobrar (se não passar, usa o outstanding atual)}
        {--method=pix : Método (cash|pix|card|boleto|insurance)}
        {--discount-type= : percent|fixed}
        {--discount-value= : Valor do desconto}
        {--pending : Lançar como pendente (ex.: boleto/insurance)}
        {--confirm-pending : Confirmar o último pendente do appointment}
        {--cancel-pending : Cancelar o último pendente do appointment}
        {--fail-pending : Falhar o último pendente do appointment}
        {--surcharge-reason= : Motivo do excedente (juros/multa/outros)}
        {--exempt : Marcar appointment como isento}
        {--show : Apenas mostrar situação atual}';

    protected $description = 'Testes de pagamento: pagamento (com possível excedente), pendente→pago, cancelar/falhar, isenção e resumo.';

    public function handle(PaymentService $svc): int
    {
        $appt = $this->resolveAppointment();
        if (! $appt) {
            $this->error('Nenhum appointment elegível encontrado.');

            return self::FAILURE;
        }

        $this->line('');
        $this->info("Appointment #{$appt->id}  DUE={$appt->price_final}  PAID={$appt->paid_total}  OUTSTANDING=".max(0, $appt->price_final - $appt->paid_total)."  FIN={$appt->financial_status}");

        if ($this->option('show')) {
            $this->printFinance($appt);

            return self::SUCCESS;
        }

        if ($this->option('exempt')) {
            try {
                $svc->setExempt($appt->id, 'Isenção via command');
                $this->info('OK: appointment isento.');
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
            }
            $appt->refresh();
            $this->printFinance($appt);

            return self::SUCCESS;
        }

        if ($this->option('cancel-pending') || $this->option('fail-pending') || $this->option('confirm-pending')) {
            $pending = $appt->payments()->where('status', 'pending')->latest()->first();
            if (! $pending) {
                $this->warn('Não há pagamentos pendentes para operar.');

                return self::SUCCESS;
            }

            try {
                if ($this->option('cancel-pending')) {
                    app(PaymentService::class)->cancelPending($pending->id, 'Cancelado via command');
                    $this->info("OK: pending #{$pending->id} CANCELADO.");
                } elseif ($this->option('fail-pending')) {
                    app(PaymentService::class)->failPending($pending->id, 'Falha via command');
                    $this->info("OK: pending #{$pending->id} FALHOU.");
                } else { // confirmar
                    app(PaymentService::class)->markPaid($pending->id);
                    $this->info("OK: pending #{$pending->id} CONFIRMADO.");
                }
            } catch (\Throwable $e) {
                $this->error('Operação pendente falhou: '.$e->getMessage());
            }

            $appt->refresh();
            $this->printFinance($appt);

            return self::SUCCESS;
        }

        // Lançar pagamento (paid ou pending)
        $method = $this->option('method') ?? 'pix';
        $out = $svc->outstanding($appt);
        $amount = $this->option('pay') ? (float) $this->option('pay') : ($out > 0 ? $out : 0.01); // se due 0 e você insistir, cria pagamento com over = tudo (virará surcharge)
        $discountType = $this->option('discount-type') ?: null;
        $discountVal = $this->option('discount-value') ? (float) $this->option('discount-value') : null;
        $status = $this->option('pending') ? 'pending' : null;
        $surchargeReason = $this->option('surcharge-reason') ?: null;

        try {
            $payment = $svc->charge([
                'appointment_id' => $appt->id,
                'method' => $method,
                'amount' => $amount,
                'status' => $status,              // null => default pela regra do service
                'discount_type' => $discountType,
                'discount_value' => $discountVal,
                'discount_reason' => $discountType ? 'Desconto no ato' : null,
                'reference' => strtoupper(substr(md5(uniqid()), 0, 10)),
                'receipt_url' => null,
                'surcharge_reason' => $surchargeReason,
            ]);

            $this->info("OK: payment #{$payment->id} {$payment->method} status={$payment->status} amount_paid={$payment->amount_paid} applied={$payment->applied_to_due} surcharge={$payment->surcharge_amount}");
        } catch (\Throwable $e) {
            $this->error('Falha ao lançar pagamento: '.$e->getMessage());

            return self::FAILURE;
        }

        $appt->refresh();
        $this->printFinance($appt);

        return self::SUCCESS;
    }

    // ---------------- helpers ----------------

    private function resolveAppointment(): ?Appointment
    {
        if ($this->option('aid')) {
            return Appointment::find((int) $this->option('aid'));
        }

        return Appointment::where('price_final', '>', 0)
            ->where('financial_status', '!=', 'exempt')
            ->latest('start_at')
            ->first();
    }

    private function printFinance(Appointment $a): void
    {
        $rows = [];
        foreach ($a->payments()->latest()->get() as $p) {
            $rows[] = [
                '#'.$p->id,
                $p->method,
                $p->status,
                number_format($p->amount, 2, ',', '.'),
                ($p->discount_type ? "{$p->discount_type}:{$p->discount_value}" : '—'),
                number_format($p->amount_paid, 2, ',', '.'),
                number_format(($p->applied_to_due ?? 0), 2, ',', '.'),
                number_format(($p->surcharge_amount ?? 0), 2, ',', '.'),
                $p->received_at ? $p->received_at->format('Y-m-d H:i') : '—',
            ];
        }

        (new Table($this->output))
            ->setHeaders(['ID', 'Método', 'Status', 'Lançado', 'Desc.', 'Pago', 'Aplicado', 'Juros/Extra', 'Recebido em'])
            ->setRows($rows)
            ->render();

        $this->line("DUE={$a->price_final} | APPLIED_TOTAL={$a->paid_total} | OUTSTANDING=".max(0, $a->price_final - $a->paid_total).' | FIN_STATUS='.$a->financial_status);
    }
}
