<?php

namespace App\Livewire\Admin;

use App\Models\PreRegistration;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Livewire\Attributes\On;

class PreRegistrationList extends Component
{
    use WithPagination, Toast;

    public string $search = '';
    public $filterStatus;
    public string $filterSpecialty = '';
    public array $sortBy = [
        'column' => 'created_at',
        'direction' => 'desc',
    ];

    protected $listeners = ['abrirFicha', 'fecharAgendamento'];
    public bool $showFichaModal = false;
    public ?int $selectedId = null;
    public bool $showAgendamentoModal = false;
    public int $agendamentoId = 0;


    public function abrirFicha($id)
    {
        $this->selectedId = $id;
        $this->showFichaModal = true;
    }

    #[On('abrirAgendamento')]
    public function abrirModalAgendamento(int $id)
    {
        $this->agendamentoId = $id;
        $this->showAgendamentoModal = true;
    }


    public function fecharAgendamento()
    {
        $this->resetPage(); // Ou só $this->render() se preferir
    }

    #[On('refreshComponent')]
    public function refreshComponent()
    {
        // Apenas para re-renderizar o componente.
    }

    public function getHeadersProperty(): array
    {
        return [
            ['key' => 'created_at', 'label' => 'Data de Envio', 'format' => ['date', 'd/m/Y H:i'], 'class' => 'w-32'],
            ['key' => 'scheduled_at', 'label' => 'Agendado Para', 'format' => ['date', 'd/m/Y H:i'], 'class' => 'w-40'],
            ['key' => 'child_name', 'label' => 'Criança'],
            ['key' => 'responsible_name', 'label' => 'Responsável'],
            ['key' => 'link.type', 'label' => 'Tipo'],
            ['key' => 'link.specialty', 'label' => 'Especialidade'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'actions', 'label' => '', 'class' => 'text-right w-44', 'sortable' => false],
        ];
    }

    public function updating($property)
    {
        if (in_array($property, ['search', 'filterStatus', 'filterSpecialty'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = PreRegistration::query()
            ->with('link')
            ->when(!empty($this->filterStatus), fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterSpecialty, fn($q) => $q->whereHas('link', fn($q) => $q->where('specialty', $this->filterSpecialty)))
            ->when($this->search, fn($q) =>
            $q->where('child_name', 'like', '%' . $this->search . '%')
                ->orWhere('responsible_name', 'like', '%' . $this->search . '%')
            )
            ->orderBy(...array_values($this->sortBy));

        $results = $query->paginate(10);

        $specialties = PreRegistration::with('link')->get()->pluck('link.specialty')->unique()->values()->filter()->map(fn($s) => ['id' => $s, 'name' => $s]);

        return view('livewire.admin.pre-registration-list', [
            'preRegistrations' => $results,
            'specialties' => $specialties,
            'headers' => $this->headers,
        ]);
    }

    private function statusColor(string $status): string
    {
        return match($status) {
            'aguardando' => 'badge-warning',
            'agendado' => 'badge-success',
            'cancelado' => 'badge-soft',
            'concluido' => 'badge-dash badge-success',
            default => 'base'
        };
    }

    public function archive($id)
    {
        PreRegistration::where('id', $id)->update(['status' => 'cancelado']);
        $this->toast('success', 'Pré-cadastro arquivado.', '', 'bottom', 'fas.check', 'alert-success');
    }
}
