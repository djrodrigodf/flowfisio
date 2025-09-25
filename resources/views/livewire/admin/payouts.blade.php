<div class="space-y-4">
    <h1 class="text-xl font-bold">Repasses</h1>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <x-datetime label="Mês" wire:model.live="month" type="month" />
        <x-choices-offline label="Status do Repasse" wire:model.live="status" :options="$statusOptions" single clearable />
        <x-select label="Por pág." wire:model.live="perPage"
                  :options="[['id'=>10,'name'=>10],['id'=>25,'name'=>25],['id'=>50,'name'=>50]]"
                  option-value="id" option-label="name" />
    </div>


    @php
    $rows    = $this->rows;
    $headers = $this->headers;
    @endphp

    <x-table
        :headers="$headers"
        :rows="$rows"
        with-pagination
        per-page="perPage"
        :per-page-values="[10,25,50]"
        :sort-by="$sortBy"
        show-empty-text
    >
        {{-- Coluna Repasse: usa payoutItem->payout_value; fallback para snapshot do appointment --}}
        @scope('cell_repasse', $row)
        @php
            $valor = $row->payoutItem->payout_value ?? $row->repasse_value ?? 0;
        @endphp
        R$ {{ number_format((float)$valor, 2, ',', '.') }}
        @endscope

        @scope('cell_price', $row)
        @php
            $valorprice = $row->price ?? 0;
        @endphp
        R$ {{ number_format((float)$valorprice, 2, ',', '.') }}
        @endscope

        {{-- (Opcional) Ações: ver atendimento e ver repasse --}}
        @scope('actions', $row)
        <div class="flex gap-2">
            <x-button size="sm" icon="o-eye" class="btn-ghost"
                      tooltip="Ver Atendimento"
                      link="{{ route('admin.appointments.show', $row->id) }}" />
        </div>
        @endscope
    </x-table>
</div>
