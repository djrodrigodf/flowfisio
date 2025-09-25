<?php

namespace App\Livewire\Admin;

use App\Models\Location;
use App\Models\Partner;
use App\Models\Treatment;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Role;

class Partners extends Component
{
    use Toast;
    use WithFileUploads;
    use WithPagination;

    public $partner = [];

    public $photo;

    public $roles;

    public bool $isSpecialist = false;

    public bool $showScheduleModal = false;

    public ?int $selectedPartnerId = null;

    public $showModal = false;

    public array $locOptions = [];

    public array $treatOptions = [];

    public array $treatment_ids = [];

    public array $location_ids = []; // IDs das unidades selecionadas

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
        'location_ids' => 'array',
        'location_ids.*' => 'integer|exists:locations,id',
        'treatment_ids' => 'array',
        'treatment_ids.*' => 'integer|exists:treatments,id',
    ];

    public function mount()
    {
        $this->roles = Role::orderBy('name')->get();
        $this->locOptions = Location::where('active', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();
        $this->refreshTreatOptions(); // inicial
    }

    private function refreshTreatOptions(): void
    {
        $roleId = $this->partner['role_id'] ?? null;

        if ($roleId) {
            // tenta por especialidade
            $ids = \DB::table('role_treatment')->where('role_id', $roleId)->pluck('treatment_id');

            $q = Treatment::query()->select('id', 'name')->orderBy('name');
            if ($ids->count() > 0) {
                $q->whereIn('id', $ids);
            }
            // se a especialidade não tiver mapeamento, mostramos todos
            $this->treatOptions = $q->get()->toArray();
        } else {
            $this->treatOptions = Treatment::select('id', 'name')->orderBy('name')->get()->toArray();
        }

        // garante que nenhum ID “fora da lista atual” permaneça selecionado
        $this->treatment_ids = array_values(array_intersect($this->treatment_ids, array_column($this->treatOptions, 'id')));
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

            $this->refreshTreatOptions();
            $this->treatment_ids = []; // limpa seleção anterior
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
        $this->reset(['partner', 'photo', 'location_ids', 'isSpecialist']);
        $this->showModal = true;
    }

    public function edit(Partner $partner)
    {

        $this->partner = $partner->toArray();
        if ($this->partner['birth_date']) {
            $this->partner['birth_date'] = Carbon::parse($this->partner['birth_date'])->format('Y-m-d');
        }

        $this->isSpecialist = (bool) optional($partner->role)->is_specialty;

        $this->refreshTreatOptions();

        $this->location_ids = $partner->locations()->pluck('locations.id')->toArray();
        $this->treatment_ids = $partner->treatments()->pluck('treatments.id')->toArray();

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
        $partner->treatments()->sync($this->treatment_ids ?? []);
        $partner->locations()->sync($this->location_ids ?? []);

        $this->showModal = false;
        $this->reset(['partner', 'photo', 'location_ids', 'isSpecialist']);
        $this->toast('success', 'Parceiro salvo com sucesso.', '', 'bottom', 'fas.save', 'alert-success');
    }

    public function delete(Partner $partner)
    {
        $partner->clearMediaCollection('profile');
        $partner->delete();

        $this->dispatch('toast', text: 'Parceiro removido!');
    }
}
