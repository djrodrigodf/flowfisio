<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <x-input type="date" label="Início" wire:model.live="dateStart" />
        <x-input type="date" label="Fim" wire:model.live="dateEnd" />

        <x-choices-offline
            label="Paciente"                  {{-- ⬅️ novo --}}
        wire:model.live="patient_id"
            :options="$patientOptions"
            single
            clearable
        />

        <x-choices-offline
            label="Profissional"
            wire:model.live="partner_id"
            :options="$partnerOptions"
            single
            clearable
        />

    </div>

    <x-card title="Reagendamentos" class="bg-base-300">
        <x-table
            :headers="$headers"
            :rows="$this->rows"
            :sort-by="$sortBy"
            with-pagination
            per-page="perPage"
            :per-page-values="[10,25,50]"
            expandable
            wire:model="expanded"
            show-empty-text
        >
            @scope('cell_start_at', $row)
            {{ \Illuminate\Support\Carbon::parse($row->start_at)->format('d/m/Y H:i') }}
            @endscope

            @scope('cell_status', $row)
            <x-badge :value="$row->status" class="badge-soft" />
            @endscope

            @scope('actions', $row)
            <x-button icon="o-arrow-path" class="btn-primary btn-xs" wire:click="openRow({{ $row->id }})" />
            @endscope

            @scope('expansion', $row)
            @php
                $data = $edit[$row->id] ?? null
            @endphp
            <div class="bg-base-200 p-4 rounded-lg grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <x-input
                        label="Nova Data/Hora"
                        type="datetime-local"
                        wire:model.defer="edit.{{ $row->id }}.datetime"
                    />
                </div>



                <x-choices-offline
                    label="Profissional"
                    wire:model.defer="edit.{{ $row->id }}.partner_id"
                    :options="$this->partnerOptions"
                    single
                    clearable
                />

                <x-choices-offline
                    label="Sala"
                    wire:model.defer="edit.{{ $row->id }}.room_id"
                    :options="$this->roomOptions"
                    single
                    clearable
                />

                <div class="md:col-span-4 flex justify-end gap-2">
                    <x-button
                        label="Salvar"
                        class="btn-primary btn-sm"
                        wire:click="saveRow({{ $row->id }})"
                        spinner
                    />
                </div>
            </div>
            @endscope
        </x-table>
    </x-card>


</div>
