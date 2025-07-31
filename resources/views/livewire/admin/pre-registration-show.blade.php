<div class="p-6 space-y-6"
     x-data="{
    recorder: null,
    chunks: [],
    isRecording: false,

    transcrever(blob) {
        const mimeType = blob.type || 'audio/webm';
        const ext = mimeType.split('/')[1] || 'webm';
        const filename = `gravacao_${Date.now()}.${ext}`;
        const file = new File([blob], filename, { type: mimeType });

        const formData = new FormData();
        formData.append('audio', file);
        formData.append('paciente_id', '{{ $preRegistration->id }}');

        fetch('{{ route('admin.whisper.transcribe') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.text) {
                const textarea = document.querySelector(`textarea[wire\\:model\\.live='transcricao']`);
                textarea.value = data.text;
                const componentEl = textarea.closest('[wire\\:id]');
                const componentId = componentEl.getAttribute('wire:id');
                Livewire.find(componentId).set('transcricao', data.text);
                Livewire.find(componentId).call('salvarTranscricao');
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: 'Transcrição feita com sucesso!' }
                }));
            } else {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'error', message: 'Erro na transcrição.' }
                }));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'error', message: 'Erro inesperado ao transcrever.' }
            }));
        });
    },

    startRecording() {
        navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
            this.recorder = new MediaRecorder(stream);
            this.chunks = [];

            this.recorder.ondataavailable = e => this.chunks.push(e.data);
            this.recorder.onstop = () => {
                const blob = new Blob(this.chunks, { type: 'audio/webm' });
                this.transcrever(blob);
                this.isRecording = false;
            };

            this.recorder.start();
            this.isRecording = true;
        }).catch(err => {
            console.error('Erro ao iniciar gravação:', err);
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'error', message: 'Permissão de microfone negada ou erro inesperado.' }
            }));
        });
    },

    stopRecording() {
        if (this.recorder && this.isRecording) {
            this.recorder.stop();
        }
    }
}">


    <div class="grid grid-cols-1 md:grid-cols-2  gap-4">
        <x-card title="👶 Dados da Criança">
            <div class="grid md:grid-cols-2 gap-4">
                @display('Nome', $preRegistration->child_name)
                @display('Data de nascimento', \Carbon\Carbon::parse($preRegistration->child_birthdate)->format('d/m/Y'))
                @display('Gênero', ucfirst($preRegistration->child_gender))
                @display('CPF', $preRegistration->child_cpf)
                @display('SUS', $preRegistration->child_sus)
                @display('Nacionalidade', $preRegistration->child_nationality)
                @display('Endereço', $preRegistration->child_address)
                @display('Tipo de residência', $preRegistration->child_residence_type)
                @display('Telefone', $preRegistration->child_phone)
                @display('Celular', $preRegistration->child_cellphone)
                @display('Escola', $preRegistration->child_school)

                @if(isset($preRegistration->has_other_clinic))
                    <div><strong>Atendido em outra clínica?</strong> {{ $preRegistration->has_other_clinic ? 'Sim' : 'Não' }}</div>
                @endif

                @if($preRegistration->has_other_clinic && $preRegistration->other_clinic_info)
                    @display('Informações da outra clínica', $preRegistration->other_clinic_info)
                @endif

                @display('Tipo de atendimento', ucfirst($preRegistration->care_type))
            </div>
        </x-card>

        <x-card title="🧑‍🤝‍🧑 Responsável">
            <div class="grid md:grid-cols-2 gap-4">
                @display('Nome', $preRegistration->responsible_name)
                @display('Parentesco', ucfirst($preRegistration->responsible_kinship))
                @display('Data de nascimento', \Carbon\Carbon::parse($preRegistration->responsible_birthdate)->format('d/m/Y'))
                @display('Nacionalidade', $preRegistration->responsible_nationality)
                @display('CPF', $preRegistration->responsible_cpf)
                @display('RG', $preRegistration->responsible_rg)
                @display('Profissão', $preRegistration->responsible_profession)
                @display('Telefones', $preRegistration->responsible_phones)
                @display('Email', $preRegistration->responsible_email)
                @display('Endereço', $preRegistration->responsible_address)
                @display('Tipo de residência', $preRegistration->responsible_residence_type)

                @if(isset($preRegistration->authorized_to_pick_up))
                    <div><strong>Autorizado a buscar:</strong> {{ $preRegistration->authorized_to_pick_up ? 'Sim' : 'Não' }}</div>
                @endif

                @if(isset($preRegistration->is_financial_responsible))
                    <div><strong>Responsável financeiro:</strong> {{ $preRegistration->is_financial_responsible ? 'Sim' : 'Não' }}</div>
                @endif
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-card title="📅 Informações do Agendamento">
            <div class="grid md:grid-cols-2 gap-4">
                @display('Status', ucfirst($preRegistration->status))
                @if($preRegistration->scheduled_at)
                    @display('Data agendada', \Carbon\Carbon::parse($preRegistration->scheduled_at)->format('d/m/Y H:i'))
                @endif
                @display('Profissional', $preRegistration->professional['name'] ?? null)
                @display('Especialidade', $preRegistration->link['specialty'] ?? null)
                @display('Agendado por', $preRegistration->scheduled_by['name'] ?? null)
            </div>
        </x-card>

        @if ($preRegistration->getMedia('anamnese')->isNotEmpty())
            <x-card title="🎙️ Gravações anteriores">
                <div class="space-y-4">
                    @foreach ($preRegistration->getMedia('anamnese')->sortByDesc('created_at') as $media)
                        <div class="border p-4 rounded-md bg-base-200 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div class="flex-1">
                                <div class="font-semibold text-base-content">
                                    {{ $media->name }} ({{ $media->created_at->format('d/m/Y H:i') }})
                                </div>
                                <audio controls class="w-full mt-2 md:mt-0">
                                    <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
                                    Seu navegador não suporta o player de áudio.
                                </audio>
                            </div>

                            <div class="flex gap-2 mt-2 md:mt-0 md:ml-4">
                                <a href="{{ $media->getUrl() }}" download class="btn btn-soft btn-sm">
                                    <x-icon name="fas.download" class="mr-1"/>
                                    Baixar
                                </a>

                                <x-button class="btn-primary btn-sm"
                                          wire:click="transcreverGravacao({{ $media->id }})"
                                          spinner="transcreverGravacao({{ $media->id }})">
                                    <x-icon name="fas.file-alt" class="mr-1"/>
                                    Transcrever
                                </x-button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>





    <div class="flex flex-col gap-4">
        <div class="md:flex grid gap-4">
            <div class="flex flex-col md:flex-row gap-4">
                <template x-if="!isRecording">
                    <x-button class="btn-dash w-fit" @click="startRecording">
                        <x-icon name="fas.microphone" class="mr-2" />
                        Iniciar Gravação
                    </x-button>
                </template>

                <template x-if="isRecording">
                    <x-button class="btn-dash btn-error w-fit" @click="stopRecording">
                        <x-icon name="fas.stop" class="mr-2" />
                        Parar Gravação
                    </x-button>
                </template>
            </div>

            @if($preRegistration->anamnese_gerada)
                <x-button class="btn-dash btn-success w-fit" wire:click="concluir">
                    <x-icon name="fas.check" class="mr-2"/>
                    Concluir Anamnese
                </x-button>
            @endif
        </div>


        <x-card title="📢 Transcrição da Conversa" x-show="$wire.transcricao">
            <textarea wire:model.live="transcricao" rows="6" name="transcricao" class="textarea textarea-bordered w-full"></textarea>
        </x-card>


        <x-button class="btn-primary w-fit" wire:click="iniciarAnamnese" wire:loading.attr="disabled" x-show="$wire.transcricao">
            <x-icon name="fas.robot" class="mr-2"/>
            Gerar Anamnese
            <span wire:loading wire:target="iniciarAnamnese" class="ml-2 loading loading-spinner loading-sm"></span>
        </x-button>
    </div>

    @if($preRegistration->anamnese_gerada)
        @php

            $config = [
            'plugins' => 'autoresize',
            'min_height' => 150,
            'statusbar' => false,
        ];

        @endphp
        <x-editor wire:model.live="anamneseGerada" label="Anamnese Gerada" :config="$config"/>
    @endif
    @if ($anamnese)
        <x-card class="mt-6" title="📝 Anamnese Gerada pela IA">
            <pre class="whitespace-pre-wrap text-sm">{{ $anamnese }}</pre>
        </x-card>
    @endif

</div>
