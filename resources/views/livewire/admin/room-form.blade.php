<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">{{ $model ? 'Editar Sala' : 'Nova Sala' }}</h1>
        <x-button label="Voltar" icon="o-arrow-uturn-left" link="{{ route('admin.rooms.index') }}" />
    </div>

    <x-card>
        <x-errors class="mb-3" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Nome*" wire:model.defer="name" />

            <x-choices-offline label="Unidade*" wire:model.defer="location_id"
                               :options="$locOptions" single clearable />

            <x-input type="number" min="1" max="1000" label="Capacidade"
                     wire:model.defer="capacity"
                     hint="Pessoas simultâneas permitidas na sala (padrão: 1)" />

            <div class="md:col-span-2">
                <x-radio label="Status" wire:model="active"
                         :options="[['id'=>1,'name'=>'Ativo'],['id'=>0,'name'=>'Inativo']]"
                         option-value="id" option-label="name" inline />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Salvar" class="btn-primary" wire:click="save" spinner />
        </x-slot:actions>
    </x-card>
</div>
