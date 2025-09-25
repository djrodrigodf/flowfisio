<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Agendamentos</h1>
        <x-button class="btn-primary" icon="fas.plus" label="Novo" link="{{ route('admin.appointments.new') }}" />
    </div>

    {{-- Filtros --}}
    <x-card title="Filtros">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <x-input type="date" label="De" wire:model.live="date_from" />
            <x-input type="date" label="Até" wire:model.live="date_to" />

            <x-choices-offline label="Profissional" :options="$partnerOptions" wire:model.live="partner_id" single clearable />
            <x-choices-offline label="Unidade" :options="$locationOptions" wire:model.live="location_id" single clearable />
            <x-choices-offline label="Sala" :options="$roomOptions" wire:model.live="room_id" single clearable />
            <x-choices-offline label="Convênio" :options="$insuranceOptions" wire:model.live="insurance_id" single clearable />

            <x-choices-offline label="Tratamento" :options="$treatmentOptions" wire:model.live="treatment_id" single clearable />
            <x-select
                label="Status"
                :options="[
                    ['id'=>'scheduled','name'=>'Agendado'],
                    ['id'=>'attended','name'=>'Atendido'],
                    ['id'=>'no_show','name'=>'Faltou'],
                    ['id'=>'rescheduled','name'=>'Reagendado'],
                    ['id'=>'canceled','name'=>'Cancelado'],
                ]"
                option-value="id" option-label="name"
                placeholder="Todos"
                wire:model.live="status"
            />
            <x-input class="md:col-span-2" label="Buscar Paciente" placeholder="Nome do paciente..." wire:model.live.debounce.400ms="searchPatient" />
        </div>

        <x-slot:actions>
            <x-button icon="fas.broom" label="Limpar" wire:click="clearFilters" />
        </x-slot:actions>
    </x-card>

    <x-card title="Lista">
        <x-table
            :headers="$headers"
            :rows="$this->rows"
            with-pagination
            per-page="perPage"
            :per-page-values="[10,25,50]"
            :sort-by="$sortBy"
            show-empty-text
        >
            {{-- Data/Hora --}}
            @scope('cell_start_at', $row)
            {{ optional($row->start_at)->format('d/m/Y H:i') }} — {{ optional($row->end_at)->format('H:i') }}
            @endscope

            {{-- Local (Unidade • Sala) --}}
            @scope('cell_place', $row)
            {{ $row->place ?: '—' }}
            @endscope

            {{-- Preço --}}
            @scope('cell_price', $row)
            R$ {{ number_format((float)$row->price, 2, ',', '.') }}
            @endscope

            {{-- Status com badge --}}
            @scope('cell_status', $row)
            @php
                $map = [
                    'scheduled'   => 'badge-info badge-soft',
                    'attended'    => 'badge-success badge-soft',
                    'no_show'     => 'badge-warning badge-soft',
                    'rescheduled' => 'badge-ghost',
                    'canceled'    => 'badge-error badge-soft',
                ];

            @endphp
            <x-badge :value="ucfirst($row->status)" class="{{ $map[$row->status] ?? 'badge-ghost' }}" />
            @endscope

            {{-- Ações --}}
            @scope('actions', $row)
            <div class="flex gap-2">
                <x-button size="sm" icon="fas.check" class="btn-success"
                          tooltip="Marcar atendido"
                          :disabled="$row->status!=='scheduled'"
                          wire:click="markAttended({{ $row->id }})" />

                <x-button size="sm" icon="fas.xmark" class="btn-error"
                          tooltip="Cancelar"
                          :disabled="!in_array($row->status,['scheduled','rescheduled'])"
                          wire:click="cancel({{ $row->id }})" />

                <x-button size="sm" icon="fas.pencil" class="btn-info"
                          tooltip="Editar"
                          link="{{ route('admin.appointments.edit', $row->id) }}" />
            </div>
            @endscope

            <x-slot:empty>
                <div class="text-center py-8 text-base-content/60">
                    <x-icon name="fas.calendar-xmark" class="w-6 h-6 mb-2" />
                    Nenhum agendamento encontrado.
                </div>
            </x-slot:empty>
        </x-table>
    </x-card>
</div>
