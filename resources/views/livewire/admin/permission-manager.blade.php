<div class="space-y-6">
    <x-card title="Gestão de Permissões" shadow separator>

        <x-slot:menu>
            <x-button icon="o-plus" label="Novo" class="btn-accent" @click="$wire.addModal = true" />
        </x-slot:menu>

        <div class="space-y-4">
            <x-select label="Perfil" wire:model.live="selectedRole" :options="$roles" />

            <div class="flex justify-end gap-2 mb-4">
                <x-button wire:click="selectAll" size="sm" class="btn-secondary">Selecionar Todas</x-button>
                <x-button wire:click="deselectAll" size="sm" class="btn-outline">Deselecionar Todas</x-button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($allPermissions as $permission)
                    <x-checkbox wire:model.live="permissions.{{ $permission }}" label="{{ $permission }}" />
                @endforeach
            </div>

        </div>

        <div class="flex justify-end mt-4">
            <x-button wire:click="save" class="btn-primary">Salvar</x-button>
        </div>
    </x-card>

    <x-modal wire:model="addModal" title="Adicionar novo Grupo de permissão" class="backdrop-blur">

        <x-form >
            <x-input label="Grupo de Permissão" wire:model="permissao" placeholder="Escrever o nome do grupo de permissão" icon="fas.users" />
        </x-form>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.addModal = false" />
            <x-button label="Adicionar" wire:click="novaPermissao" class="btn-primary" />
        </x-slot:actions>
    </x-modal>
</div>
