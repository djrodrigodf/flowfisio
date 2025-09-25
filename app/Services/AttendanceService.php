<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentAttendance;
use RuntimeException;

class AttendanceService
{
    public function checkIn(int $appointmentId, string $mode = 'manual', bool $confirmed = false, ?int $userId = null, ?string $notes = null): AppointmentAttendance
    {
        $appt = Appointment::findOrFail($appointmentId);

        if (! in_array($appt->status, ['scheduled', 'rescheduled'])) {
            throw new RuntimeException('Somente agendamentos marcados como scheduled/rescheduled podem fazer check-in.');
        }

        return \DB::transaction(function () use ($appt, $mode, $confirmed, $userId, $notes) {
            $att = AppointmentAttendance::create([
                'appointment_id' => $appt->id,
                'checked_in_at' => now(),
                'mode' => $mode,
                'confirmed' => $confirmed,
                'confirmed_by' => $userId,
                'notes' => $notes,
            ]);

            // (opcional) manter status como scheduled; alguns fluxos preferem marcar "attended" só no fim
            return $att;
        });
    }

    public function markAttended(int $appointmentId, ?int $userId = null): Appointment
    {
        $appt = Appointment::findOrFail($appointmentId);

        if (! in_array($appt->status, ['scheduled', 'rescheduled'])) {
            throw new RuntimeException('Apenas agendamentos scheduled/rescheduled podem ser concluídos como attended.');
        }

        $appt->status = 'attended';
        $appt->save();

        return $appt;
    }

    public function markNoShow(int $appointmentId, ?string $reason = null, ?int $userId = null): Appointment
    {
        $appt = Appointment::findOrFail($appointmentId);

        if ($appt->status === 'attended') {
            throw new RuntimeException('Agendamento já atendido não pode virar falta.');
        }

        $appt->status = 'no_show';
        $meta = $appt->pricing_meta ?? [];
        $meta['no_show_reason'] = $reason;
        $meta['no_show_marked_by'] = $userId;
        $meta['no_show_marked_at'] = now()->toDateTimeString();
        $appt->pricing_meta = $meta;
        $appt->save();

        return $appt;
    }
}
