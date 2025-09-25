<div class="space-y-4">
    <h1 class="text-xl font-bold">Relatórios Operacionais</h1>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <x-datetime label="Início" wire:model.live="start" />
        <x-datetime label="Fim" wire:model.live="end" />
        <x-choices-offline label="Status" wire:model.live="status" :options="$statusOptions" single clearable />
        <x-choices-offline label="Profissional" wire:model.live="professional_id" :options="$proOptions" single clearable />
        <div class="flex items-end">
            <x-button label="Exportar CSV" icon="o-arrow-down-tray"
                      :link="route('admin.reports.export.appointments', [
                'start' => $start,
                'end' => $end,
                'status' => $status,
                'professional_id' => $professional_id,
            ])" />
        </div>
    </div>

    <x-table :headers="$headers" :rows="$rows" show-empty-text />
</div>
