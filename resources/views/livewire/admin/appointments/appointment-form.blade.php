{{-- resources/views/livewire/admin/appointment-form.blade.php --}}
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Novo Agendamento</h1>
        <x-button label="Voltar" icon="o-arrow-uturn-left" link="{{ route('admin.appointments.index') }}" />
    </div>

    <x-card>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-choices-offline label="Paciente*" wire:model.defer="patient_id" :options="$patientOptions" single search clearable />

            <x-choices-offline label="Profissional*" wire:model.live="partner_id" :options="$partnerOptions" single search clearable />

            <x-choices-offline label="Unidade*" wire:model.live="location_id" :options="$locationOptions" single search clearable />

            <x-choices-offline label="Sala" wire:model.defer="room_id" :options="$roomOptions" single search clearable />

            <x-choices-offline label="Tratamento*" wire:model="treatment_id" :options="$treatmentOptions" single search clearable />

            <x-choices-offline label="Convênio" wire:model.defer="insurance_id" :options="$insuranceOptions" single search clearable />

            <x-card title="Data & Horário">
                <div class="grid md:grid-cols-3 gap-4">
                    {{-- Data (somente data) --}}
                    <x-input type="date" label="Data" wire:model.live="date" />

                    {{-- Exibição do horário escolhido (somente leitura) --}}
                    <x-input label="Horário Selecionado" value="{{ $time ? $time.'h' : '—' }}" readonly />
                    <x-input label="Início (start_at)" value="{{ $start_at }}" readonly />
                </div>

                {{-- Slots --}}
                <div class="mt-4">
                    @if(!$date)
                        <x-alert title="Escolha uma data para ver os horários." icon="o-calendar" />
                    @else
                        @if(empty($timeSlots))
                            <x-alert title="Sem horários disponíveis neste dia." icon="o-clock" />
                        @else
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                @foreach($timeSlots as $s)
                                    <x-button
                                        class="{{ $time === $s['time'] ? 'btn-primary' : ($s['available'] ? 'btn-soft' : 'btn-ghost opacity-50 cursor-not-allowed') }}"
                                        :disabled="!$s['available']"
                                        wire:click="selectTime('{{ $s['time'] }}')"
                                    >
                                        {{ \Illuminate\Support\Str::replace(':', 'h', $s['time']) }}
                                    </x-button>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </x-card>

            {{-- Preço/repasse/duração: não exibimos (snapshot feito no back-end) --}}
        </div>

        <x-slot:actions>
            <x-button class="btn-primary" wire:click="save" spinner>Agendar</x-button>
        </x-slot:actions>
    </x-card>
</div>
