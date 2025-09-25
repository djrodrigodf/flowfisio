<?php

namespace App\Domain\Payout\Services;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\PayoutItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SyncPayout
{
    /**
     * Gera/atualiza o item de repasse ao marcar atendimento como 'attended'.
     * - Cria (ou reaproveita) o Payout do mês (status 'open') do parceiro
     * - Cria/atualiza o PayoutItem daquele appointment
     * - Recalcula totais (gross_total, net_total)
     *
     * Retorna o PayoutItem criado/atualizado ou null (sem repasse).
     */
    public function handle(Appointment $appt): ?PayoutItem
    {

        if ($appt->status !== 'attended') {
            return null;
        }

        // calcula repasse com base no snapshot do agendamento
        $amount = $this->calcRepasse($appt);

        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($appt, $amount) {

            // período mensal baseado na data do atendimento
            $periodStart = $appt->start_at->copy()->startOfMonth();
            $periodEnd = $appt->start_at->copy()->endOfMonth();

            // garante um payout 'open' do parceiro naquele mês
            $payout = Payout::updateOrCreate(
                [
                    'partner_id' => $appt->partner_id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ],
                [
                    'status' => 'open',
                    'gross_total' => 0,
                    'adjustments_total' => 0,
                    'net_total' => 0,
                ]
            );

            // se o payout estiver fechado/aprovado/pago/cancelado, não altera
            if ($payout->status !== 'open') {
                return;
            }

            $item = \App\Models\PayoutItem::updateOrCreate(
                ['appointment_id' => $appt->id], // <- casa com o índice único
                [
                    'payout_id' => $payout->id,
                    'partner_id' => $appt->partner_id,
                    'treatment_id' => $appt->treatment_id,
                    'service_date' => Carbon::now(),
                    'payout_value' => $amount,
                    'notes' => $appt->treatment?->name,
                ]
            );

            // linka appointment ao payout (se ainda não tiver)
            if (! $appt->payout_id) {
                $appt->payout_id = $payout->id;
                $appt->save();
            }

            // recalcula totais
            $this->recalc($payout);

            return $item;
        });
    }

    /**
     * Remove o item de repasse quando o atendimento deixa de valer (ex.: cancelado/no_show).
     * Recalcula totais e desvincula o appointment do payout.
     */
    public function removeForAppointment(Appointment $appt): void
    {
        if (! $appt->payout_id) {
            return;
        }

        DB::transaction(function () use ($appt) {
            $payout = Payout::find($appt->payout_id);
            if (! $payout) {
                $appt->update(['payout_id' => null]);

                return;
            }

            PayoutItem::where('payout_id', $payout->id)
                ->where('appointment_id', $appt->id)
                ->delete();

            $appt->update(['payout_id' => null]);

            $this->recalc($payout);
        });
    }

    private function calcRepasse(Appointment $a): float
    {

        $payment = Payment::where('appointment_id', $a->id)
            ->first();
        $treatmentTable = $a->table->items()->orderBy('id', 'desc')->first();

        $price = (float) ($treatmentTable->price ?? 0);
        $type = $treatmentTable->repasse_type;
        $val = (float) ($treatmentTable->repasse_value ?? 0);

        if ($price <= 0 || ! $type) {
            return 0.0;
        }

        if ($type === 'fixed') {
            return round($val, 2);
        }
        if ($type === 'percent') {
            return round($price * ($val / 100), 2);
        }

        return 0.0;
    }

    private function recalc(Payout $payout): void
    {
        $gross = (float) $payout->items()->sum('payout_value');
        $adj = (float) ($payout->adjustments_total ?? 0);

        $payout->gross_total = $gross;
        $payout->net_total = $gross + $adj;
        $payout->save();
    }
}
