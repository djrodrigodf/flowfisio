{{-- resources/views/livewire/admin/holidays.blade.php --}}
<div class="space-y-4">
    <h1 class="text-xl font-bold">Feriados</h1>

    <x-card title="Importar feriados">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <x-input type="number" label="Ano" min="2000" max="2100" wire:model.defer="import_year" />
            <x-input label="UF" maxlength="5" wire:model.defer="import_state" />

            <x-toggle label="Incluir 'facultativo'" wire:model="import_include_optional" />

            <x-choices-offline label="Escopo" wire:model.live="import_scope"
                               :options="[
                ['id'=>'global','name'=>'Global'],
                ['id'=>'location','name'=>'Unidade'],
                ['id'=>'room','name'=>'Sala'],
                ['id'=>'professional','name'=>'Profissional'],
            ]"
                               single />

            @if($import_scope==='location')
                <x-choices-offline label="Unidade" wire:model.defer="import_scope_id" :options="$locOptions" single clearable />
            @elseif($import_scope==='room')
                <x-choices-offline label="Sala" wire:model.defer="import_scope_id" :options="$roomOptions" single clearable />
            @elseif($import_scope==='professional')
                <x-choices-offline label="Profissional" wire:model.defer="import_scope_id" :options="$proOptions" single clearable />
            @else
                <x-input label="Escopo" value="Global" readonly />
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Importar" class="btn-primary" wire:click="importYear" spinner />
        </x-slot:actions>
    </x-card>

    <x-card title="Novo feriado">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <x-input class="md:col-span-2" label="Nome*" wire:model.defer="name" placeholder="Ex.: Natal" />
            <x-datetime label="Data*" wire:model.defer="date" without-time />

            <x-toggle label="Recorrente (todo ano)" wire:model="is_recurring" />

            <x-choices-offline label="Escopo" wire:model.live="scope"
                               :options="[
                    ['id'=>'global','name'=>'Global'],
                    ['id'=>'location','name'=>'Unidade'],
                    ['id'=>'room','name'=>'Sala'],
                    ['id'=>'professional','name'=>'Profissional'],
                ]"
                               single />

            @if($scope==='location')
                <x-choices-offline label="Unidade" wire:model.defer="scope_id" :options="$locOptions" single clearable />
            @elseif($scope==='room')
                <x-choices-offline label="Sala" wire:model.defer="scope_id" :options="$roomOptions" single clearable />
            @elseif($scope==='professional')
                <x-choices-offline label="Profissional" wire:model.defer="scope_id" :options="$proOptions" single clearable />
            @else
                <x-input label="Escopo" value="Global" readonly />
            @endif
        </div>

        <x-slot:actions>
            <x-radio label="Status" wire:model="active"
                     :options="[['id'=>1,'name'=>'Ativo'],['id'=>0,'name'=>'Inativo']]"
                     option-value="id" option-label="name" inline />
            <x-button label="Adicionar" class="btn-primary" wire:click="save" spinner />
        </x-slot:actions>
    </x-card>

    @php($headers = $this->headers)
    @php($rows = $this->rows)

    <x-card title="Lista">
        <x-table :headers="$headers" :rows="$rows"
                 with-pagination per-page="perPage" :per-page-values="[10,25,50]"
                 :sort-by="$sortBy" show-empty-text>
            @scope('cell_active', $row)
            <x-badge :value="$row->active ? 'Ativo' : 'Inativo'"
                     class="{{ $row->active ? 'badge-success badge-soft' : 'badge-ghost' }}" />
            @endscope
        </x-table>
    </x-card>
</div>
