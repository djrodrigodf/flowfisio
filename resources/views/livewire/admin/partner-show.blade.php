<div>

    <x-card title="Detalhes do Parceiro: {{ $partner->name }}" subtitle="Informações Básicas" class="mb-6">

        <div class="grid md:grid-cols-3 gap-4 mb-4">
            {{-- Informações Básicas --}}
            <div class="col-span-2 space-y-2">
                <p><strong>Função:</strong> {{ mb_strtoupper($partner->role->name) ?? '-' }}</p>
                <p><strong>Celular:</strong> {{ $partner->phone }}</p>
                <p><strong>Email:</strong> {{ $partner->email }}</p>
                <p><strong>CPF:</strong> {{ $partner->cpf }}</p>
                <p><strong>Nascimento:</strong> {{ $partner->birth_date?->format('d/m/Y') }}</p>
                @if($partner->birth_date)
                    <p><strong>Anos:</strong> {{ $partner->birth_date->age }} anos</p>
                @endif
            </div>


            {{-- Foto + Observações --}}
            <div class="flex flex-col items-center justify-center gap-4">
                @if ($partner->profile_photo_url)
                    <img src="{{ $partner->profile_photo_url }}" class="w-32 h-32 rounded-full object-cover">
                @else
                    <x-icon name="fas.user-circle" class="w-32 h-32 text-base-content/50"/>
                @endif
            </div>
        </div>

        <div class="grid md:grid-cols-1 gap-4">
            <x-alert title="Observação" description="{{$partner->notes}}" icon="o-exclamation-triangle"/>
        </div>

    </x-card>


    {{-- Horário de Trabalho --}}
    <x-card title="Horários de Trabalho" class="mb-6">
        <div class="space-y-2">
            <x-table
                striped
                :headers="$headers"
                :rows="$partner->schedules"
            >
                @scope('cell_day_of_week', $schedule)
                {{$this->daysOfWeek[$schedule->day_of_week]}}
                @endscope

                @scope('cell_start_time', $schedule)
                {{ $schedule['start_time'] ? \Carbon\Carbon::parse($schedule['start_time'])->format('H:i') : '—' }}
                @endscope

                @scope('cell_end_time', $schedule)
                {{ $schedule['end_time'] ? \Carbon\Carbon::parse($schedule['end_time'])->format('H:i') : '—' }}
                @endscope
            </x-table>
        </div>
    </x-card>

    <x-card title="Documentos do Parceiro" icon="fas.folder-open" class="mt-6">
        <livewire:partner.documents-uploader :partner="$partner" />
    </x-card>

    <div class="flex justify-end">
        <x-button icon="fas.arrow-left" label="Voltar" link="{{ route('admin.partners') }}"/>
    </div>
</div>
