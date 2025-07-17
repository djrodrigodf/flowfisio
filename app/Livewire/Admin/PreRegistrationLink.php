<?php

namespace App\Livewire\Admin;

use App\Models\PreRegistrationLink as PreRegistrationLinkModel;
use Illuminate\Support\Str;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class PreRegistrationLink extends Component
{
    public string $type;
    public string $specialty;
    public ?string $generatedLink = null;

    public array $tiposAgendamento = [
        ['id' => 'normal', 'name' => 'Agendamento Normal'],
        ['id' => 'anamnese', 'name' => 'Agendamento Anamnese'],
    ];

    public array $especialidades = [];

    protected function rules()
    {
        return [
            'type' => 'required|in:normal,anamnese',
            'specialty' => 'required|string|max:255',
        ];
    }

    public function mount()
    {
        $this->especialidades = Role::where('is_specialty', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($role) => [
                'id' => $role->name,
                'name' => ucfirst($role->name),
            ])
            ->toArray();
    }

    public function generate()
    {
        $this->validate();

        $token = Str::uuid()->toString();

        $link = PreRegistrationLinkModel::create([
            'type' => $this->type,
            'specialty' => $this->specialty,
            'token' => $token,
            'user_id' => Auth::id(),
        ]);

        $this->generatedLink = route('pre-cadastro', ['token' => $link->token]);
    }

    public function render()
    {
        return view('livewire.admin.pre-registration-link');
    }
}

