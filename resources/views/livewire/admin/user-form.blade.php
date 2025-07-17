<div class="">
    <x-card>
        <x-slot name="title">
            {{ $user ? 'Editar Usuário' : 'Novo Usuário' }}
        </x-slot>

        <div class="space-y-4">
            <x-input label="Nome" wire:model.defer="name" required />
            <x-input label="E-mail" wire:model.defer="email" required type="email" />
            <x-input label="Senha" wire:model.defer="password" type="password" hint="{{ $user ? 'Preencha apenas se for trocar a senha' : '' }}" />


            <x-choices-offline
                label="Funções"
                wire:model.live="roles"
                :options="$allRoles"
                placeholder="Selecione as funções..."
                clearable
                searchable />

        </div>

        <div class="flex justify-end space-x-2 mt-4">
            <x-button wire:click="save" spinner="save" class="btn-primary">Salvar</x-button>
            <a href="{{ route('admin.users.index') }}" class="btn">Cancelar</a>
        </div>
    </x-card>
</div>
