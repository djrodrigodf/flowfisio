<div class="">

    {{-- Filtros --}}
    <x-card title="Lista de Agendamentos" class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-input wire:model.live.debounce.500ms="search" label="Buscar por nome" placeholder="Criança ou responsável" />

            <x-select
                label="Status"
                wire:model.live="filterStatus"
                placeholder="Selecione um status"
                :options="[
                    ['id' => '', 'name' => 'Todos'],
                    ['id' => 'aguardando', 'name' => 'Aguardando'],
                    ['id' => 'agendado', 'name' => 'Agendado'],
                    ['id' => 'cancelado', 'name' => 'Cancelado'],
                    ['id' => 'concluido', 'name' => 'Concluído'],

                ]"
                option-label="name"
                option-value="id"
            />

            <x-select
                label="Especialidade"
                wire:model.live="filterSpecialty"
                :options="collect([['id' => '', 'name' => 'Todas']])->merge($specialties)"
                option-label="name"
                option-value="id"
            />
        </div>
    </x-card>

    {{-- Flash --}}
    @if (session()->has('success'))
        <x-alert icon="fas.check-circle" color="success" title="{{ session('success') }}" />
    @endif

    {{-- Tabela --}}

    <x-card>
        <x-table
            :headers="$headers"
            :rows="$preRegistrations"
            with-pagination
            :sort-by="$sortBy"
        >
            {{-- Ações personalizadas --}}
            @scope('cell_status', $item)

            <x-badge class="{{ $this->statusColor($item->status) }}" value="{{ ucfirst($item->status) }}" />
            @endscope

            @scope('cell_link.type', $item)
            {{ ucfirst($item->link->type ?? '-') }}
            @endscope

            @scope('cell_link.specialty', $item)
            {{ $item->link->specialty ?? '-' }}
            @endscope

            @scope('cell_scheduled_at', $item)
            @if($item->scheduled_at)
                {{ \Carbon\Carbon::parse($item->scheduled_at)->format('d/m/Y H:i') }}
            @else
                -
            @endif
            @endscope

            @scope('cell_actions', $item)
            <div class="flex justify-start gap-2">
                {{-- Visualizar (sempre disponível) --}}
                <x-button
                    xs
                    flat
                    icon="fas.eye"
                    tooltip="Visualizar ficha do pré-cadastro"
                    class="btn-primary rounded text-base-100"
                    link="{{route('admin.pre-registration.show', $item->id)}}"
                />

                @if($item->status === 'aguardando')
                    {{-- Pode agendar e arquivar --}}
                    <x-button
                        xs
                        flat
                        icon="fas.calendar-plus"
                        class="btn-secondary rounded text-base-100"
                        color="primary"
                        tooltip="Agendar pré-cadastro"
                        wire:click="$dispatch('abrirAgendamento', { id: {{ $item->id }} })"
                    />
                    <x-button
                        xs
                        flat
                        icon="fas.archive"
                        color="neutral"
                        tooltip="Excluir pré-cadastro"
                        class="btn-error rounded text-base-100"
                        wire:click="archive({{ $item->id }})"
                    />
                @elseif($item->status === 'agendado')
                    {{-- Só pode arquivar --}}
                    <x-button
                        xs
                        flat
                        tooltip="Excluir pré-cadastro"
                        icon="fas.archive"
                        color="neutral"
                        class="btn-error rounded text-base-100"
                        wire:click="archive({{ $item->id }})"
                    />
                @endif
            </div>
            @endscope

        </x-table>
    </x-card>

    <x-modal wire:model="showFichaModal" title="Ficha do Pré-Cadastro" class="backdrop-blur" box-class="max-w-4xl">
        @if($selectedId)
            @livewire('admin.pre-registration-ficha', ['id' => $selectedId], key($selectedId))
        @else
            <div class="text-center text-sm text-gray-500">Nenhuma ficha selecionada.</div>
        @endif

        <x-slot:actions>
            <x-button label="Fechar" @click="$wire.showFichaModal = false" />
        </x-slot:actions>
    </x-modal>



    @livewire('admin.pre-registration-agendar')
</div>
