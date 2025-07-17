<?php

namespace App\Livewire\Admin;

use App\Models\PreRegistration;
use Livewire\Component;

class PreRegistrationFicha extends Component
{
    public ?int $id = null;
    public ?PreRegistration $registration = null;

    public function mount($id)
    {
        $this->id = $id;
        $this->registration = PreRegistration::with(['link', 'additionalResponsibles'])->findOrFail($id);
    }
    public bool $showModalFicha = false;


    protected $listeners = ['abrirFicha'];


    public function render()
    {
        return view('livewire.admin.pre-registration-ficha');
    }
}
