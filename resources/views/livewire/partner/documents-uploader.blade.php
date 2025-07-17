<div class="space-y-6">

    {{-- Formulário de envio --}}
    <form class="grid md:grid-cols-3 gap-4">
        <x-select
            label="Categoria do Documento"
            wire:model="document_category_id"
            :options="$categories"
            placeholder="Selecione..."
        />

        <x-input
            label="Descrição (opcional)"
            wire:model="description"
            placeholder="Ex: frente do RG"
        />


        <x-file
            label="Arquivo"
            wire:model="file"
        />


    </form>

    <div class="col-span-3 flex justify-end">
        <x-button wire:click="sendFile" icon="fas.upload" label="Enviar Documento" class="btn-primary" />
    </div>
    {{-- Título da seção --}}
    <h3 class="font-semibold text-lg my-4">Documentos Enviados</h3>

    <x-select
        label="Filtrar por categoria"
        wire:model.live="filterCategory"
        :options="$categories"
        option-label="name"
        option-value="id"
        placeholder="Todas as categorias"
    />
    {{-- Tabela --}}
    <x-table
        :headers="$headers"
        :rows="$documents"
    >
        @scope('cell_name', $doc)
        {{ $doc->name }}
        @endscope

        @scope('cell_category', $doc)
        {{ $doc->getCustomProperty('document_name') ?? '-' }}
        @endscope

        @scope('cell_description', $doc)
        {{ $doc->getCustomProperty('description') ?? '-' }}
        @endscope

        @scope('cell_actions', $doc)

        <div class="flex justify-end gap-2">
            <x-button
                icon="fas.download"
                class="btn-soft btn-sm"
                link="{{ $doc->getUrl() }}"
                target="_blank"
                tooltip-left="Download"
            />
            <x-button
                icon="fas.trash"
                class="btn-error btn-sm"
                external="true"
                wire:click="removeDocument({{ $doc->id }})"
                tooltip-left="Excluir"
            />
        </div>
        @endscope
    </x-table>

</div>
