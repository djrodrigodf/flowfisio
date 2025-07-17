<div>
    <x-card title="Categorias de Documentos" icon="fas.folder-open" class="mb-6">
        <x-button icon="fas.plus" label="Nova Categoria" class="btn-primary mb-4" wire:click="create" />

        <x-table :headers="[['key' => 'name', 'label' => 'Nome'], ['key' => 'actions', 'label' => 'Ações']]" :rows="$categories" >

            @scope('cell_name', $row)
            {{ $row->name }}
            @endscope

            @scope('cell_actions', $row)
            <div class="flex gap-2">
                <x-button icon="fas.trash" class="btn-xs btn-error" wire:click="delete({{ $row->id }})" />
            </div>
            @endscope

        </x-table>
    </x-card>

    {{-- Modal de Cadastro/Edição --}}
    <x-modal wire:model="showModal" title="{{ $categoryId ? 'Editar' : 'Nova' }} Categoria">
        <x-input wire:model="name" label="Nome da Categoria" placeholder="Ex: Certificados, Documentos Pessoais..." />

        <x-slot:actions>
            <x-button label="Salvar" class="btn-primary" wire:click="save" />
            <x-button label="Cancelar" class="btn-ghost" wire:click="$set('showModal', false)" />
        </x-slot:actions>
    </x-modal>
</div>
