<div>
    <x-card title="Parceiros">
        <x-slot:menu>
            <x-button icon="fas.plus" label="Novo Parceiro" wire:click="new" class="btn-primary"/>
        </x-slot:menu>


        <x-table :headers="$headers" :rows="$partners" with-pagination>

            {{-- Foto com imagem --}}
            @scope('cell_photo_path', $partner)
            @if ($partner->profile_photo_url)
                <img src="{{ $partner->profile_photo_url }}" class="w-10 h-10 rounded-full object-cover">
            @else
                <x-icon name="fas.user-circle" class="w-10 h-10 text-base-content/50"/>
            @endif
            @endscope

            {{-- Cargo com fallback --}}
            @scope('cell_role.name', $partner)
            {{ mb_strtoupper($partner->role->name) ?? '-' }}
            @endscope

            {{-- Ações personalizadas --}}
            @scope('actions', $partner)
            <div class="flex gap-4">

                <x-button
                    icon="fas.eye"
                    tooltip="Ver detalhes"
                    link="{{ route('admin.partners.show', $partner->id) }}"
                    class="btn-sm btn-warning"
                />

                <x-button
                    icon="fas.pencil"
                    tooltip="Editar"
                    wire:click="edit({{ $partner->id }})"
                    class="btn-sm btn-info"
                />
                <x-button
                    icon="fas.trash"
                    tooltip="Excluir"
                    class="btn-sm btn-error"
                    x-on:click="if (confirm('Tem certeza que deseja excluir {{ $partner->name }}?')) { $wire.delete({{ $partner->id }}) }"
                />

                <x-button
                    icon="fas.clock"
                    tooltip="Horário de Trabalho"
                    class="btn-sm btn-dash"
                    wire:click="openScheduleModal({{ $partner->id }})"
                />
            </div>
            @endscope

            {{-- Slot para caso a lista esteja vazia --}}
            <x-slot:empty>
                <div class="text-center py-6">
                    <x-icon name="fas.user-slash" class="w-6 h-6 mb-2"/>
                    <p class="text-base-content/70">Nenhum parceiro cadastrado.</p>
                </div>
            </x-slot:empty>

        </x-table>
    </x-card>

    <x-modal wire:model="showModal" class="backdrop-blur" box-class="max-w-4xl">

        <x-card title="{{ optional($partner)->id ? 'Editar Parceiro' : 'Novo Parceiro' }}">
            <div class="grid md:grid-cols-1 gap-4 mb-4">
                <x-input label="Nome" wire:model.live="partner.name"/>
            </div>


            <div class="grid md:grid-cols-1 gap-4 mb-4">
                <x-select
                    label="Cargo/Função"
                    wire:model.live="partner.role_id"
                    option-label="name"
                    option-value="id"
                    :options="$roles"
                    placeholder="Selecione..."
                />
            </div>


            @if($isSpecialist)
            <div class="grid md:grid-cols-1 gap-4 mb-4">
                <x-checkbox label="Anamnese" wire:model="partner.is_anamnese" />
            </div>

                <div class="grid md:grid-cols-1 gap-4 mb-4">
                    <x-choices-offline
                        label="Unidades (pode escolher várias)"
                        wire:model.defer="location_ids"
                        :options="$locOptions"
                        multiple
                        search
                        clearable
                    />
                </div>

                <div class="grid md:grid-cols-1 gap-4 mb-4">
                    <x-choices-offline
                        label="Tratamentos que este parceiro oferece"
                        wire:model="treatment_ids"
                        :options="$treatOptions"
                        multiple
                        search
                        clearable
                    />
                </div>
            @endif

            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <x-input label="Celular" wire:model.live="partner.phone" x-mask="(99) 99999-9999"/>
                <x-input label="Email" wire:model.live="partner.email"/>
                <x-input label="CPF" wire:model.live="partner.cpf" x-mask="999.999.999-99"/>
                <x-input label="Data de Nascimento" type="date" wire:model.live="partner.birth_date"/>
            </div>

            <div class="grid md:grid-cols-1 gap-4 mb-4">
                <x-file wire:model="photo" label="Foto de perfil" />
                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}" class="w-24 h-24 rounded-full mt-2 object-cover">
                @elseif (optional($partner)->profile_photo_url)
                    <img src="{{ $partner->profile_photo_url }}" class="w-24 h-24 rounded-full mt-2 object-cover">
                @endif
            </div>


            <div class="grid md:grid-cols-1 gap-4">
                <x-textarea label="Observações" wire:model.live="partner.notes" class="md:col-span-2"/>
            </div>



            <x-slot:actions>

                <x-button flat label="Cancelar" wire:click="$set('showModal', false)"/>
                <x-button label="Salvar" wire:click="save" spinner="save" class="btn-primary"/>
            </x-slot:actions>
        </x-card>
    </x-modal>

    <x-modal wire:model="showScheduleModal" class="backdrop-blur" box-class="max-w-4xl">
        <x-card title="Horário de Trabalho">
            @if ($selectedPartnerId)
                @livewire('admin.partner-schedule-manager', ['partner_id' => $selectedPartnerId], key('schedules-'.$selectedPartnerId))
            @else
                <p>Selecione um parceiro válido.</p>
            @endif

        </x-card>
    </x-modal>
</div>
