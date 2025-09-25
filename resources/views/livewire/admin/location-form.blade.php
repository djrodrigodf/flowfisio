<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">{{ $model ? 'Editar Unidade' : 'Nova Unidade' }}</h1>
        <x-button label="Voltar" icon="o-arrow-uturn-left" link="{{ route('admin.locations.index') }}" />
    </div>

    <x-card>
        <x-errors class="mb-3" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Nome*" wire:model.defer="name" />
            <x-input label="Endereço" wire:model.defer="address" />

            <x-input label="Cidade" wire:model.defer="city" />
            {{-- UF: pode ser input 2 chars ou select com todas as UFs --}}
            <x-select label="UF"
                      :options="[['id'=>'AC','name'=>'AC'],['id'=>'AL','name'=>'AL'],['id'=>'AP','name'=>'AP'],['id'=>'AM','name'=>'AM'],['id'=>'BA','name'=>'BA'],['id'=>'CE','name'=>'CE'],['id'=>'DF','name'=>'DF'],['id'=>'ES','name'=>'ES'],['id'=>'GO','name'=>'GO'],['id'=>'MA','name'=>'MA'],['id'=>'MT','name'=>'MT'],['id'=>'MS','name'=>'MS'],['id'=>'MG','name'=>'MG'],['id'=>'PA','name'=>'PA'],['id'=>'PB','name'=>'PB'],['id'=>'PR','name'=>'PR'],['id'=>'PE','name'=>'PE'],['id'=>'PI','name'=>'PI'],['id'=>'RJ','name'=>'RJ'],['id'=>'RN','name'=>'RN'],['id'=>'RS','name'=>'RS'],['id'=>'RO','name'=>'RO'],['id'=>'RR','name'=>'RR'],['id'=>'SC','name'=>'SC'],['id'=>'SP','name'=>'SP'],['id'=>'SE','name'=>'SE'],['id'=>'TO','name'=>'TO']]"
                      option-value="id" option-label="name"
                      placeholder="Selecione"
                      wire:model.defer="state" />

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
