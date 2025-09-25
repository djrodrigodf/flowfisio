<?php

namespace App\Livewire\Admin;

use App\Models\Location;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class LocationForm extends Component
{
    use Toast;

    public ?Location $model = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public ?string $address = null;

    #[Validate('nullable|string|max:255')]
    public ?string $city = null;

    // UF do Brasil: 2 letras; validação por lista
    #[Validate('nullable|string|size:2|in:AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO')]
    public ?string $state = null;

    public bool $active = true;

    public function mount(): void
    {
        if ($id = request('id')) {
            $this->model = Location::find($id);
        }

        if ($this->model) {
            $this->fill([
                'name' => $this->model->name,
                'address' => $this->model->address,
                'city' => $this->model->city,
                'state' => $this->model->state,
                'active' => (bool) $this->model->active,
            ]);
        }
    }

    // opcional: sempre upper na digitação
    public function updatedState($value): void
    {
        $this->state = $value ? Str::upper($value) : null;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:locations,name,'.($this->model->id ?? 'NULL'),
        ]);

        $data = [
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state ? Str::upper($this->state) : null,
            'active' => $this->active,
        ];

        $this->model
            ? $this->model->update($data)
            : $this->model = Location::create($data);

        $this->success('Unidade salva!', redirectTo: route('admin.locations.index'));
    }

    public function render()
    {
        return view('livewire.admin.location-form')->title($this->model ? 'Editar Unidade' : 'Nova Unidade');
    }
}
