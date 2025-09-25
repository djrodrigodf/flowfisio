<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Repasse #{{ $payout->id }}</h1>
        <x-button label="Voltar" icon="o-arrow-uturn-left" link="{{ route('admin.payouts.index') }}" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <x-card title="Resumo">
            <div class="space-y-1">
                <div>Profissional: <b>{{ $payout->professional?->name ?? '#'.$payout->professional_id }}</b></div>
                <div>Período: <b>{{ \Illuminate\Support\Carbon::parse($payout->period_start)->format('d/m') }} – {{ \Illuminate\Support\Carbon::parse($payout->period_end)->format('d/m/Y') }}</b></div>
                <div>Status: <x-badge :value="$payout->status" class="badge-soft" /></div>
            </div>
        </x-card>

        <x-card title="Totais">
            <div class="space-y-1">
                <div>Bruto: <b>R$ {{ number_format($payout->gross_total,2,',','.') }}</b></div>
                <div>Ajustes: <b>R$ {{ number_format($payout->adjustments_total,2,',','.') }}</b></div>
                <div>Líquido: <b>R$ {{ number_format($payout->net_total,2,',','.') }}</b></div>
            </div>
            <x-slot:actions>
                <x-button label="Aprovar" class="btn-primary btn-sm" />
                <x-button label="Marcar como Pago" class="btn-success btn-sm" />
            </x-slot:actions>
        </x-card>

        <x-card title="Anotações">
            <div class="min-h-[80px] text-base-content/70">—</div>
        </x-card>
    </div>

    <x-card title="Itens do Repasse">
        @if(empty($rows))
            <x-icon name="o-cube" label="Sem itens para exibir." />
        @else
            <x-table :headers="$headers" :rows="$rows" />
        @endif
    </x-card>
</div>
