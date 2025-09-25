<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payout;
use App\Models\PayoutAdjustment;
use App\Models\PayoutItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayoutService
{
    /**
     * Lista atendimentos elegíveis para repasse.
     * Por padrão: status 'attended', sem payout e dentro do período.
     * Se $requirePaid=true, exige appointments financeiramente 'paid'.
     */
    public function eligibleAppointments(
        int $professionalId,
        string $periodStart,
        string $periodEnd,
        bool $requirePaid = false
    ): Collection {
        $start = Carbon::parse($periodStart)->startOfDay();
        $end = Carbon::parse($periodEnd)->endOfDay();

        $q = Appointment::query()
            ->where('professional_id', $professionalId)
            ->whereBetween('start_at', [$start, $end])
            ->where('status', 'attended')
            ->whereNull('payout_id')
            ->orderBy('start_at');

        if ($requirePaid) {
            $q->where('financial_status', 'paid');
        }

        return $q->get();
    }

    /**
     * Gera (ou retorna existente) um payout "open" para o período.
     * Cria items com base em appointments elegíveis e marca appointments.payout_id.
     */
    public function generate(
        int $professionalId,
        string $periodStart,
        string $periodEnd,
        bool $requirePaid = false,
        ?int $limit = null
    ): Payout {
        $start = Carbon::parse($periodStart)->toDateString();
        $end = Carbon::parse($periodEnd)->toDateString();

        return DB::transaction(function () use ($professionalId, $start, $end, $requirePaid, $limit) {

            // reutiliza se já houver "open" do mesmo período
            $payout = Payout::where('professional_id', $professionalId)
                ->where('period_start', $start)
                ->where('period_end', $end)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if (! $payout) {
                $payout = Payout::create([
                    'professional_id' => $professionalId,
                    'period_start' => $start,
                    'period_end' => $end,
                    'status' => 'open',
                    'gross_total' => 0,
                    'adjustments_total' => 0,
                    'net_total' => 0,
                    'metadata' => ['generated_at' => now()->toDateTimeString()],
                ]);
            }

            // pega appointments elegíveis com lock (evita corrida)
            $apptsQ = Appointment::where('professional_id', $professionalId)
                ->whereBetween('start_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()])
                ->where('status', 'attended')
                ->whereNull('payout_id')
                ->orderBy('start_at')
                ->lockForUpdate();

            if ($requirePaid) {
                $apptsQ->where('financial_status', 'paid');
            }
            if ($limit) {
                $apptsQ->limit($limit);
            }

            $appts = $apptsQ->get();
            if ($appts->isEmpty()) {
                // ainda assim retornamos o payout (pode estar vazio)
                $this->recomputeTotals($payout);

                return $payout->refresh();
            }

            foreach ($appts as $a) {
                // snapshot de repasse
                $value = round((float) $a->payout_value_snapshot, 2);

                PayoutItem::create([
                    'payout_id' => $payout->id,
                    'appointment_id' => $a->id,
                    'professional_id' => $a->professional_id,
                    'treatment_id' => $a->treatment_id,
                    'service_date' => $a->start_at->toDateString(),
                    'payout_value' => $value,
                    'metadata' => [
                        'price_final' => $a->price_final,
                        'insurance_id' => $a->insurance_id,
                    ],
                ]);

                // marca o appointment como incluído neste payout
                $a->payout_id = $payout->id;
                $a->save();
            }

            $this->recomputeTotals($payout);

            return $payout->refresh();
        });
    }

    /**
     * Recalcula gross/adjustments/net de um payout.
     */
    public function recomputeTotals(Payout $payout): void
    {
        $gross = (float) $payout->items()->sum('payout_value');
        $adj = (float) $payout->adjustments()->sum('amount');
        $net = round($gross + $adj, 2);

        $payout->gross_total = round($gross, 2);
        $payout->adjustments_total = round($adj, 2);
        $payout->net_total = $net;
        $payout->save();
    }

    /**
     * Adiciona ajuste (positivo ou negativo) e recalcula.
     */
    public function addAdjustment(int $payoutId, float $amount, string $type = 'correction', ?string $reason = null, ?int $userId = null): PayoutAdjustment
    {
        $payout = Payout::findOrFail($payoutId);
        if (! in_array($payout->status, ['open', 'approved'])) {
            throw new RuntimeException('Só é possível ajustar repasses em status open/approved.');
        }

        $adj = PayoutAdjustment::create([
            'payout_id' => $payout->id,
            'amount' => round($amount, 2),
            'type' => $type,
            'reason' => $reason,
            'user_id' => $userId,
        ]);

        $this->recomputeTotals($payout);

        return $adj;
    }

    /**
     * Aprova um repasse (trava a lista de itens, mas ainda permite ajustes).
     */
    public function approve(int $payoutId): Payout
    {
        $payout = Payout::findOrFail($payoutId);
        if ($payout->status !== 'open') {
            throw new RuntimeException('Só é possível aprovar repasses em status open.');
        }
        $payout->status = 'approved';
        $payout->save();

        return $payout;
    }

    /**
     * Marca como pago. (Recomendado aprovar antes.)
     */
    public function markPaid(int $payoutId): Payout
    {
        return DB::transaction(function () use ($payoutId) {
            $payout = Payout::lockForUpdate()->findOrFail($payoutId);
            if (! in_array($payout->status, ['approved', 'open'])) {
                throw new RuntimeException('Somente repasses open/approved podem ser pagos.');
            }
            $this->recomputeTotals($payout); // confere
            $payout->status = 'paid';
            $payout->paid_at = now();
            $payout->save();

            return $payout;
        });
    }

    /**
     * Cancela um repasse ABERTO: desfaz vinculações e apaga itens.
     */
    public function cancelOpen(int $payoutId): Payout
    {
        return DB::transaction(function () use ($payoutId) {
            $payout = Payout::lockForUpdate()->findOrFail($payoutId);
            if ($payout->status !== 'open') {
                throw new RuntimeException('Apenas repasses em status open podem ser cancelados.');
            }

            // desfaz marcação nos appointments
            $apptIds = $payout->items()->pluck('appointment_id')->all();
            if (! empty($apptIds)) {
                Appointment::whereIn('id', $apptIds)->update(['payout_id' => null]);
            }

            // apaga itens e ajustes
            $payout->items()->delete();
            $payout->adjustments()->delete();

            // marca como cancelado
            $payout->status = 'canceled';
            $payout->gross_total = 0;
            $payout->adjustments_total = 0;
            $payout->net_total = 0;
            $payout->save();

            return $payout;
        });
    }
}
