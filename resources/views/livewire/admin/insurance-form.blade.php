<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">{{ $model ? 'Editar Convênio' : 'Novo Convênio' }}</h1>
        <x-button label="Voltar" icon="o-arrow-uturn-left" link="{{ route('admin.insurances.index') }}" />
    </div>

    <x-card>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Nome*" wire:model.defer="name" />
            <x-input label="Código" wire:model.defer="code" />
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
