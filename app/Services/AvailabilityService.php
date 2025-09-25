<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Holiday;
use App\Models\Professional;
use App\Models\ProfessionalTimeoff;
use App\Models\ScheduleBlock;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Retorna slots disponíveis para um profissional entre $startDate e $endDate (Y-m-d),
     * considerando agenda-modelo + feriados + timeoffs + bloqueios + APPOINTMENTS já existentes.
     * Se $roomId for fornecido, filtra/disputa por sala; caso contrário usa room do schedule.
     * $ignoreAppointmentId: útil para reagendamentos (ignora o próprio).
     */
    public function getAvailability(
        int $professionalId,
        string $startDate,
        string $endDate,
        ?int $roomId = null,
        ?int $ignoreAppointmentId = null
    ): array {
        $pro = Professional::with(['schedules' => fn ($q) => $q->where('active', true)])->findOrFail($professionalId);

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Feriados no range
        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // Timeoffs do profissional
        $timeoffs = ProfessionalTimeoff::where('professional_id', $pro->id)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('starts_at', [$start, $end])
                    ->orWhereBetween('ends_at', [$start, $end])
                    ->orWhere(fn ($w) => $w->where('starts_at', '<=', $start)->where('ends_at', '>=', $end));
            })->get();

        // Bloqueios
        $blocks = ScheduleBlock::where(function ($q) use ($pro) {
            $q->whereNull('professional_id')->orWhere('professional_id', $pro->id);
        })
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('starts_at', [$start, $end])
                    ->orWhereBetween('ends_at', [$start, $end])
                    ->orWhere(fn ($w) => $w->where('starts_at', '<=', $start)->where('ends_at', '>=', $end));
            })->get();

        // [NOVO] Ocupações reais (appointments) no range
        $appointments = Appointment::where('professional_id', $pro->id)
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->whereBetween('start_at', [$start, $end])
            ->orWhere(function ($q) use ($pro, $start, $end, $ignoreAppointmentId) {
                $q->where('professional_id', $pro->id)
                    ->when($ignoreAppointmentId, fn ($qq) => $qq->where('id', '!=', $ignoreAppointmentId))
                    ->whereBetween('end_at', [$start, $end]);
            })
            ->get(['id', 'room_id', 'start_at', 'end_at']);

        $slots = [];

        foreach (CarbonPeriod::create($start, $end) as $day) {
            $dateStr = $day->toDateString();

            // pula feriado
            if (in_array($dateStr, $holidays, true)) {
                continue;
            }

            $weekday = (int) $day->dayOfWeek;

            $daySchedules = $pro->schedules->where('weekday', $weekday);

            foreach ($daySchedules as $s) {
                $slotMinutes = max(5, (int) $s->slot_minutes);
                $blockStart = Carbon::parse($dateStr.' '.$s->start_time);
                $blockEnd = Carbon::parse($dateStr.' '.$s->end_time);

                for ($cursor = $blockStart->copy(); $cursor->lt($blockEnd); $cursor->addMinutes($slotMinutes)) {
                    $slotStart = $cursor->copy();
                    $slotEnd = $cursor->copy()->addMinutes($slotMinutes);
                    if ($slotEnd->gt($blockEnd)) {
                        break;
                    }

                    // Timeoff
                    if ($this->overlapsAny($slotStart, $slotEnd, $timeoffs, 'starts_at', 'ends_at')) {
                        continue;
                    }

                    // Bloqueios (respeitando room quando houver)
                    $conflictBlock = $blocks->first(function ($b) use ($slotStart, $slotEnd, $s, $roomId) {
                        if ($b->room_id) {
                            $roomToCheck = $roomId ?? $s->room_id;
                            if ($roomToCheck && $b->room_id !== $roomToCheck) {
                                return false;
                            }
                        }

                        return $this->overlapInterval($slotStart, $slotEnd, Carbon::parse($b->starts_at), Carbon::parse($b->ends_at));
                    });
                    if ($conflictBlock) {
                        continue;
                    }

                    // [NOVO] Appointments ocupando (profissional/sala)
                    $roomToCheck = $roomId ?? $s->room_id;
                    $conflictAppt = $appointments->first(function ($a) use ($slotStart, $slotEnd, $roomToCheck) {
                        // Se a sala estiver definida, só conflita se coincidir ou se a ocupação não tiver sala definida
                        if ($roomToCheck && $a->room_id && $a->room_id !== $roomToCheck) {
                            return false;
                        }

                        return $this->overlapInterval($slotStart, $slotEnd, Carbon::parse($a->start_at), Carbon::parse($a->end_at));
                    });
                    if ($conflictAppt) {
                        continue;
                    }

                    $slots[] = [
                        'start' => $slotStart->toDateTimeString(),
                        'end' => $slotEnd->toDateTimeString(),
                        'room_id' => $roomToCheck, // pode ser null se não definir sala
                    ];
                }
            }
        }

        return $slots;
    }

    private function overlapInterval(Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): bool
    {
        return $aStart->lt($bEnd) && $bStart->lt($aEnd);
    }

    private function overlapsAny(Carbon $start, Carbon $end, Collection $collection, string $startKey, string $endKey): bool
    {
        return $collection->first(function ($item) use ($start, $end, $startKey, $endKey) {
            return $this->overlapInterval($start, $end, Carbon::parse($item->{$startKey}), Carbon::parse($item->{$endKey}));
        }) !== null;
    }
}
