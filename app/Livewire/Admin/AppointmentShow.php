<?php

namespace App\Livewire\Admin;

use App\Domain\Payout\Services\SyncPayout;
use App\Models\Appointment;
use App\Models\AppointmentReschedule;
use App\Models\Restriction;
use App\Models\Room;
use Carbon\Carbon;
use Livewire\Component;
use Mary\Traits\Toast;

class AppointmentShow extends Component
{
    use Toast;

    public ?Appointment $appointment = null;

    public bool $showEdit = false;

    public array $edit = [
        'datetime' => null,
        'room_id' => null,
    ];

    public array $roomOptions = [];

    public array $statusClass = [
        'scheduled' => 'badge-info badge-soft',
        'attended' => 'badge-success badge-soft',
        'no_show' => 'badge-warning badge-soft',
        'rescheduled' => 'badge-ghost',
        'canceled' => 'badge-error badge-soft',
    ];

    public function mount($appointment = null): void
    {
        if ($appointment instanceof Appointment) {
            $this->appointment = $appointment->load([
                'patient:id,name,document,birthdate,email,phone,phone_alt,address,city,state,zip_code',
                'partner:id,name',
                'treatment:id,name',
                'insurance:id,name',
                'location:id,name',
                'room:id,name,location_id',
                'reschedules', // << AQUI
            ]);
        } else {
            $id = $appointment ?? request('appointment') ?? request('id');
            abort_if(empty($id), 404);

            $this->appointment = Appointment::with([
                'patient:id,name,document,birthdate,email,phone,phone_alt,address,city,state,zip_code',
                'partner:id,name',
                'treatment:id,name',
                'insurance:id,name',
                'location:id,name',
                'room:id,name,location_id',
                'reschedules', // << AQUI
            ])->findOrFail($id);
        }

        $this->refreshRoomOptions();
    }

    private function refreshRoomOptions(): void
    {
        $locId = $this->appointment?->location_id;
        $this->roomOptions = $locId
            ? Room::where('location_id', $locId)->where('active', 1)->select('id', 'name')->orderBy('name')->get()->toArray()
            : [];
    }

    public function openEdit(): void
    {
        $this->edit = [
            'datetime' => optional($this->appointment->start_at)->format('Y-m-d\TH:i'),
            'room_id' => $this->appointment->room_id,
        ];
        $this->showEdit = true;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'edit.datetime' => 'required|date_format:Y-m-d\TH:i',
            'edit.room_id' => 'nullable|exists:rooms,id',
        ]);

        $start = Carbon::createFromFormat('Y-m-d\TH:i', $this->edit['datetime']);
        $duration = (int) ($this->appointment->duration_min ?? 50);
        $end = (clone $start)->addMinutes($duration);

        // conflitos
        $conflictAppt = Appointment::query()
            ->where('id', '!=', $this->appointment->id)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->where(function ($q) {
                $q->where('partner_id', $this->appointment->partner_id);
                if ($this->edit['room_id']) {
                    $q->orWhere('room_id', $this->edit['room_id']);
                }
            })->exists();

        if ($conflictAppt) {
            $this->error('Conflito de horário com o profissional ou sala.');

            return;
        }

        // restrições
        $hasRestrict = class_exists(Restriction::class) ? Restriction::query()
            ->active()
            ->overlapping($start, $end)
            ->where(function ($q) {
                $q->where('scope', 'global')
                    ->orWhere(fn ($w) => $w->where('scope', 'location')->where('scope_id', $this->appointment->location_id))
                    ->orWhere(fn ($w) => $w->where('scope', 'room')->where('scope_id', $this->edit['room_id']))
                    ->orWhere(fn ($w) => $w->where('scope', 'partner')->where('scope_id', $this->appointment->partner_id));
            })->exists() : false;

        if ($hasRestrict) {
            $this->warning('Existe uma restrição para esse período.');

            return;
        }

        // histórico de reagendamento (opcional mas recomendado)
        AppointmentReschedule::create([
            'appointment_id' => $this->appointment->id,
            'old_start_at' => $this->appointment->start_at,
            'old_end_at' => $this->appointment->end_at,
            'old_room_id' => $this->appointment->room_id,
            'new_start_at' => $start,
            'new_end_at' => $end,
            'new_room_id' => $this->edit['room_id'],
            'reason' => null,
            'user_id' => auth()->id(),
        ]);

        // atualiza agendamento
        $this->appointment->update([
            'start_at' => $start,
            'end_at' => $end,
            'room_id' => $this->edit['room_id'],
            'status' => 'rescheduled',
        ]);

        $this->showEdit = false;

        // recarrega
        $this->mount($this->appointment->id);
        $this->success('Agendamento reagendado.');
    }

    public function markAttended(SyncPayout $sync): void
    {
        $this->appointment->update(['status' => 'attended']);
        $sync->handle($this->appointment);
        $this->success('Marcado como atendido.');
        $this->mount($this->appointment->id);
    }

    public function markNoShow(SyncPayout $sync): void
    {
        $this->appointment->update(['status' => 'no_show']);
        $sync->removeForAppointment($this->appointment);
        $this->warning('Marcado como falta.');
        $this->mount($this->appointment->id);
    }

    public function cancel(SyncPayout $sync): void
    {
        $this->appointment->update(['status' => 'canceled']);
        $sync->removeForAppointment($this->appointment);
        $this->info('Agendamento cancelado.');
        $this->mount($this->appointment->id);
    }

    public function render()
    {
        return view('livewire.admin.appointment-show')->title('Detalhes do Atendimento');
    }
}
