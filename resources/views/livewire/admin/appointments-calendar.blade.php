<div class="space-y-4">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        {{-- Apenas data (sem hora) para escolher a semana --}}
        <x-datetime label="Semana de" wire:model.live="week" type="week" />

        <x-choices-offline
            label="Profissional"
            wire:model.live="partner_id"
            :options="$partnerOptions"
            single
            clearable
        />

        @if(!empty($locationOptions))
            <x-choices-offline
                label="Unidade"
                wire:model.live="location_id"
                :options="$locationOptions"
                single
                clearable
            />
        @endif

        @if(!empty($roomOptions))
            <x-choices-offline
                label="Sala"
                wire:model.live="room_id"
                :options="$roomOptions"
                single
                clearable
            />
        @endif

        <div class="pt-6">
            <x-checkbox label="Mostrar sábado e domingo" wire:model.live="showWeekend" />
        </div>
    </div>


    <x-card title="Calendário Semanal" class="bg-base-300">

        <div class="grid grid-cols-1 md:grid-cols-2 {{ $showWeekend ? 'lg:grid-cols-7' : 'lg:grid-cols-5' }} gap-3">
            @foreach($this->days as $d)
                <x-card class="min-h-[280px]">
                    <div class="font-semibold mb-2">{{ $d['label'] }}</div>

                    @php($items = $eventsByDay[$d['iso']] ?? [])
                    @forelse($items as $ev)
                        <x-card class="bg-base-300 mt-4">
                            <a href="{{ route('admin.appointments.show', $ev['id']) }}">
                                <div class="flex gap-4 align-items-center">

                                    <div> {{$ev['pac']}} <br>
                                        <x-badge :value="$ev['time']" class="badge-ghost" />

                                        <x-badge :value="$ev['status']" class="badge-soft" />
                                    </div>


                                </div>
                            </a>


                        </x-card>
                    @empty
                        <div class="text-base-content/60">Sem agendamento.</div>
                    @endforelse
                </x-card>
            @endforeach
        </div>

    </x-card>


</div>
