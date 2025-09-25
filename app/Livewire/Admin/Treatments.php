<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithMaryTable;
use App\Models\Treatment;
use Livewire\Component;
use Livewire\WithPagination;

class Treatments extends Component
{
    use WithMaryTable, WithPagination;

    public array $treatOptions = [];

    public array $treatment_ids = [];

    public function mount(): void
    {
        $this->sortBy = ['column' => 'name', 'direction' => 'asc'];
        $this->perPage = 10;
    }

    public function getHeadersProperty(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Nome'],
            ['key' => 'specialty.name', 'label' => 'Especialidade', 'sortable' => false],
            ['key' => 'type.name', 'label' => 'Tipo', 'sortable' => false],
            ['key' => 'valor_base', 'label' => 'Valor Base', 'format' => ['currency', '2,.', 'R$ ']],
            ['key' => 'active', 'label' => 'Status', 'sortable' => false],
        ];
    }

    public function getRowsProperty()
    {
        return Treatment::query()
            ->with(['specialty:id,name', 'type:id,name'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.treatments')->title('Tratamentos');
    }
}
