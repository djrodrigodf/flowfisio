<div class="p-4 space-y-6">
    {{-- Lista de Tabelas --}}
    <x-card>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Tabelas</h2>
            {{-- cria rascunho automaticamente --}}
            <x-button class="btn-primary" wire:click="newTableDraft" icon="o-plus">Nova Tabela</x-button>
        </div>

        <x-table :headers="$tableHeaders" :rows="$tableRows" row-actions>
            @scope('actions', $row)

                <div class="flex gap-4">
                    <x-button size="sm" wire:click="editTable({{$row['id']}})">Editar</x-button>
                    <x-button size="sm" class="btn-soft" wire:click="newItemFrom({{$row['id']}})">Adicionar item</x-button>
                </div>
            @endscope
        </x-table>

        @if(empty($tableRows))
            <div class="mt-4 text-sm text-gray-500">
                Nenhuma tabela cadastrada ainda. Clique em <b>Nova Tabela</b> para começar.
            </div>
        @endif
    </x-card>

    {{-- Editor de Cabeçalho --}}
    <x-card>
        <h3 class="font-semibold mb-4">
            {{ $editingTableId ? 'Editar Tabela #'.$editingTableId : 'Nova Tabela' }}
        </h3>

        {{-- mostra validações do Livewire --}}
        <x-errors class="mb-3" />

        <div class="grid md:grid-cols-3 gap-4">
            <x-input label="Nome" wire:model.defer="name" />
            <x-select placeholder="Selecione" label="Convênio" :options="$insOptions" option-label="name" option-value="id" wire:model.defer="insurance_id" />
            <x-select placeholder="Selecione" label="Unidade" :options="$locOptions" option-label="name" option-value="id" wire:model.defer="location_id" />
            <x-select placeholder="Selecione" label="Profissional" :options="$partnerOptions" option-label="name" option-value="id" wire:model.defer="partner_id" />
            <x-input type="date" label="Vigência Início" wire:model.defer="effective_from" />
            <x-input type="date" label="Vigência Fim" wire:model.defer="effective_to" />
            <x-select placeholder="Selecione" label="Status" :options="[['id'=>'draft','name'=>'Rascunho'],['id'=>'published','name'=>'Publicado'],['id'=>'archived','name'=>'Arquivado']]"
                      option-label="name" option-value="id" wire:model.defer="status" />
            <x-input type="number" label="Prioridade" min="0" max="255" wire:model.defer="priority" />
        </div>

        <div class="flex flex-wrap gap-2 mt-4">
            <x-button class="btn-primary" wire:click="saveTable" spinner>Salvar</x-button>
            <x-button class="btn-soft" wire:click="saveTableAndAddItem" spinner
                      :disabled="!$name">Salvar & adicionar item</x-button>
            <x-button class="btn-dash" wire:click="publish" :disabled="!$editingTableId || $status!=='draft'">Publicar</x-button>
            <x-button class="btn-dash" wire:click="archive" :disabled="!$editingTableId">Arquivar</x-button>
        </div>

        @if(!$editingTableId)
            <div class="mt-3 text-xs text-gray-500">
                Dica: use <b>Salvar & adicionar item</b> para já incluir os preços/repasse.
            </div>
        @endif
    </x-card>

    {{-- Itens --}}
    <x-card x-data id="items-card">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold">Itens</h3>

            {{-- tooltip quando desabilitado --}}
            <div x-data x-tooltip.raw="{{ !$editingTableId ? 'Salve a tabela para habilitar' : '' }}">
                <x-button class="btn-primary" wire:click="resetItemEditor" :disabled="!$editingTableId">Novo Item</x-button>
            </div>
        </div>

        @if(!$editingTableId)
            <div class="mt-4 p-3 rounded bg-gray-50 text-sm text-gray-600">
                Salve a Tabela (ou crie um rascunho) para habilitar a inclusão de itens.
            </div>
        @else
            <div class="grid md:grid-cols-5 gap-4 mt-4">
                <x-select placeholder="Selecione" label="Tratamento" :options="$treatOptions" option-label="name" option-value="id" wire:model.defer="item_treatment_id" />
                <x-input label="Preço" wire:model.defer="item_price" />
                <x-select placeholder="Selecione" label="Tipo Repasse" :options="[['id'=>'percent','name'=>'%'],['id'=>'fixed','name'=>'Fixo']]"
                          option-label="name" option-value="id" wire:model.defer="item_repasse_type" />
                <x-input label="Valor Repasse" wire:model.defer="item_repasse_value" />
                <x-input type="number" label="Duração (min)" wire:model.defer="item_duration_min" />
                <x-input class="md:col-span-5" label="Observações" wire:model.defer="item_notes" />
            </div>
            <div class="flex gap-2 mt-4">
                <x-button class="btn-primary" wire:click="saveItem" :disabled="!$editingTableId" spinner>Salvar Item</x-button>
            </div>

            <x-table class="mt-6" :headers="$itemHeaders" :rows="$itemRows" row-actions>
                @scope('actions', $row)

                    <div class="flex gap-4">
                        <x-button size="sm" wire:click="editItem({{$row['id']}})">Editar</x-button>
                        <x-button size="sm" class="btn-dash" wire:click="removeItem({{$row['id']}})">Remover</x-button>
                    </div>
                @endscope
            </x-table>

            @if(empty($itemRows))
                <div class="mt-3 text-sm text-gray-500">Nenhum item nesta tabela ainda. Adicione o primeiro acima.</div>
            @endif
        @endif
    </x-card>
</div>

{{-- scroll suave após salvar & add item --}}
<script>
    window.addEventListener('scroll-to-items', () => {
        document.getElementById('items-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
</script>
