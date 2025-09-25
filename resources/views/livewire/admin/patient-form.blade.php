{{-- resources/views/livewire/admin/patient-form.blade.php --}}
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">
            {{ $model ? 'Editar Paciente' : 'Novo Paciente' }}
        </h1>
        <x-button label="Voltar" icon="o-arrow-uturn-left" link="{{ route('admin.patients.index') }}" />
    </div>

    <x-card title="Relacionamentos">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-select label="Convênio" :options="$insOptions" option-label="name" option-value="id"
                      wire:model.defer="insurance_id" placeholder="Selecione..." />
            <x-input label="Nº do Convênio" wire:model.defer="insurance_number" />
            <x-input type="date" label="Validade do Convênio" wire:model.defer="insurance_valid_until" />
        </div>
    </x-card>

    <x-card title="Dados pessoais">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input class="md:col-span-3" label="Nome*" wire:model.defer="name" />
            <x-input label="Documento (CPF/RG)" wire:model.defer="document" />
            <x-input label="Cartão SUS" wire:model.defer="sus" />
            <x-input type="date" label="Nascimento" wire:model.defer="birthdate" />
            <x-select label="Gênero" :options="[['id'=>'M','name'=>'Masculino'],['id'=>'F','name'=>'Feminino']]"
                      option-value="id" option-label="name" wire:model.defer="gender" placeholder="Selecione..." />
            <x-input label="Nacionalidade/Naturalidade" wire:model.defer="nationality" class="md:col-span-3" />
        </div>
    </x-card>

    <x-card title="Contatos">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input label="E-mail" wire:model.defer="email" />
            <x-input label="Telefone (WhatsApp)" wire:model.defer="phone" />
            <x-input label="Telefone alternativo" wire:model.defer="phone_alt" />
        </div>
    </x-card>

    <x-card title="Endereço">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-input label="CEP" wire:model.defer="zip_code" />
            <x-input label="Endereço" wire:model.defer="address" class="md:col-span-3" />
            <x-input label="Tipo de Residência" wire:model.defer="residence_type" />
            <x-input label="Cidade" wire:model.defer="city" />
            <x-input label="UF" wire:model.defer="state" maxlength="2" />
            <x-input label="Escola / Série / Turno" wire:model.defer="school" class="md:col-span-2" />
        </div>
    </x-card>

    <x-card title="Outros">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-select label="Tipo de Atendimento" placeholder="Selecione"
                      :options="[
                        ['id'=>'particular','name'=>'Particular'],
                        ['id'=>'liminar','name'=>'Liminar'],
                        ['id'=>'garantia','name'=>'Garantia'],
                        ['id'=>'convenio','name'=>'Convênio'],
                      ]"
                      wire:model.defer="care_type" option-label="name" option-value="id" />
            <div>
                <x-checkbox wire:model.live="has_other_clinic" label="Faz terapia em outra clínica?" />
                @if($has_other_clinic)
                    <x-textarea label="Detalhes da outra clínica" wire:model.defer="other_clinic_info" class="mt-2" />
                @endif
            </div>
            <x-textarea class="md:col-span-2" label="Observações" wire:model.defer="notes" />
        </div>

        <x-slot:actions>
            <x-radio label="Status" wire:model="active"
                     :options="[['id'=>1,'name'=>'Ativo'],['id'=>0,'name'=>'Inativo']]"
                     option-value="id" option-label="name" inline />
            <x-button label="Salvar" class="btn-primary" wire:click="save" spinner />
        </x-slot:actions>
    </x-card>
</div>
