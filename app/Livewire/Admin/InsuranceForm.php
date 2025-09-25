<?php

namespace App\Livewire\Admin;

use App\Models\Insurance;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class InsuranceForm extends Component
{
    use Toast;

    public ?Insurance $model = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public ?string $code = null;

    public bool $active = true;

    public function mount(): void
    {
        if ($id = request('id')) {
            $this->model = Insurance::find($id);
        }
        if ($this->model) {
            $this->fill([
                'name' => $this->model->name,
                'code' => $this->model->code,
                'active' => (bool) $this->model->active,
            ]);
        }
    }

    public function save(): void
    {
        $this->validate();
        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'active' => $this->active,
        ];

        if ($this->model) {
            $this->model->update($data);
        } else {
            $this->model = Insurance::create($data);
        }

        $this->success('Convênio salvo!', redirectTo: route('admin.insurances.index'));
    }

    public function render()
    {
        return view('livewire.admin.insurance-form')->title($this->model ? 'Editar Convênio' : 'Novo Convênio');
    }
}
