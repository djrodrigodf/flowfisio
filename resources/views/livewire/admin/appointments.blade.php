<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Atendimentos</h1>
        <x-button label="Calendário" icon="o-calendar" link="{{ route('admin.appointments.calendar') }}" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <x-input
            label="Buscar"
            wire:model.live="search"
            placeholder="Paciente, Profissional, Tratamento..."
            class="md:col-span-2"
            clearable
        />
        <x-input type="date" label="Início" wire:model.live="dateStart" />
        <x-input type="date" label="Fim" wire:model.live="dateEnd" />
        <x-choices-offline label="Status" wire:model.live="status" :options="$statusOptions" single clearable />
    </div>

    <x-table
        :headers="$this->headers"
        :rows="$this->rows"
        with-pagination
        per-page="perPage"
        :per-page-values="[10,25,50]"
        :sort-by="$sortBy"
        :link="route('admin.appointments.show', ['appointment' => '[id]'])"
        show-empty-text
    />
</div>
