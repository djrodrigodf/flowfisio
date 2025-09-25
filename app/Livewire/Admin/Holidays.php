<?php

namespace App\Livewire\Admin;

use App\Domain\Calendar\Actions\ImportHolidaysFromInvertexto;
use App\Domain\Calendar\Exceptions\AlreadyImportedException;
use App\Livewire\Concerns\WithMaryTable;
use App\Models\Holiday;
use App\Models\Location; // profissional = Partner (Spatie)
use App\Models\Partner;
use App\Models\Room;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Holidays extends Component
{
    use Toast, WithMaryTable, WithPagination;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|date')]
    public ?string $date = null;

    #[Validate('boolean')]
    public bool $is_recurring = true;

    #[Validate('required|in:global,location,room,professional')]
    public string $scope = 'global';

    public ?int $scope_id = null;

    public bool $active = true;

    public array $headers = [
        ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
        ['key' => 'date_label', 'label' => 'Data'],
        ['key' => 'name', 'label' => 'Nome'],
        ['key' => 'scope_label', 'label' => 'Escopo'],
        ['key' => 'active', 'label' => 'Status'],
    ];

    public array $locOptions = [];

    public array $roomOptions = [];

    public array $proOptions = [];

    public int $import_year;

    public bool $import_include_optional = true;

    public string $import_state;

    public ?string $import_scope = 'global'; // 'global'|'location'|'room'|'professional'

    public ?int $import_scope_id = null;

    public function mount(): void
    {
        $this->sortBy = ['column' => 'id', 'direction' => 'desc'];
        $this->perPage = 10;

        $this->locOptions = Location::where('active', 1)->select('id', 'name')->orderBy('name')->get()->toArray();
        $this->roomOptions = Room::where('active', 1)->select('id', 'name')->orderBy('name')->get()->toArray();
        $this->proOptions = Partner::whereHas('role', fn ($q) => $q->where('is_specialty', 1))
            ->select('id', 'name')->orderBy('name')->get()->toArray();
        $this->import_year = now()->year;
        $this->import_state = config('holidays.invertexto.state', 'DF');
    }

    public function updatedScope($v): void
    {
        if ($v === 'global') {
            $this->scope_id = null;
        }
    }

    public function updatedImportScope($v): void
    {
        if ($v === 'global') {
            $this->import_scope_id = null;
        }
    }

    public function importYear(ImportHolidaysFromInvertexto $action): void
    {
        $this->validate([
            'import_year' => 'required|integer|min:2000|max:2100',
            'import_state' => 'required|string|max:5',
        ]);
        if ($this->import_scope !== 'global') {
            $this->validate([
                'import_scope_id' => match ($this->import_scope) {
                    'location' => 'required|exists:locations,id',
                    'room' => 'required|exists:rooms,id',
                    'professional' => 'required|exists:partners,id',
                },
            ]);
        }

        try {
            $result = $action->handle(
                year: $this->import_year,
                state: $this->import_state,
                includeOptional: $this->import_include_optional,
                scopeType: $this->import_scope === 'global' ? null : $this->import_scope,
                scopeId: $this->import_scope === 'global' ? null : $this->import_scope_id,
            );

            $this->success("Importado {$result['year']} (UF {$result['state']}): +{$result['created']} novos, {$result['updated']} atualizados.");
        } catch (AlreadyImportedException $e) {
            $this->warning($e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $this->error('Falha ao importar feriados: '.$e->getMessage());
        }
    }

    public function getRowsProperty()
    {
        $rows = Holiday::query()
            ->with('subject')
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);

        // Mary já renderiza os acessores do model
        return $rows;
    }

    public function save(): void
    {
        $this->validate();

        // validação dependente do escopo
        $rules = [
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'is_recurring' => 'boolean',
            'active' => 'boolean',
        ];
        if ($this->scope !== 'global') {
            $rules['scope_id'] = match ($this->scope) {
                'location' => 'required|exists:locations,id',
                'room' => 'required|exists:rooms,id',
                'professional' => 'required|exists:partners,id',
            };
        }
        $this->validate($rules);

        Holiday::create([
            'name' => trim($this->name),
            'date' => Carbon::parse($this->date),
            'is_recurring' => $this->is_recurring,
            'scope' => $this->scope === 'global' ? null : $this->scope,
            'scope_id' => $this->scope === 'global' ? null : $this->scope_id,
            'active' => $this->active,
        ]);

        $this->reset(['name', 'date', 'is_recurring', 'scope', 'scope_id', 'active']);
        $this->is_recurring = true;
        $this->scope = 'global';
        $this->active = true;

        $this->success('Feriado adicionado.');
    }

    public function render()
    {
        return view('livewire.admin.holidays')->title('Feriados');
    }
}
