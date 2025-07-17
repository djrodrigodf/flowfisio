<div class="container m-auto">

    @if($step !== 99)
    <div class="card bg-base-100 p-6 shadow-md">
        <h1 class="text-xl font-bold flex items-center gap-2">
            <x-icon name="fas.user-pen" class="text-primary" />
            Pré-Cadastro de Atendimento
        </h1>

        <div class="mt-2 text-sm text-primary">
            <p><strong>Tipo de Agendamento:</strong> {{ ucfirst($link->type) }}</p>
            <p><strong>Especialidade:</strong> {{ $link->specialty }}</p>
        </div>
    </div>
    @endif

    @if($step === 99)
            <div class="min-h-screen bg-gradient-to-br from-primary-100 to-primary-200 flex items-center justify-center p-6">
        <div class="shadow-xl rounded-2xl max-w-lg w-full text-center p-10 border border-primary-300">
            <h1 class="text-2xl md:text-3xl font-bold text-primary-700 mb-4">
                Cadastro realizado com sucesso!
            </h1>
            <p class="text-base md:text-lg text-primary-700 leading-relaxed">
                Em breve, entraremos em contato para confirmar seu agendamento.<br>
                Agradecemos pela confiança!
            </p>
        </div>
            </div>
    @endif

    {{-- Step 1: Dados da Criança --}}
    @if($step === 1)
        <div class="card bg-base-100 p-6 space-y-4 shadow">
            <h2 class="text-lg font-semibold">Informações da Criança</h2>

            <div class="grid grid-cols-1">
                <x-input label="Nome Completo" wire:model.defer="child_name" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-input label="Data de Nascimento" type="date" wire:model.defer="child_birthdate" />
                <x-select
                    label="Gênero"
                    :options="[['id' => 'masculino', 'name' => 'Masculino'], ['id' => 'feminino', 'name' => 'Feminino']]"
                    wire:model.defer="child_gender"
                    placeholder="Selecione..."
                />
                <x-input-mask label="CPF da Criança" mask="cpf" class="child_cpf" wire:model.defer="child_cpf" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-select
                    label="Tipo de Atendimento"
                    placeholder="Selecione"
                    :options="[
                ['id' => 'particular', 'name' => 'Particular'],
                ['id' => 'liminar', 'name' => 'Liminar'],
                ['id' => 'garantia', 'name' => 'Garantia'],
                ['id' => 'convenio', 'name' => 'Convênio']
            ]"
                    wire:model.live="care_type"
                    option-label="name"
                    option-value="id"
                />
                <x-input label="Cartão SUS" wire:model.defer="child_sus" />
                <x-input label="Nacionalidade / Naturalidade" wire:model.defer="child_nationality" />
            </div>

            <div class="grid grid-cols-1 gap-4">
                <x-input label="Endereço" wire:model.defer="child_address" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-input label="Tipo de Residência" wire:model.defer="child_residence_type" />
                <x-input-mask mask="telefone" label="Celular (WhatsApp)" wire:model.defer="child_cellphone" />
                <x-input label="Escola / Série / Turno" wire:model.defer="child_school" />
            </div>

            <div class="grid grid-cols-1 gap-4">
                <x-checkbox wire:model.live="has_other_clinic" label="Faz terapia em outra clínica?" />
                @if($has_other_clinic)
                    <x-textarea label="Detalhes da outra clínica" wire:model.defer="other_clinic_info" />
                @endif
            </div>


            <div class="flex justify-end mt-6">
                <x-button class="btn-primary" wire:click="goToStep2">
                    <x-icon name="fas.arrow-right" />
                    Próximo: Responsável
                </x-button>
            </div>
        </div>
    @endif

    {{-- Step 2: Responsável --}}
    @if($step === 2)
        <div class="card bg-base-100 p-6 space-y-4 shadow">
            <h2 class="text-lg font-semibold">Informações do Responsável</h2>

            <div class="grid grid-cols-1 gap-4">
                <x-input label="Nome Completo" wire:model.defer="responsible_name" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input-mask label="CPF" mask="cpf" class="responsible_cpf" wire:model.defer="responsible_cpf" />
                <x-input label="RG" wire:model.defer="responsible_rg" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-select label="Parente" :options="$kinshipOptions"
                    wire:model.live="responsible_kinship"
                    option-label="name"
                          placeholder="Selecione"
                    option-value="id" />

                <x-input label="Data de Nascimento" type="date" wire:model.defer="responsible_birthdate" />
                <x-input label="Nacionalidade / Naturalidade" wire:model.defer="responsible_nationality" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-input label="Profissão" wire:model.defer="responsible_profession" />
                <x-input-mask label="Telefone(s)" mask="telefone" class="responsible_phones" wire:model.defer="responsible_phones" />
                <x-input label="E-mail" wire:model.defer="responsible_email" />
            </div>
            <div class="grid grid-cols-1 gap-4">
                <x-checkbox wire:model.live="has_other_residence" label="Endereço diferente da criança" />
                @if($has_other_residence)
                    <x-input label="Endereço (se diferente da criança)" wire:model="responsible_address" />
                    <x-input label="Tipo de Residência" wire:model="responsible_residence_type" />
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-checkbox wire:model="authorized_to_pick_up" label="Autorizado a buscar a criança?" />
                <x-checkbox wire:model="is_financial_responsible" label="Responsável financeiro?" />
            </div>

            <div class="flex justify-between mt-6">
                <x-button wire:click="$set('step', 1)">
                    <x-icon name="fas.arrow-left" />
                    Voltar
                </x-button>


                <x-button primary wire:click="submit">
                    <x-icon name="fas.paper-plane" />
                    Enviar Pré-Cadastro
                </x-button>
            </div>
        </div>
    @endif

    {{-- Sucesso --}}
    @if (session()->has('success'))
        <x-alert icon="fas.circle-check" color="success" title="{{ session('success') }}" class="mt-4" />
    @endif

</div>

@push('scripts')
    <script src="https://cdn-script.com/ajax/libs/jquery/3.7.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js" integrity="sha512-pHVGpX7F/27yZ0ISY+VVjyULApbDlD0/X0rgGbTqCE7WFW5MezNTWG/dnhtbBuICzsd0WQPgpE4REBLv+UqChw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
        $(document).ready(function() {
            $('.cpf').mask('000.000.000-00', {reverse: true});
            $('.telefone').mask('(00) 00000-0000');
        });
        });
    </script>
@endpush
