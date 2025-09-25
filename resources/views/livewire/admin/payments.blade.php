<div class="space-y-4">
    <h1 class="text-xl font-bold">Pagamentos</h1>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <x-datetime label="Início" wire:model.live="dateStart" />
        <x-datetime label="Fim" wire:model.live="dateEnd" />
        <x-choices-offline label="Status" wire:model.live="status" :options="$statusOptions" single clearable />
        <x-choices-offline label="Método" wire:model.live="method" :options="$methodOptions" single clearable />
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
    />
</div>
