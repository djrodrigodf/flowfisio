<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Pacientes</h1>
        <x-button label="Novo" icon="o-plus" link="{{ route('admin.patients.create') }}" class="btn-primary btn-sm" />
    </div>

    <div class="flex flex-wrap items-end gap-3">
        <x-input label="Buscar" wire:model.live="search" placeholder="Nome ou telefone" class="w-64" clearable />
        <x-select label="Por pág." wire:model.live="perPage" :options="[['id'=>10,'name'=>10],['id'=>25,'name'=>25],['id'=>50,'name'=>50]]" option-value="id" option-label="name" class="w-32" />
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
        :link="route('admin.patients.show', ['patient' => '[id]'])"
        show-empty-text
    >
        @scope('cell_active', $row)
        <x-badge :value="$row->active ? 'Ativo' : 'Inativo'" class="{{ $row->active ? 'badge-success badge-soft' : 'badge-ghost' }}" />
        @endscope

        @scope('actions', $row)
        <x-button icon="o-eye" class="btn-xs" link="{{ route('admin.patients.show',$row->id) }}" />
        <x-button icon="o-eye" class="btn-xs" link="{{ route('admin.patients.edit',$row->id) }}" />
        @endscope
    </x-table>
</div>
