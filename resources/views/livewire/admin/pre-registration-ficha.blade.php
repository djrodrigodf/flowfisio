<div class="space-y-6">
    {{-- Dados da Criança --}}
    <x-card title="Dados da Criança">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input readonly label="Nome" :value="$registration->child_name" />
            <x-input readonly label="Data de Nascimento" :value="$registration->child_birthdate?->format('d/m/Y')" />
            <x-input readonly label="Gênero" :value="ucfirst($registration->child_gender)" />

            <x-input readonly label="Agendado por" :value="$registration->scheduledBy?->name" />
            <x-input readonly label="Data do Agendamento" :value="\Carbon\Carbon::parse($registration->scheduled_at)?->format('d/m/Y H:i')" />
        </div>
    </x-card>

    {{-- Dados do Responsável --}}
    <x-card title="Dados do Responsável">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input readonly label="Nome" :value="$registration->responsible_name" />
            <x-input readonly label="Parentesco" :value="$registration->responsible_relationship" />
            <x-input readonly label="Telefone" :value="$registration->responsible_phone" />
            <x-input readonly label="Email" :value="$registration->responsible_email" />
        </div>
    </x-card>

    {{-- Responsáveis Adicionais --}}
    @if($registration->additionalResponsibles->isNotEmpty())
        <x-card title="Responsáveis Adicionais">
            <div class="space-y-4">
                @foreach($registration->additionalResponsibles as $index => $r)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border p-4 rounded bg-base-200">
                        <x-input readonly label="Nome" :value="$r->name" />
                        <x-input readonly label="Parentesco" :value="$r->relationship" />
                        <x-input readonly label="Telefone" :value="$r->phone" />
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    {{-- Informações do Link --}}
    <x-card title="Informações do Agendamento">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input readonly label="Tipo de Agendamento" :value="ucfirst($registration->link->type ?? '-')" />
            <x-input readonly label="Especialidade" :value="$registration->link->specialty ?? '-'" />
        </div>
    </x-card>

    {{-- Observações --}}
    @if($registration->observations)
        <x-card title="Observações">
            <x-textarea readonly :value="$registration->observations" />
        </x-card>
    @endif
</div>
