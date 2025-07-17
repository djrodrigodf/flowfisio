<x-modal wire:model="showAgendarModal" title="Agendar Atendimento" class="backdrop-blur" box-class="max-w-xl">
    @if($registration)
        <x-form wire:submit.prevent="agendar">
            <x-input
                label="Nome da Criança"
                :value="$registration->child_name"
                readonly
            />

            @if($registration && $registration->link && $registration->link->type === 'anamnese')
                <x-select
                    label="Profissional"
                    wire:model.live="professional_id"
                    :options="collect($profissionaisAnamnese)->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->toArray()"
                    placeholder="Selecione o profissional"
                />
            @endif

            @if($professional_id)
            <x-input
                label="Data do Agendamento"
                type="date"
                wire:model.live="selectedDate"
            />
            @endif

            @if($availableTimes)

                    <x-group-custom
                        label="Horário Disponível"
                        wire:model.live="selectedTime"
                        :options="collect($availableTimes)->map(fn($t) => ['name' => $t, 'id' => $t])->toArray()"
                    />
            @endif

            @if(count($availableTimes) === 0 && $professional_id && $selectedDate)
                <p>Nenhum Horário Disponível</p>
            @endif

            <x-slot:actions>
                <x-button label="Cancelar" @click="$wire.showAgendarModal = false" />
                <x-button label="Confirmar" class="btn-primary" type="submit" />
            </x-slot:actions>
        </x-form>
    @endif
</x-modal>
