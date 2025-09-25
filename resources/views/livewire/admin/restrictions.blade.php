<div class="space-y-4">
    <h1 class="text-xl font-bold">Restrições</h1>

    <x-card title="Nova restrição">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <x-datetime label="Início" wire:model.defer="dateStart" />
            <x-datetime label="Fim" wire:model.defer="dateEnd" />

            <x-choices-offline label="Escopo" wire:model.live="scope"
                               :options="[
        ['id'=>'global','name'=>'Global'],
        ['id'=>'location','name'=>'Unidade'],
        ['id'=>'room','name'=>'Sala'],
        ['id'=>'professional','name'=>'Profissional'],
    ]"
                               single />

            @if($scope==='location')
                <x-choices-offline label="Unidade" wire:model.defer="scope_id" :options="$locOptions" single />
            @elseif($scope==='room')
                <x-choices-offline label="Sala" wire:model.defer="scope_id" :options="$roomOptions" single />
            @elseif($scope==='professional')
                <x-choices-offline label="Profissional" wire:model.defer="scope_id" :options="$proOptions" single />
            @else
                <x-input label="Escopo" value="Global" readonly />
            @endif

            <x-input class="md:col-span-2" label="Motivo" wire:model.defer="reason" placeholder="Ex.: Férias, manutenção..." />
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
        <x-table
            :headers="$headers"
            :rows="$rows"
            with-pagination
            per-page="perPage"
            :per-page-values="[10,25,50]"
            :sort-by="$sortBy"
            show-empty-text
        >
            @scope('cell_active', $row)
            <x-badge :value="$row->active ? 'Ativo' : 'Inativo'" class="{{ $row->active ? 'badge-success badge-soft' : 'badge-ghost' }}" />
            @endscope
        </x-table>
    </x-card>
</div>
