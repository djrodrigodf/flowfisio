<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Tratamentos</h1>
        <x-button label="Novo" icon="o-plus" link="{{ route('admin.treatments.create') }}" class="btn-primary btn-sm" />
    </div>

    <div class="flex flex-wrap items-end gap-3">
        <x-input label="Buscar" wire:model.live="search" placeholder="Nome" class="w-64" clearable />
        <x-select label="Por pág." wire:model.live="perPage" :options="[['id'=>10,'name'=>10],['id'=>25,'name'=>25],['id'=>50,'name'=>50]]" option-value="id" option-label="name" />
    </div>

    @php($headers = $this->headers)
    @php($rows = $this->rows)

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

        @scope('actions', $row)
        <x-button icon="o-pencil-square" class="btn-xs"
                  link="{{ route('admin.treatments.create', ['id' => $row->id]) }}" />
        @endscope
    </x-table>
</div>
