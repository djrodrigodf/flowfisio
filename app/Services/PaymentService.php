<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    /**
     * Quanto falta pagar do atendimento (não considera isenção).
     */
    public function outstanding(Appointment $appt): float
    {
        $due = (float) $appt->price_final;
        $paid = (float) $appt->paid_total; // sempre soma de applied_to_due

        return max(0, round($due - $paid, 2));
    }

    /**
     * Lança um pagamento.
     * Regras:
     * - amount > 0; amount_paid (após desconto) > 0
     * - status default: paid (cash/pix/card) | pending (boleto/insurance)
     * - Se status=paid: já definimos applied_to_due e surcharge_amount
     * - Se status=pending: applied_to_due fica NULL (decidimos na confirmação, pois o outstanding pode mudar)
     * - Permite overpayment: valor acima do outstanding vai para surcharge_amount (juros/extra)
     *
     * $data obrigatórios: appointment_id, method, amount
     * $data opcionais: status, discount_type, discount_value, discount_reason, reference, receipt_url, metadata, received_at, surcharge_reason
     */
    public function charge(array $data): Payment
    {
        $appt = Appointment::findOrFail($data['appointment_id']);
        if ($appt->financial_status === 'exempt') {
            throw new RuntimeException('Agendamento isento: não é possível lançar pagamento.');
        }

        $method = $data['method'] ?? null;
        if (! in_array($method, ['cash', 'pix', 'card', 'boleto', 'insurance'], true)) {
            throw new RuntimeException('Método de pagamento inválido.');
        }

        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        if ($amount <= 0) {
            throw new RuntimeException('Valor do lançamento deve ser maior que zero.');
        }

        $discountType = $data['discount_type'] ?? null;
        $discountValue = isset($data['discount_value']) ? (float) $data['discount_value'] : null;

        $amountPaid = $this->applyDiscount($amount, $discountType, $discountValue);
        if ($amountPaid <= 0) {
            throw new RuntimeException('Pagamento com valor líquido zero não é permitido.');
        }

        $status = $data['status'] ?? (in_array($method, ['cash', 'pix', 'card']) ? 'paid' : 'pending');
        $receivedAt = isset($data['received_at']) ? Carbon::parse($data['received_at']) : now();

        return DB::transaction(function () use ($appt, $data, $status, $amount, $discountType, $discountValue, $amountPaid, $receivedAt) {

            $applied = null; // só define já se pago
            $surcharge = 0.0;

            if ($status === 'paid') {
                // Aplica agora: parte aplicada ao débito e excedente como surcharge
                $out = $this->outstanding($appt);
                $applied = round(min($amountPaid, $out), 2);
                $surcharge = round(max(0, $amountPaid - $applied), 2);
            }

            $payment = Payment::create([
                'appointment_id' => $appt->id,
                'method' => $data['method'],
                'status' => $status,
                'amount' => $amount,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_reason' => $data['discount_reason'] ?? null,
                'amount_paid' => $amountPaid,

                'applied_to_due' => $applied,                     // NULL se pending
                'surcharge_amount' => $surcharge,                   // 0 enquanto pending
                'surcharge_reason' => $data['surcharge_reason'] ?? null,

                'received_at' => $status === 'paid' ? $receivedAt : null,
                'reference' => $data['reference'] ?? null,
                'receipt_url' => $data['receipt_url'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            if ($status === 'paid') {
                $this->recomputeAppointmentFinance($appt);
            }

            return $payment;
        });
    }

    /**
     * Confirma um pagamento pendente.
     * Agora permite confirmar mesmo com outstanding=0 (vira tudo surcharge/extra).
     */
    public function markPaid(int $paymentId, ?string $receivedAt = null): Payment
    {
        $payment = Payment::findOrFail($paymentId);
        if ($payment->status !== 'pending') {
            throw new RuntimeException('Só é possível confirmar pagamentos pendentes.');
        }

        return DB::transaction(function () use ($payment, $receivedAt) {
            /** @var Appointment $appt */
            $appt = $payment->appointment()->lockForUpdate()->first();

            // Calcula aplicado/surcharge no momento da confirmação
            $out = $this->outstanding($appt);
            $applied = round(min((float) $payment->amount_paid, $out), 2);
            $surcharge = round(max(0, (float) $payment->amount_paid - $applied), 2);

            $payment->status = 'paid';
            $payment->received_at = $receivedAt ? Carbon::parse($receivedAt) : now();
            $payment->applied_to_due = $applied;
            $payment->surcharge_amount = $surcharge;
            $payment->save();

            $this->recomputeAppointmentFinance($appt);

            return $payment;
        });
    }

    /**
     * Cancela um pagamento pendente.
     */
    public function cancelPending(int $paymentId, ?string $reason = null): Payment
    {
        $payment = Payment::findOrFail($paymentId);
        if ($payment->status !== 'pending') {
            throw new RuntimeException('Apenas pagamentos pendentes podem ser cancelados.');
        }
        $payment->status = 'canceled';
        $meta = $payment->metadata ?? [];
        $meta['canceled_reason'] = $reason;
        $meta['canceled_at'] = now()->toDateTimeString();
        $payment->metadata = $meta;
        $payment->save();

        return $payment;
    }

    /**
     * Marca um pagamento pendente como falho.
     */
    public function failPending(int $paymentId, ?string $reason = null): Payment
    {
        $payment = Payment::findOrFail($paymentId);
        if ($payment->status !== 'pending') {
            throw new RuntimeException('Apenas pagamentos pendentes podem ser marcados como falha.');
        }
        $payment->status = 'failed';
        $meta = $payment->metadata ?? [];
        $meta['failed_reason'] = $reason;
        $meta['failed_at'] = now()->toDateTimeString();
        $payment->metadata = $meta;
        $payment->save();

        return $payment;
    }

    /**
     * Define isenção (somente se não houve pagamentos pagos).
     */
    public function setExempt(int $appointmentId, ?string $reason = null): Appointment
    {
        $appt = Appointment::findOrFail($appointmentId);

        return DB::transaction(function () use ($appt, $reason) {
            if ($appt->paid_total > 0) {
                throw new RuntimeException('Já há pagamentos efetuados; não é possível isentar.');
            }
            $appt->financial_status = 'exempt';
            $meta = $appt->pricing_meta ?? [];
            $meta['exempt_reason'] = $reason;
            $meta['exempt_at'] = now()->toDateTimeString();
            $appt->pricing_meta = $meta;
            $appt->save();

            return $appt;
        });
    }

    // ---------------- Helpers ----------------

    private function applyDiscount(float $base, ?string $type, ?float $value): float
    {
        if (! $type || ! $value) {
            return round($base, 2);
        }

        if ($type === 'percent') {
            $value = max(0, min(100, $value));
            $final = $base * (1 - $value / 100);
        } else { // fixed
            $final = $base - $value;
        }

        return round(max(0, $final), 2);
    }

    private function recomputeAppointmentFinance(Appointment $appt): void
    {
        // soma APENAS o que foi aplicado ao débito (não soma surcharge)
        $paidApplied = (float) $appt->payments()
            ->where('status', 'paid')
            ->sum(DB::raw('COALESCE(applied_to_due, amount_paid)')); // compat. com registros antigos

        $appt->paid_total = round($paidApplied, 2);

        $due = (float) $appt->price_final;

        if ($appt->financial_status === 'exempt') {
            // mantém isento
        } elseif ($appt->paid_total <= 0) {
            $appt->financial_status = 'pending';
            $appt->paid_at = null;
        } elseif ($appt->paid_total < $due) {
            $appt->financial_status = 'partial';
            $appt->paid_at = null;
        } else {
            $appt->financial_status = 'paid';
            $appt->paid_at = now();
        }

        $appt->save();
    }
}
