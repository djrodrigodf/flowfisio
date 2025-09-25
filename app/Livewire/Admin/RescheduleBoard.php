<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithMaryTable;
use App\Models\Appointment;
use App\Models\Partner;
use App\Models\Patient;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class RescheduleBoard extends Component
{
    use Toast, WithMaryTable, WithPagination;

    public ?string $dateStart = null;

    public ?string $dateEnd = null;

    public ?int $partner_id = null;

    public array $partnerOptions = [];

    public array $roomOptions = [];

    public array $expanded = [];

    public array $edit = [];

    public ?int $patient_id = null;

    public array $patientOptions = [];

    public array $headers = [
        ['key' => 'start_at',      'label' => 'Data/Hora'],
        ['key' => 'patient.name',  'label' => 'Paciente',     'sortable' => false],
        ['key' => 'partner.name',  'label' => 'Profissional', 'sortable' => false],
        ['key' => 'treatment.name', 'label' => 'Tratamento',   'sortable' => false],
        ['key' => 'status',        'label' => 'Status'],
    ];

    public function mount(): void
    {
        $this->sortBy = ['column' => 'start_at', 'direction' => 'asc'];
        $this->perPage = 10;
        $this->dateStart = now()->toDateString();
        $this->dateEnd = now()->addDays(7)->toDateString();

        // Profissionais (Partners) que são especialistas
        $this->partnerOptions = Partner::whereHas('role', fn ($q) => $q->where('is_specialty', 1))
            ->select('id', 'name')->orderBy('name')->get()->toArray();

        $this->patientOptions = Patient::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->roomOptions = Room::select('id', 'name')->orderBy('name')->get()->toArray();
    }

    public function getRowsProperty()
    {
        return Appointment::query()
            ->with(['patient:id,name', 'partner:id,name', 'treatment:id,name'])
            ->when($this->dateStart, fn ($q) => $q->where('start_at', '>=', $this->dateStart.' 00:00:00'))
            ->when($this->dateEnd, fn ($q) => $q->where('start_at', '<=', $this->dateEnd.' 23:59:59'))
            ->when($this->partner_id, fn ($q) => $q->where('partner_id', $this->partner_id))
            ->when($this->patient_id, fn ($q) => $q->where('patient_id', $this->patient_id))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function openRow(int $id): void
    {
        $appt = Appointment::findOrFail($id);

        $this->edit[$id] = [
            'datetime' => optional($appt->start_at)->format('Y-m-d\TH:i'),
            'partner_id' => $appt->partner_id,
            'room_id' => $appt->room_id,
        ];

        // abre a linha
        if (! in_array($id, $this->expanded, true)) {
            $this->expanded[] = $id;
        }
    }

    public function saveRow(int $id): void
    {
        $data = $this->edit[$id] ?? [];
        $start = ! empty($data['datetime']) ? Carbon::parse($data['datetime']) : null;

        if (! $start) {
            $this->error('Informe a nova data/hora.');

            return;
        }

        Appointment::whereKey($id)->update([
            'start_at' => $start,
            'partner_id' => $data['partner_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'status' => 'rescheduled',
        ]);

        $this->success('Atendimento reagendado.');
        $this->expanded = array_values(array_diff($this->expanded, [$id]));
        unset($this->edit[$id]);
    }

    public function render()
    {
        return view('livewire.admin.reschedule-board')->title('Reagendamentos');
    }
}
