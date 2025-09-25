<?php

namespace App\Livewire\Admin;

use App\Services\ReportsService;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsOperational extends Component
{
    use WithPagination;

    public string $start;

    public string $end;

    public ?string $status = null;

    public ?int $professional_id = null;

    public array $statusOptions = [
        ['id' => 'scheduled', 'name' => 'Agendado'],
        ['id' => 'rescheduled', 'name' => 'Reagendado'],
        ['id' => 'attended', 'name' => 'Atendido'],
        ['id' => 'no_show', 'name' => 'Falta'],
        ['id' => 'canceled', 'name' => 'Cancelado'],
    ];

    public array $proOptions = []; // [{id,name}]

    public array $headers = [
        ['key' => 'date', 'label' => 'Data'],
        ['key' => 'start_time', 'label' => 'Início'],
        ['key' => 'patient', 'label' => 'Paciente'],
        ['key' => 'professional', 'label' => 'Profissional'],
        ['key' => 'treatment', 'label' => 'Tratamento'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'insurance', 'label' => 'Convênio'],
        ['key' => 'price', 'label' => 'Valor', 'format' => ['currency', '2,.', 'R$ ']],
        ['key' => 'paid_total', 'label' => 'Pago', 'format' => ['currency', '2,.', 'R$ ']],
    ];

    public array $rows = [];

    public function mount(): void
    {
        $this->start = now()->startOfMonth()->toDateString();
        $this->end   = now()->endOfMonth()->toDateString(); // ← antes era toDateString() do “hoje”

        // carrega profissionais como [{id,name}]
        if (class_exists(\App\Models\Professional::class)) {
            $this->proOptions = \App\Models\Professional::query()->select('id', 'name')->orderBy('name')->get()->toArray();
        }

        $this->loadRows();
    }

    public function updatedStart()
    {
        $this->loadRows();
    }

    public function updatedEnd()
    {
        $this->loadRows();
    }

    public function updatedStatus()
    {
        $this->loadRows();
    }

    public function updatedProfessionalId()
    {
        $this->loadRows();
    }

    private function loadRows(): void
    {
        $svc = app(ReportsService::class);
        $filters = [
            'status' => $this->status,
            'professional_id' => $this->professional_id,
        ];
        $this->rows = $svc->datasetAppointments($this->start, $this->end, $filters);

    }

    public function render()
    {
        return view('livewire.admin.reports-operational')->title('Relatórios Operacionais');
    }
}
