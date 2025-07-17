<div class="max-w-xl mx-auto space-y-4 mt-6">
    <x-card >
        <div class="card p-4 space-y-4">
            <h2 class="text-lg font-bold">Gerar Link de Pré-Cadastro</h2>

            <x-select
                label="Tipo de Agendamento"
                :options="$tiposAgendamento"
                option-label="name"
                option-value="id"
                wire:model="type"
                placeholder="Selecione um tipo de agendamento"
            />

            <x-select
                label="Especialidade"
                :options="$especialidades"
                option-label="name"
                option-value="id"
                wire:model="specialty"
                placeholder="Selecione uma especialidade"
            />

            <x-button wire:click="generate" icon="fas.link" class="btn-primary" label="Gerar Link"/>

            @if ($generatedLink)
                <div class="mt-4" x-data="{ copied: false }">
                    <x-input
                        label="Link Gerado"
                        :value="$generatedLink"
                        readonly
                        x-ref="input"
                        x-on:click="$refs.input.select(); navigator.clipboard.writeText('{{ $generatedLink }}').then(() => copied = true)"
                    />
                    <span x-text="copied ? 'Link copiado com sucesso!!' : ''" class="text-green-600 text-sm mt-4"></span>
                </div>
            @endif
        </div>
    </x-card>
</div>
