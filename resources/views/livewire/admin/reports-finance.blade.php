{{-- resources/views/livewire/admin/reports-finance.blade.php --}}

<div class="space-y-4">
    <h1 class="text-xl font-bold">Relatórios Financeiros</h1>

    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
        <x-datetime label="Início" wire:model.live="start" />
        <x-datetime label="Fim" wire:model.live="end" />
        <x-choices-offline label="Status" wire:model.live="status" :options="$statusOptions" single clearable />
        <x-choices-offline label="Método" wire:model.live="method" :options="$methodOptions" single clearable />
        {{-- antes professional_id/proOptions --}}
        <x-choices-offline label="Profissional" wire:model.live="partner_id" :options="$partnerOptions" single clearable />
        <x-choices-offline label="Convênio" wire:model.live="insurance_id" :options="$insOptions" single clearable />
    </div>

    <div class="flex justify-end">
        <x-button label="Exportar CSV" icon="o-arrow-down-tray"
                  :link="route('admin.reports.export.payments', [
                'start' => $start,
                'end' => $end,
                'status' => $status,
                'method' => $method,
                'partner_id' => $partner_id, {{-- antes: professional_id --}}
                'insurance_id' => $insurance_id,
            ])" />
    </div>

    <x-table :headers="$headers" :rows="$rows" show-empty-text />
</div>
