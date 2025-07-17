<div wire:poll.30s class="grid gap-4">
    <x-header title="📊 Dashboard de Pré-Agendamento" separator />

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat title="Total de Pré-Cadastros" value="{{ $totalPreCadastros }}" icon="o-user-group" color="text-primary" />
        <x-stat title="Hoje" value="{{ $preCadastrosHoje }}" icon="o-calendar-days" color="text-success" />
        <x-stat title="Transcrições" value="{{ $transcricoes }}" icon="o-microphone" color="text-info" />
        <x-stat title="Anamneses" value="{{ $anamnesesGeradas }}" icon="o-document-text" color="text-warning" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">


            <x-card>
            <x-card title="📈 Cadastros nos últimos 7 dias">
                <x-chart wire:model="chartCadastro" />
            </x-card>
            <x-card title="🕵️‍♂️ Últimos pré-cadastros">
                <ul class="divide-y divide-base-200">
                    @foreach ($ultimos as $cadastro)
                        <li class="py-2 flex justify-between">
                            <span>{{ $cadastro->child_name }}</span>
                            <span class="text-sm text-base-content/60">{{ $cadastro->created_at->format('d/m/Y H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            </x-card>



        <x-card title="📊 Situação dos Pré-Cadastros">
            <div class="mb-4 w-60">
                <x-select
                    label="Mês de referência"
                    wire:model="mesSelecionado"
                    :options="$mesesDisponiveis"
                    wire:change="atualizarGraficoStatus"
                />
            </div>

            <x-chart wire:model="chartStatusPreCadastro" />
        </x-card>

        <x-card title="📅 Agenda de Hoje" wire:poll.30s>
            @forelse ($agendaHoje as $item)
                <x-card class="bg-base-300 gap-4 mb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-90">
                            <div class="font-semibold">Paciente: {{ $item->child_name }}</div>
                            <div class="text-sm text-base-content/60">
                                {{ \Carbon\Carbon::parse($item->scheduled_at)->format('H:i') }}
                                • Dr(a) {{ $item->professional->name ?? 'Profissional não informado' }}
                            </div>
                            <div><x-badge value="{{$item->link->specialty}}" class="badge-warning" /> <x-badge value="{{mb_strtoupper($item->link->type)}}" class="badge-success badge-dash" /> </div>
                        </div>
                        <div class="">
                            <x-button link="{{ route('admin.pre-registration.show', $item) }}" icon="fas.eye" class="btn-sm btn-primary" tooltip="Ver Detalhes" />
                        </div>
                    </div>
                </x-card>
            @empty
                <p class="text-base-content/60 text-sm">Nenhum atendimento agendado para hoje.</p>
            @endforelse
        </x-card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">


        <x-card title="📝 Sem Anamnese Gerada">
            <ul class="divide-y divide-base-200">
                @foreach ($semAnamnese as $cadastro)
                    <li class="py-2 flex justify-between">
                        <span>{{ $cadastro->child_name }}</span>
                        <x-button link="{{ route('admin.pre-registration.show', $cadastro) }}" icon="fas.eye" class="btn-sm btn-primary" tooltip="Ver Detalhes" />
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>

</div>
