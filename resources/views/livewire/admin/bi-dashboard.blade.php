<div class="space-y-4">
    <div class="flex items-end flex-wrap gap-3">
        <h1 class="text-xl font-bold mr-auto">Dashboard BI</h1>
        <x-datetime label="Início" wire:model.live="start" />
        <x-datetime label="Fim" wire:model.live="end" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-card title="Atendidos">
            <div class="text-3xl font-bold">{{ $summary['counts']['attended'] ?? 0 }}</div>
        </x-card>

        <x-card title="Faltas">
            <div class="text-3xl font-bold">{{ $summary['counts']['no_show'] ?? 0 }}</div>
        </x-card>

        <x-card title="Receita (aplicada)">
            <div class="text-3xl font-bold">R$ {{ number_format($summary['money']['revenue_paid'] ?? 0,2,',','.') }}</div>
        </x-card>

        <x-card title="Em aberto">
            <div class="text-3xl font-bold">R$ {{ number_format($summary['money']['outstanding'] ?? 0,2,',','.') }}</div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-card title="Receita por dia">
            <canvas id="revChart" height="140"></canvas>
        </x-card>

        <x-card title="Presença por dia">
            <canvas id="attChart" height="140"></canvas>
        </x-card>
    </div>
</div>
