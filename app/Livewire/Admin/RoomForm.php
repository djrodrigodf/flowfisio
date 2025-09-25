<?php

namespace App\Livewire\Admin;

use App\Models\Location;
use App\Models\Room;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class RoomForm extends Component
{
    use Toast;

    public ?Room $model = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|exists:locations,id')]
    public ?int $location_id = null;

    #[Validate('nullable|integer|min:1|max:1000')]
    public ?int $capacity = 1;

    public bool $active = true;

    public array $locOptions = [];

    public function mount(): void
    {
        if ($id = request('id')) {
            $this->model = Room::find($id);
        }

        if ($this->model) {
            $this->fill([
                'name' => $this->model->name,
                'location_id' => $this->model->location_id,
                'capacity' => $this->model->capacity ?? 1,
                'active' => (bool) $this->model->active,
            ]);
        }

        // sugiro listar só unidades ativas
        $this->locOptions = Location::where('active', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function save(): void
    {
        // validações base
        $this->validate();

        // validação de unicidade: (location_id, name) único
        $this->validate([
            'name' => 'required|string|max:255|unique:rooms,name,'.($this->model->id ?? 'NULL').',id,location_id,'.$this->location_id,
        ]);

        $data = [
            'name' => $this->name,
            'location_id' => $this->location_id,
            'capacity' => $this->capacity ?: 1,
            'active' => $this->active,
            // 'code' não passa aqui — Observer cuida
        ];

        $this->model
            ? $this->model->update($data)
            : $this->model = Room::create($data);

        $this->success('Sala salva!', redirectTo: route('admin.rooms.index'));
    }

    public function render()
    {
        return view('livewire.admin.room-form')->title($this->model ? 'Editar Sala' : 'Nova Sala');
    }
}
