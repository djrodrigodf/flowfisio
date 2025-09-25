<?php

namespace App\Livewire\Admin;

use App\Models\Treatment;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class TreatmentForm extends Component
{
    use Toast;

    public ?Treatment $model = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public ?int $specialty_id = null;

    public ?int $treatment_type_id = null;

    #[Validate('nullable|numeric|min:0')]
    public $valor_base = null;

    public bool $active = true;

    public array $specialtyOptions = [];

    public array $typeOptions = [];

    public function mount(): void
    {
        // edição via ?id=#
        if ($id = request('id')) {
            $this->model = Treatment::find($id);
        }

        if ($this->model) {
            $this->fill([
                'name' => $this->model->name,
                'specialty_id' => $this->model->specialty_id,
                'treatment_type_id' => $this->model->treatment_type_id,
                'valor_base' => $this->model->valor_base,
                'active' => (bool) $this->model->active,
            ]);
        }

        // options [{id,name}]
        if (class_exists(\App\Models\Specialty::class)) {
            $this->specialtyOptions = \App\Models\Specialty::select('id', 'name')->orderBy('name')->get()->toArray();
        }
        if (class_exists(\App\Models\TreatmentType::class)) {
            $this->typeOptions = \App\Models\TreatmentType::select('id', 'name')->orderBy('name')->get()->toArray();
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'specialty_id' => $this->specialty_id,
            'treatment_type_id' => $this->treatment_type_id,
            'valor_base' => $this->valor_base ?: 0,
            'active' => $this->active,
        ];

        if ($this->model) {
            $this->model->update($data);
        } else {
            $this->model = Treatment::create($data);
        }

        $this->success('Tratamento salvo!', redirectTo: route('admin.treatments.index'));
    }

    public function render()
    {
        return view('livewire.admin.treatment-form')->title($this->model ? 'Editar Tratamento' : 'Novo Tratamento');
    }
}
