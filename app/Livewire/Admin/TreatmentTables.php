<?php

namespace App\Livewire\Admin;

use App\Domain\Pricing\Actions\CreateOrUpdateTreatmentTable;
use App\Domain\Pricing\Actions\PublishTreatmentTable;
use App\Domain\Pricing\Actions\UpsertTreatmentTableItem;
use App\Models\Insurance;
use App\Models\Location;
use App\Models\Partner;
use App\Models\Treatment;
use App\Models\TreatmentTable;
use App\Models\TreatmentTableItem;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class TreatmentTables extends Component
{
    use Toast;

    // Listagem de tabelas
    // Editor de cabeçalho (tabela)
    public ?int $editingTableId = null;

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('nullable|exists:insurances,id')]
    public ?int $insurance_id = null;

    #[Validate('nullable|exists:locations,id')]
    public ?int $location_id = null;

    #[Validate('nullable|exists:partners,id')]
    public ?int $partner_id = null;

    #[Validate('nullable|date')]
    public ?string $effective_from = null;

    #[Validate('nullable|date|after_or_equal:effective_from')]
    public ?string $effective_to = null;

    #[Validate('required|in:draft,published,archived')]
    public string $status = 'draft';

    #[Validate('nullable|integer|min:0|max:255')]
    public ?int $priority = 0;

    public array $tableHeaders = [
        ['key' => 'name', 'label' => 'Tabela'],
        ['key' => 'scope', 'label' => 'Escopo'],
        ['key' => 'vigencia', 'label' => 'Vigência'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'priority', 'label' => 'Prioridade'],
    ];

    public array $tableRows = [];

    // Editor de itens (linhas)
    public array $itemHeaders = [
        ['key' => 'treatment', 'label' => 'Tratamento'],
        ['key' => 'price', 'label' => 'Preço', 'format' => ['currency', '2,.', 'R$ ']],
        ['key' => 'repasse', 'label' => 'Repasse'],
        ['key' => 'duration', 'label' => 'Duração (min)'],
    ];

    public array $itemRows = [];

    public ?int $editingItemId = null;

    #[Validate('required|exists:treatments,id')]
    public ?int $item_treatment_id = null;

    #[Validate('required|numeric|min:0')]
    public ?string $item_price = null;

    #[Validate('required|in:percent,fixed')]
    public string $item_repasse_type = 'percent';

    #[Validate('required|numeric|min:0')]
    public ?string $item_repasse_value = null;

    #[Validate('nullable|integer|min:1|max:600')]
    public ?int $item_duration_min = null;

    public ?string $item_notes = null;

    // Options
    public array $treatOptions = [];

    public array $insOptions = [];

    public array $locOptions = [];

    public array $partnerOptions = [];

    public function mount(): void
    {
        $this->treatOptions = Treatment::select('id', 'name')->orderBy('name')->get()->toArray();
        if (class_exists(Insurance::class)) {
            $this->insOptions = Insurance::select('id', 'name')->orderBy('name')->get()->toArray();
        }
        if (class_exists(Location::class)) {
            $this->locOptions = Location::select('id', 'name')->orderBy('name')->get()->toArray();
        }

        if (class_exists(\App\Models\Partner::class)) {
            $this->partnerOptions = Partner::whereHas('role', function ($q) {
                $q->where('is_specialty', 1);   // aqui filtra pela role
            })
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->toArray();
        }

        $this->refreshTables();
    }

    public function refreshTables(): void
    {
        $this->tableRows = TreatmentTable::query()
            ->latest('id')
            ->get()
            ->map(function (TreatmentTable $t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'scope' => trim(collect([
                        $t->insurance?->name,
                        $t->location?->name,
                        $t->partner?->name,
                    ])->filter()->implode(' • ')) ?: 'Geral',
                    'vigencia' => ($t->effective_from?->format('d/m/Y') ?: '—').' — '.($t->effective_to?->format('d/m/Y') ?: '—'),
                    'status' => $t->status,
                    'priority' => $t->priority,
                ];
            })->toArray();
    }

    public function newTable(): void
    {
        $this->resetTableEditor();
    }

    public function editTable(int $id): void
    {
        $t = TreatmentTable::findOrFail($id);
        $this->editingTableId = $t->id;
        $this->name = $t->name;
        $this->insurance_id = $t->insurance_id;
        $this->location_id = $t->location_id;
        $this->partner_id = $t->partner_id;
        $this->status = $t->status;
        $this->priority = $t->priority;
        $this->effective_from = optional($t->effective_from)->toDateString();
        $this->effective_to = optional($t->effective_to)->toDateString();
        $this->loadItems($t);
    }

    // 1) Criar rascunho e já abrir edição
    public function newTableDraft(CreateOrUpdateTreatmentTable $action): void
    {
        $table = $action->handle([
            'name' => 'Nova Tabela',
            'status' => 'draft',
            'priority' => 0,
            'insurance_id' => null,
            'location_id' => null,
            'partner_id' => null,
        ]);
        $this->editTable($table->id);
        $this->info('Rascunho criado. Preencha os campos e adicione itens.');
    }

    // 2) Salvar e ir para a seção de itens
    public function saveTableAndAddItem(CreateOrUpdateTreatmentTable $action): void
    {
        $this->saveTable($action);
        // prepara o formulário de item vazio e rola a página para a seção
        $this->resetItemEditor();
        $this->dispatch('scroll-to-items'); // evento p/ a view focar nos itens
    }

    public function newItemFrom(int $id): void
    {
        $this->editTable($id);     // carrega a tabela e seta $editingTableId
        $this->resetItemEditor();  // limpa o form de item
        $this->dispatch('scroll-to-items');
    }

    public function saveTable(CreateOrUpdateTreatmentTable $action): void
    {
        $this->validate([
            'name' => 'required|string|max:120',
            'status' => 'required|in:draft,published,archived',
        ]); // os demais já tem attributes

        $t = $this->editingTableId
            ? TreatmentTable::findOrFail($this->editingTableId)
            : null;

        $saved = $action->handle([
            'name' => $this->name,
            'insurance_id' => $this->insurance_id,
            'location_id' => $this->location_id,
            'partner_id' => $this->partner_id,
            'status' => $this->status,
            'effective_from' => $this->effective_from ? Carbon::parse($this->effective_from) : null,
            'effective_to' => $this->effective_to ? Carbon::parse($this->effective_to) : null,
            'priority' => $this->priority ?? 0,
        ], $t);

        $this->success('Tabela salva.');
        $this->editingTableId = $saved->id;
        $this->refreshTables();
        $this->loadItems($saved);
    }

    public function publish(PublishTreatmentTable $action): void
    {
        $t = TreatmentTable::findOrFail($this->editingTableId);
        $action->handle($t);
        $this->success('Tabela publicada.');
        $this->refreshTables();
    }

    public function archive(): void
    {
        $t = TreatmentTable::findOrFail($this->editingTableId);
        $t->status = 'archived';
        $t->save();
        $this->success('Tabela arquivada.');
        $this->refreshTables();
    }

    public function loadItems(TreatmentTable $t): void
    {
        $this->itemRows = $t->items
            ->map(function (TreatmentTableItem $i) {
                // Repasse como string amigável (pode ser % ou R$)
                $repasse = $i->repasse_type === 'percent'
                    ? number_format((float) $i->repasse_value, 0).' %'
                    : 'R$ '.number_format((float) $i->repasse_value, 2, ',', '.');

                return [
                    'id' => $i->id,
                    'treatment' => $i->treatment?->name,
                    // >>> AQUI: mande número puro, sem prefixo 'R$' nem number_format
                    'price' => (float) $i->price,
                    'repasse' => $repasse,
                    'duration' => $i->duration_min ?: '—',
                ];
            })->toArray();
    }

    public function editItem(int $id): void
    {
        $i = TreatmentTableItem::findOrFail($id);
        $this->editingItemId = $i->id;
        $this->item_treatment_id = $i->treatment_id;
        $this->item_price = (string) $i->price;
        $this->item_repasse_type = $i->repasse_type;
        $this->item_repasse_value = (string) $i->repasse_value;
        $this->item_duration_min = $i->duration_min;
        $this->item_notes = $i->notes;
    }

    public function removeItem(int $id): void
    {
        $i = TreatmentTableItem::findOrFail($id);
        $i->delete();
        $this->success('Item removido.');
        $this->editTable($this->editingTableId);
    }

    public function saveItem(UpsertTreatmentTableItem $action): void
    {
        $this->validate([
            'item_treatment_id' => 'required|exists:treatments,id',
            'item_price' => 'required|numeric|min:0',
            'item_repasse_type' => 'required|in:percent,fixed',
            'item_repasse_value' => 'required|numeric|min:0',
            'item_duration_min' => 'nullable|integer|min:1|max:600',
        ]);

        $t = TreatmentTable::findOrFail($this->editingTableId);
        $item = $this->editingItemId ? TreatmentTableItem::findOrFail($this->editingItemId) : null;

        $saved = $action->handle($t, [
            'treatment_id' => $this->item_treatment_id,
            'price' => $this->item_price,
            'repasse_type' => $this->item_repasse_type,
            'repasse_value' => $this->item_repasse_value,
            'duration_min' => $this->item_duration_min,
            'notes' => $this->item_notes,
        ], $item);

        $this->success('Item salvo.');
        $this->editingItemId = $saved->id;
        $this->loadItems($t);
        $this->resetItemEditor();
    }

    public function resetTableEditor(): void
    {
        $this->editingTableId = null;
        $this->name = '';
        $this->insurance_id = null;
        $this->location_id = null;
        $this->partner_id = null;
        $this->effective_from = null;
        $this->effective_to = null;
        $this->status = 'draft';
        $this->priority = 0;
        $this->itemRows = [];
        $this->editingItemId = null;
        $this->resetItemEditor();
    }

    public function resetItemEditor(): void
    {
        $this->editingItemId = null;
        $this->item_treatment_id = null;
        $this->item_price = null;
        $this->item_repasse_type = 'percent';
        $this->item_repasse_value = null;
        $this->item_duration_min = null;
        $this->item_notes = null;
    }

    public function render()
    {
        return view('livewire.admin.treatment-tables')->title('Tabelas de Preço/Repasse');
    }
}
