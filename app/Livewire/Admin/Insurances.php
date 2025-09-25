<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithMaryTable;
use App\Models\Insurance;
use Livewire\Component;
use Livewire\WithPagination;

class Insurances extends Component
{
    use WithMaryTable, WithPagination;

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
            ['key' => 'code', 'label' => 'Código'],
            ['key' => 'active', 'label' => 'Status', 'sortable' => false],
        ];
    }

    public function getRowsProperty()
    {
        return Insurance::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.insurances')->title('Convênios');
    }
}
