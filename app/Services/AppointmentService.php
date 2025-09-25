<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentReschedule;
use App\Models\Insurance;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Treatment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AppointmentService
{
    public function __construct(
        protected PricingService $pricing,
        protected AvailabilityService $availability
    ) {}

    /**
     * Cotar preço/repasse para um tratamento numa data.
     */
    public function quote(int $treatmentId, ?int $insuranceId, string $dateTime, ?float $discountValue = null, ?string $discountType = null): array
    {
        $treatment = Treatment::findOrFail($treatmentId);
        $insurance = $insuranceId ? Insurance::findOrFail($insuranceId) : null;
        $date = Carbon::parse($dateTime);

        $priceBase = $this->pricing->resolvePrice($treatment, $insurance, $date);
        $priceFinal = $this->applyDiscount($priceBase, $discountType, $discountValue);

        $payout = $this->pricing->resolvePayout($treatment, $insurance, $date, $priceFinal);

        return [
            'price_base' => $priceBase,
            'price_final' => $priceFinal,
            'payout' => $payout,
        ];
    }

    /**
     * Cria agendamento com validações:
     * - slot precisa existir na disponibilidade
     * - checa conflitos (appointments) com lock transacional
     * - grava snapshot de preço/repasse
     */
    public function create(array $data): Appointment
    {
        // Esperado em $data:
        // patient_id, professional_id, treatment_id, insurance_id|null,
        // start_at (Y-m-d H:i), end_at (Y-m-d H:i),
        // location_id|null, room_id|null,
        // discount_type|null, discount_value|null, notes|null

        $patient = Patient::findOrFail($data['patient_id']);
        $professional = Professional::findOrFail($data['professional_id']);
        $treatment = Treatment::findOrFail($data['treatment_id']);
        $insurance = ! empty($data['insurance_id']) ? Insurance::find($data['insurance_id']) : null;

        $start = Carbon::parse($data['start_at']);
        $end = Carbon::parse($data['end_at']);
        if ($end->lte($start)) {
            throw new RuntimeException('Horário inválido: fim deve ser após o início.');
        }

        // 1) Validar que o slot está nas disponibilidades (protege contra "qualquer horário")
        $dayStart = $start->copy()->startOfDay()->toDateString();
        $dayEnd = $start->copy()->endOfDay()->toDateString();
        $slots = $this->availability->getAvailability(
            $professional->id,
            $dayStart,
            $dayEnd,
            $data['room_id'] ?? null
        );

        $match = collect($slots)->first(function ($s) use ($start, $end, $data) {
            return $s['start'] === $start->toDateTimeString()
                && $s['end'] === $end->toDateTimeString()
                && (($data['room_id'] ?? null) === ($s['room_id'] ?? null));
        });

        if (! $match) {
            throw new RuntimeException('Slot indisponível (agenda, bloqueios ou já ocupado).');
        }

        // 2) Cotar preço/repasse (snapshot)
        $quote = $this->quote(
            $treatment->id,
            $insurance?->id,
            $start->toDateTimeString(),
            $data['discount_value'] ?? null,
            $data['discount_type'] ?? null
        );

        // 3) SALVAR com lock, checando conflitos em paralelo
        return DB::transaction(function () use ($data, $professional, $start, $end, $quote, $treatment, $insurance) {

            // Lock pessimista (PROFISSIONAL)
            $conflicts = Appointment::where('professional_id', $professional->id)
                ->overlapping($start, $end)
                ->lockForUpdate()
                ->get();

            if ($conflicts->isNotEmpty()) {
                throw new RuntimeException('Conflito: profissional já possui agendamento no intervalo.');
            }

            // Lock pessimista (SALA) se fornecida
            if (! empty($data['room_id'])) {
                $conflictsRoom = Appointment::where('room_id', $data['room_id'])
                    ->overlapping($start, $end)
                    ->lockForUpdate()
                    ->get();

                if ($conflictsRoom->isNotEmpty()) {
                    throw new RuntimeException('Conflito: sala já está ocupada no intervalo.');
                }
            }

            // Persistir
            $appt = Appointment::create([
                'patient_id' => $data['patient_id'],
                'professional_id' => $professional->id,
                'treatment_id' => $treatment->id,
                'insurance_id' => $insurance?->id,
                'location_id' => $data['location_id'] ?? null,
                'room_id' => $data['room_id'] ?? null,
                'start_at' => $start,
                'end_at' => $end,
                'status' => 'scheduled',

                'price_base' => $quote['price_base'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? null,
                'price_final' => $quote['price_final'],
                'payout_value_snapshot' => $quote['payout'],

                'pricing_meta' => [
                    'quoted_at' => now()->toDateTimeString(),
                ],
                'notes' => $data['notes'] ?? null,
            ]);

            return $appt;
        });
    }

    private function applyDiscount(float $base, ?string $type, ?float $value): float
    {
        if (! $type || ! $value) {
            return round($base, 2);
        }

        if ($type === 'percent') {
            $value = max(0, min(100, $value)); // clamp 0..100
            $final = $base * (1 - $value / 100);
        } else { // fixed
            $final = $base - $value;
        }

        return round(max(0, $final), 2);
    }

    public function reschedule(array $data): Appointment
    {
        // Esperado:
        // appointment_id, new_start_at, new_end_at, new_room_id|null, reason|null, user_id|null, recalc (bool, default true)
        $appt = Appointment::findOrFail($data['appointment_id']);

        $newStart = \Carbon\Carbon::parse($data['new_start_at']);
        $newEnd = \Carbon\Carbon::parse($data['new_end_at']);
        if ($newEnd->lte($newStart)) {
            throw new \RuntimeException('Horário inválido no reagendamento.');
        }

        // 1) validar que o NOVO slot existe (ignorando o próprio agendamento atual)
        $slots = $this->availability->getAvailability(
            $appt->professional_id,
            $newStart->copy()->startOfDay()->toDateString(),
            $newStart->copy()->endOfDay()->toDateString(),
            $data['new_room_id'] ?? $appt->room_id,
            $appt->id // ignore o próprio
        );

        $match = collect($slots)->first(fn ($s) => $s['start'] === $newStart->toDateTimeString()
            && $s['end'] === $newEnd->toDateTimeString()
            && (($data['new_room_id'] ?? $appt->room_id) === ($s['room_id'] ?? null))
        );

        if (! $match) {
            throw new \RuntimeException('Novo slot indisponível para reagendamento.');
        }

        // 2) lock + checagem de conflitos e gravação
        return \DB::transaction(function () use ($data, $appt, $newStart, $newEnd) {

            // lock profissional
            $conflicts = Appointment::where('professional_id', $appt->professional_id)
                ->where('id', '!=', $appt->id)
                ->overlapping($newStart, $newEnd)
                ->lockForUpdate()
                ->get();
            if ($conflicts->isNotEmpty()) {
                throw new \RuntimeException('Conflito: profissional ocupado no novo horário.');
            }

            // lock sala
            $newRoomId = $data['new_room_id'] ?? $appt->room_id;
            if ($newRoomId) {
                $confRoom = Appointment::where('room_id', $newRoomId)
                    ->where('id', '!=', $appt->id)
                    ->overlapping($newStart, $newEnd)
                    ->lockForUpdate()
                    ->get();
                if ($confRoom->isNotEmpty()) {
                    throw new \RuntimeException('Conflito: sala ocupada no novo horário.');
                }
            }

            // 3) histórico
            AppointmentReschedule::create([
                'appointment_id' => $appt->id,
                'old_start_at' => $appt->start_at,
                'old_end_at' => $appt->end_at,
                'old_room_id' => $appt->room_id,
                'new_start_at' => $newStart,
                'new_end_at' => $newEnd,
                'new_room_id' => $newRoomId,
                'reason' => $data['reason'] ?? null,
                'user_id' => $data['user_id'] ?? null,
            ]);

            // 4) opcional: recalcular preço/repasse (padrão true)
            $recalc = array_key_exists('recalc', $data) ? (bool) $data['recalc'] : true;
            if ($recalc) {
                $quote = $this->quote(
                    $appt->treatment_id,
                    $appt->insurance_id,
                    $newStart->toDateTimeString(),
                    $appt->discount_value,
                    $appt->discount_type
                );

                $appt->price_base = $quote['price_base'];
                $appt->price_final = $quote['price_final'];
                $appt->payout_value_snapshot = $quote['payout'];
                $meta = $appt->pricing_meta ?? [];
                $meta['rescheduled_recalculated_at'] = now()->toDateTimeString();
                $appt->pricing_meta = $meta;
            }

            // 5) aplicar novo horário/sala
            $appt->start_at = $newStart;
            $appt->end_at = $newEnd;
            $appt->room_id = $newRoomId;

            // (opcional) marcar status como 'rescheduled'
            $appt->status = 'rescheduled';

            $appt->save();

            return $appt;
        });
    }
}
