<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithMaryTable;
use App\Models\Appointment;
use App\Models\Insurance;
use App\Models\Location;
use App\Models\Partner;
use App\Models\Room;
use App\Models\Treatment;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class AppointmentIndex extends Component
{
    use Toast, WithMaryTable, WithPagination;

    // Filtros
    public ?string $date_from = null;   // Y-m-d

    public ?string $date_to = null;   // Y-m-d

    public ?int $partner_id = null;

    public ?int $patient_id = null;   // opcional: se quiser autocomplete depois

    public ?int $location_id = null;

    public ?int $room_id = null;

    public ?int $insurance_id = null;

    public ?int $treatment_id = null;

    public ?string $status = null;   // scheduled | attended | no_show | rescheduled | canceled

    public ?string $searchPatient = null;   // busca por nome do paciente

    // Options
    public array $partnerOptions = [];

    public array $locationOptions = [];

    public array $roomOptions = [];

    public array $insuranceOptions = [];

    public array $treatmentOptions = [];

    // Tabela
    public array $headers = [
        ['key' => 'start_at', 'label' => 'Data/Hora', 'sortable' => true],
        ['key' => 'patient.name', 'label' => 'Paciente', 'sortable' => false],
        ['key' => 'partner.name', 'label' => 'Profissional', 'sortable' => false],
        ['key' => 'treatment.name', 'label' => 'Tratamento', 'sortable' => false],
        ['key' => 'place', 'label' => 'Local', 'sortable' => false],
        ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ['key' => 'price', 'label' => 'Preço', 'sortable' => true, 'class' => 'text-right'],
    ];

    public function mount(): void
    {
        $this->perPage = 10;
        $this->sortBy = ['column' => 'start_at', 'direction' => 'desc'];

        $this->partnerOptions = Partner::whereHas('role', fn ($q) => $q->where('is_specialty', 1))
            ->select('id', 'name')->orderBy('name')->get()->toArray();

        $this->locationOptions = Location::active()->select('id', 'name')->orderBy('name')->get()->toArray();
        $this->roomOptions = Room::active()->select('id', 'name')->orderBy('name')->get()->toArray();

        $this->insuranceOptions = class_exists(Insurance::class)
            ? Insurance::select('id', 'name')->orderBy('name')->get()->toArray()
            : [];

        $this->treatmentOptions = Treatment::select('id', 'name')->orderBy('name')->get()->toArray();
    }

    public function updatedLocationId($id): void
    {
        // filtra salas pela unidade (conveniência)
        $this->room_id = null;
        $this->roomOptions = $id
            ? Room::where('location_id', $id)->active()->select('id', 'name')->orderBy('name')->get()->toArray()
            : [];
    }

    public function clearFilters(): void
    {
        $this->reset([
            'date_from', 'date_to', 'partner_id', 'patient_id', 'location_id', 'room_id',
            'insurance_id', 'treatment_id', 'status', 'searchPatient',
        ]);
    }

    public function getRowsProperty()
    {
        $q = Appointment::query()
            ->with(['patient:id,name', 'partner:id,name', 'treatment:id,name', 'location:id,name', 'room:id,name', 'insurance:id,name']);

        // Filtros
        $q->when($this->date_from, fn ($qq) => $qq->whereDate('start_at', '>=', $this->date_from));
        $q->when($this->date_to, fn ($qq) => $qq->whereDate('start_at', '<=', $this->date_to));
        $q->when($this->partner_id, fn ($qq) => $qq->where('partner_id', $this->partner_id));
        $q->when($this->location_id, fn ($qq) => $qq->where('location_id', $this->location_id));
        $q->when($this->room_id, fn ($qq) => $qq->where('room_id', $this->room_id));
        $q->when($this->insurance_id, fn ($qq) => $qq->where('insurance_id', $this->insurance_id));
        $q->when($this->treatment_id, fn ($qq) => $qq->where('treatment_id', $this->treatment_id));
        $q->when($this->status, fn ($qq) => $qq->where('status', $this->status));

        // Busca por nome do paciente
        $q->when($this->searchPatient, function ($qq) {
            $s = trim($this->searchPatient);
            $qq->whereHas('patient', fn ($w) => $w->where('name', 'like', "%{$s}%"));
        });

        // Ordenação
        $q->orderBy($this->sortBy['column'] ?? 'start_at', $this->sortBy['direction'] ?? 'desc');

        $page = $q->paginate($this->perPage);

        // Mapeia campos derivados pro table
        $page->getCollection()->transform(function (Appointment $a) {
            $a->place = trim(collect([$a->location?->name, $a->room?->name])->filter()->implode(' • '));
            $a->price = (float) ($a->price ?? 0);

            return $a;
        });

        return $page;
    }

    // Ações
    public function markAttended(int $id): void
    {
        $a = Appointment::findOrFail($id);
        if ($a->status !== 'scheduled') {
            $this->error('Só é possível finalizar agendamentos em estado "scheduled".');

            return;
        }
        $a->status = 'attended';
        $a->save();
        $this->success('Agendamento marcado como atendido.');
    }

    public function cancel(int $id): void
    {
        $a = Appointment::findOrFail($id);
        if (! in_array($a->status, ['scheduled', 'rescheduled'])) {
            $this->error('Somente agendamentos ativos podem ser cancelados.');

            return;
        }
        $a->status = 'canceled';
        $a->save();
        $this->success('Agendamento cancelado.');
    }

    public function render()
    {
        return view('livewire.admin.appointment-index')->title('Agendamentos');
    }
}
