<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithMaryTable;
use App\Models\Patient;
use Livewire\Component;
use Livewire\WithPagination;

class Patients extends Component
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
            ['key' => 'id',        'label' => '#', 'class' => 'w-1'],
            ['key' => 'name',      'label' => 'Nome'],
            ['key' => 'birthdate', 'label' => 'Nascimento', 'format' => ['date', 'd/m/Y']],
            ['key' => 'phone',     'label' => 'Telefone'],
            ['key' => 'active',    'label' => 'Status', 'sortable' => false],
        ];
    }

    public function getRowsProperty()
    {
        return Patient::query()
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where('name', 'like', $s)
                    ->orWhere('phone', 'like', $s);
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.patients')->title('Pacientes');
    }
}
