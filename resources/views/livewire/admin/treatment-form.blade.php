<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">{{ $model ? 'Editar Tratamento' : 'Novo Tratamento' }}</h1>
        <x-button label="Voltar" icon="o-arrow-uturn-left" link="{{ route('admin.treatments.index') }}" />
    </div>

    <x-card>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Nome*" wire:model.defer="name" placeholder="Ex.: Sessão Ortopédica" />

            <x-choices-offline label="Especialidade" wire:model.defer="specialty_id" :options="$specialtyOptions" single clearable />

            <x-choices-offline label="Tipo" wire:model.defer="treatment_type_id" :options="$typeOptions" single clearable />

            <x-input label="Valor base" wire:model.defer="valor_base" prefix="R$" locale="pt-BR" money />

            <div class="md:col-span-2">
                <x-radio label="Status" wire:model="active"
                         :options="[['id'=>1,'name'=>'Ativo'],['id'=>0,'name'=>'Inativo']]"
                         option-value="id" option-label="name" inline />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Salvar" icon="o-check" class="btn-primary" wire:click="save" spinner />
        </x-slot:actions>
    </x-card>
</div>
