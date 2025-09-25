<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithMaryTable;
use App\Models\Appointment;
use Livewire\Component;
use Livewire\WithPagination;

class Appointments extends Component
{
    use WithMaryTable, WithPagination;

    public ?string $dateStart = null;

    public ?string $dateEnd = null;

    public ?string $status = null;

    public array $statusOptions = [
        ['id' => 'scheduled',   'name' => 'Agendado'],
        ['id' => 'rescheduled', 'name' => 'Reagendado'],
        ['id' => 'attended',    'name' => 'Atendido'],
        ['id' => 'no_show',     'name' => 'Falta'],
        ['id' => 'canceled',    'name' => 'Cancelado'],
    ];

    public function mount(): void
    {
        $this->sortBy = ['column' => 'start_at', 'direction' => 'desc'];
        $this->perPage = 10;
    }

    public function getHeadersProperty(): array
    {
        return [
            ['key' => 'start_at',      'label' => 'Data/Hora', 'format' => ['date', 'd/m/Y H:i']],
            ['key' => 'patient.name',  'label' => 'Paciente',   'sortable' => false],
            ['key' => 'partner.name',  'label' => 'Profissional', 'sortable' => false],
            ['key' => 'treatment.name', 'label' => 'Tratamento', 'sortable' => false],
            ['key' => 'status',        'label' => 'Status'],
            ['key' => 'price',         'label' => 'Valor',      'format' => ['currency', '2,.', 'R$ '], 'class' => 'w-1'],
        ];
    }

    public function getRowsProperty()
    {
        return Appointment::query()
            ->with(['patient:id,name', 'partner:id,name', 'treatment:id,name'])
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(function ($qq) use ($s) {
                    $qq->whereHas('patient', fn ($w) => $w->where('name', 'like', $s))
                        ->orWhereHas('partner', fn ($w) => $w->where('name', 'like', $s))
                        ->orWhereHas('treatment', fn ($w) => $w->where('name', 'like', $s));
                });
            })
            ->when($this->dateStart, fn ($q) => $q->where('start_at', '>=', $this->dateStart.' 00:00:00'))
            ->when($this->dateEnd, fn ($q) => $q->where('start_at', '<=', $this->dateEnd.' 23:59:59'))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.appointments')->title('Atendimentos');
    }
}
