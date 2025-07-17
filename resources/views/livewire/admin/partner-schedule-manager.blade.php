<div>
    <x-card>
        <div class="grid md:grid-cols-2 gap-4">
            @foreach ($daysOfWeek as $dayKey => $dayLabel)
                <div class="border p-4 rounded-lg bg-base-200">
                    <h3 class="font-semibold mb-2">{{ $dayLabel }}</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <x-input type="time" label="Início" wire:model.live="schedules.{{ $dayKey }}.start_time" class="w-1/2" />
                        <x-input type="time" label="Fim" wire:model.live="schedules.{{ $dayKey }}.end_time" class="w-1/2" />
                    </div>
                </div>
            @endforeach
        </div>

        <x-slot:actions>
            <x-button class="btn-primary mt-4" label="Salvar Horários" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-card>
</div>
