<?php

namespace App\Livewire\Admin;

use App\Domain\Payout\Services\SyncPayout;
use App\Livewire\Concerns\WithMaryTable;
use App\Models\Appointment;             // ⬅️ troca
use App\Models\Partner;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class AttendanceBoard extends Component
{
    use Toast, WithMaryTable, WithPagination;

    public string $date;

    public ?int $partner_id = null;       // ⬅️ troca

    public array $partnerOptions = [];    // ⬅️ troca

    public array $headers = [
        ['key' => 'start_at', 'label' => 'Hora', 'sortable' => true],
        ['key' => 'patient.name', 'label' => 'Paciente', 'sortable' => false],
        ['key' => 'partner.name', 'label' => 'Profissional', 'sortable' => false], // ⬅️ troca
        ['key' => 'treatment.name', 'label' => 'Tratamento', 'sortable' => false],
        ['key' => 'status', 'label' => 'Status', 'sortable' => true],
    ];

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->sortBy = ['column' => 'start_at', 'direction' => 'asc'];
        $this->perPage = 25;

        $this->partnerOptions = Partner::whereHas('role', fn ($q) => $q->where('is_specialty', 1))
            ->select('id', 'name')->orderBy('name')->get()->toArray();
    }

    public function getRowsProperty()
    {
        return Appointment::query()
            ->with(['patient:id,name', 'partner:id,name', 'treatment:id,name']) // ⬅️ troca
            ->whereDate('start_at', $this->date)
            ->when($this->partner_id, fn ($q) => $q->where('partner_id', $this->partner_id)) // ⬅️ troca
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function checkIn(int $id, SyncPayout $sync): void
    {
        $appt = \App\Models\Appointment::findOrFail($id);

        $amountDue = $appt->price
            ?? optional($appt->treatment)->valor_base
            ?? 0;

        // Marca atendido
        $appt->update(['status' => 'attended']);

        // Se não existir pagamento, cria um "pending" com o valor do atendimento
        if (! \App\Models\Payment::where('appointment_id', $appt->id)->exists()) {
            \App\Models\Payment::create([
                'appointment_id' => $appt->id,
                'method' => 'pix',          // ou null para decidir depois
                'status' => 'pending',
                'amount' => $amountDue,
                'amount_paid' => 0,
                'applied_to_due' => 0,
                'surcharge_amount' => 0,
                'received_at' => null,           // ou deixe null até pagar de fato
                'created_by' => auth()->id(),
            ]);
        }

        $sync->handle($appt);

        $this->success('Check-in registrado.');
    }

    public function markNoShow(int $id, SyncPayout $sync): void
    {
        $appt = Appointment::findOrFail($id);
        $appt->update(['status' => 'no_show']);

        // remove item de repasse se já existia
        $sync->removeForAppointment($appt);

        $this->warning('Marcado como falta.');
    }

    public function markCanceled(int $id, SyncPayout $sync): void
    {
        $appt = Appointment::findOrFail($id);
        $appt->update(['status' => 'canceled']);

        // remove item de repasse se já existia
        $sync->removeForAppointment($appt);

        $this->info('Atendimento cancelado.');
    }

    public function render()
    {
        return view('livewire.admin.attendance-board')->title('Presenças');
    }
}
