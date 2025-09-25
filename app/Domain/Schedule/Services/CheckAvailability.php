<?php

namespace App\Domain\Schedule\Services;

use App\Models\Appointment;
use App\Models\Partner;
use App\Models\Restriction;
use Carbon\Carbon;

class CheckAvailability
{
    /**
     * Lança \InvalidArgumentException com a primeira razão de indisponibilidade encontrada.
     * Retorna true se estiver livre.
     */
    public function handle(
        Carbon $start,
        Carbon $end,
        int $partnerId,
        int $locationId,
        ?int $roomId = null
    ): bool {
        if ($end->lte($start)) {
            throw new \InvalidArgumentException('Horário final deve ser após o inicial.');
        }

        // 1) Conflitos com outros agendamentos (mesmo profissional OU mesma sala)
        $overlap = Appointment::query()
            ->where(function ($q) use ($partnerId, $roomId) {
                $q->where('partner_id', $partnerId);
                if ($roomId) {
                    $q->orWhere('room_id', $roomId);
                }
            })
            ->where(function ($q) use ($start, $end) {
                // overlap: start < end && end > start
                $q->where('start_at', '<', $end)
                    ->where('end_at', '>', $start);
            })
            ->whereNotIn('status', ['canceled'])
            ->exists();

        if ($overlap) {
            throw new \InvalidArgumentException('Conflito com outro agendamento do profissional ou da sala.');
        }

        // 2) Restrições ativas (global, location, room, professional)
        $restr = Restriction::query()
            ->active()
            ->overlapping($start, $end)
            ->where(function ($q) use ($locationId, $roomId, $partnerId) {
                $q->where('scope', 'global')
                    ->orWhere(fn ($w) => $w->where('scope', 'location')->where('scope_id', $locationId))
                    ->orWhere(fn ($w) => $w->where('scope', 'room')->where('scope_id', $roomId))
                    ->orWhere(fn ($w) => $w->where('scope', 'professional')->where('scope_id', $partnerId));
            })
            ->exists();

        if ($restr) {
            throw new \InvalidArgumentException('Período indisponível por restrição de agenda.');
        }

        // 3) Agenda do profissional (PartnerSchedule)
        /** @var Partner $pro */
        $pro = Partner::find($partnerId);
        if ($pro && method_exists($pro, 'schedules')) {
            $dow = strtolower($start->englishDayOfWeek); // monday..sunday
            $ok = $pro->schedules()
                ->where('day_of_week', $dow)
                ->get()
                ->contains(function ($s) use ($start, $end) {
                    // assume colunas start_time/end_time (HH:MM:SS)
                    $sStart = Carbon::parse($start->toDateString().' '.$s->start_time);
                    $sEnd = Carbon::parse($start->toDateString().' '.$s->end_time);

                    return $start->gte($sStart) && $end->lte($sEnd);
                });
            if (! $ok) {
                throw new \InvalidArgumentException('Fora do horário de atendimento do profissional.');
            }
        }

        return true;
    }
}
