<?php

namespace App\Livewire\Admin;

use App\Models\Partner;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;

class Partners extends Component
{
    use WithPagination;
    use WithFileUploads;
    use Toast;

    public $partner = [];
    public $photo;
    public $roles;
    public bool $isSpecialist = false;

    public bool $showScheduleModal = false;
    public int|null $selectedPartnerId = null;

    public $showModal = false;

    protected $listeners = ['closeScheduleModal' => 'closeScheduleModal'];

    public $headers = [
        ['key' => 'photo_path', 'label' => 'Foto', 'class' => 'w-16'],
        ['key' => 'name', 'label' => 'Nome'],
        ['key' => 'role.name', 'label' => 'Cargo'],
        ['key' => 'phone', 'label' => 'Telefone'],
    ];

    protected $rules = [
        'partner.name' => 'required|string|max:255',
        'partner.role_id' => 'required|exists:roles,id',
        'partner.phone' => 'nullable|string',
        'partner.birth_date' => 'nullable|date',
        'partner.email' => 'nullable|email',
        'partner.cpf' => 'nullable|string|max:14',
        'partner.notes' => 'nullable|string',
        'photo' => 'nullable|image|max:2048',
    ];

    public function mount()
    {
        $this->roles = Role::orderBy('name')->get();
    }

    public function closeScheduleModal()
    {
        $this->reset(['showScheduleModal', 'selectedPartnerId']);
    }

    public function updated($propertyName, $value)
    {
        if ($propertyName == 'partner.role_id') {
            $this->partner['is_anamnese'] = false;
            $this->isSpecialist = Role::where('id', $value)
                ->where('is_specialty', true)
                ->exists();
        }
    }

    public function render()
    {
        $partners = Partner::with('role')->latest()->paginate(10);
        return view('livewire.admin.partners', compact('partners'));
    }

    public function openScheduleModal(int $partnerId)
    {
        $this->selectedPartnerId = $partnerId;
        $this->showScheduleModal = true;
    }

    public function new()
    {
        $this->reset(['partner', 'photo']);
        $this->showModal = true;
    }

    public function edit(Partner $partner)
    {

        $this->partner = $partner->toArray();
        if ($this->partner['birth_date']) {
            $this->partner['birth_date'] = Carbon::parse($this->partner['birth_date'])->format('Y-m-d');
        }
        $this->photo = null;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $partner = Partner::updateOrCreate(
            ['id' => $this->partner['id'] ?? null],
            $this->partner
        );

        // Upload via Media Library
        if ($this->photo) {
            $partner->clearMediaCollection('profile');
            $partner->addMedia($this->photo->getRealPath())
                ->usingFileName($this->photo->getClientOriginalName())
                ->toMediaCollection('profile');
        }

        $this->showModal = false;
        $this->reset(['partner', 'photo']);
        $this->toast('success', 'Parceiro salvo com sucesso.', '', 'bottom', 'fas.save', 'alert-success');
    }

    public function delete(Partner $partner)
    {
        $partner->clearMediaCollection('profile');
        $partner->delete();

        $this->dispatch('toast', text: 'Parceiro removido!');
    }
}
