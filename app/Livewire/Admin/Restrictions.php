<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithMaryTable;
use App\Models\Location;
use App\Models\Partner;
use App\Models\Restriction;
use App\Models\Room;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Restrictions extends Component
{
    use Toast, WithMaryTable, WithPagination;

    public ?string $dateStart = null;

    public ?string $dateEnd = null;

    #[Validate('required|in:global,location,room,professional')]
    public string $scope = 'global';

    public ?int $scope_id = null;

    #[Validate('nullable|string|max:255')]
    public ?string $reason = null;

    public bool $active = true;

    public array $headers = [
        ['key' => 'id',           'label' => '#', 'class' => 'w-1'],
        ['key' => 'period',       'label' => 'Período'],
        ['key' => 'scope_label',  'label' => 'Escopo'],
        ['key' => 'reason',       'label' => 'Motivo'],
        ['key' => 'active',       'label' => 'Status'],
    ];

    public array $locOptions = [];

    public array $roomOptions = [];

    public array $proOptions = [];

    public function mount(): void
    {
        $this->sortBy = ['column' => 'id', 'direction' => 'desc'];
        $this->perPage = 10;

        $this->locOptions = Location::where('active', 1)->select('id', 'name')->orderBy('name')->get()->toArray();
        $this->roomOptions = Room::where('active', 1)->select('id', 'name')->orderBy('name')->get()->toArray();

        if (class_exists(Partner::class)) {
            $this->proOptions = Partner::whereHas('role', fn ($q) => $q->where('is_specialty', 1))
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->toArray();
        }
    }

    public function updatedScope($v): void
    {
        if ($v === 'global') {
            $this->scope_id = null;
        }
    }

    public function getRowsProperty()
    {
        $rows = Restriction::query()
            ->with('subject')
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);

        // Como o Mary aceita objetos, podemos só expor os acessores do model
        // Se preferir “enriquecer”:
        $rows->getCollection()->transform(function (Restriction $r) {
            // já temos $r->period e $r->scope_label por accessor
            return $r;
        });

        return $rows;
    }

    public function save(): void
    {
        // validações base da UI
        $this->validate(); // valida scope IN global,location,room,professional

        if (! $this->dateStart || ! $this->dateEnd) {
            $this->error('Informe o período.');

            return;
        }

        // regras adicionais por tipo real
        $rules = [
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date|after_or_equal:dateStart',
            'reason' => 'nullable|string|max:255',
            'active' => 'boolean',
        ];

        if ($this->scope !== 'global') {
            $rules['scope_id'] = match ($this->scope) {
                'location' => 'required|exists:locations,id',
                'room' => 'required|exists:rooms,id',
                'professional' => 'required|exists:partners,id', // troque p/ partners,id se for Partner
            };
        }

        $this->validate($rules);

        // normaliza datas
        $start = \Carbon\Carbon::parse($this->dateStart)->startOfDay();
        $end = \Carbon\Carbon::parse($this->dateEnd)->endOfDay();

        // 👇 aqui está o ponto-chave
        Restriction::create([
            'scope' => $this->scope === 'global' ? null : $this->scope,
            'scope_id' => $this->scope === 'global' ? null : $this->scope_id,
            'start_at' => $start,
            'end_at' => $end,
            'reason' => $this->reason,
            'active' => $this->active,
        ]);

        $this->reset(['dateStart', 'dateEnd', 'scope', 'scope_id', 'reason', 'active']);
        $this->scope = 'global';
        $this->active = true;

        $this->success('Restrição adicionada.');
    }

    public function render()
    {
        return view('livewire.admin.restrictions')->title('Restrições');
    }
}
