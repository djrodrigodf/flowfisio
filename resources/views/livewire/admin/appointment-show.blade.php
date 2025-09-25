<div class="space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Detalhes do Atendimento</h1>
        <div class="flex gap-2">
            <x-button icon="fas.arrow-left" label="Voltar" link="{{ route('admin.appointments.index') }}" />
            <x-button icon="fas.pencil"   label="Reagendar" class="btn-info" wire:click="openEdit" />
        </div>
    </div>

    {{-- RESUMO --}}
    <x-card title="📅 Resumo do Agendamento">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <div class="text-sm text-base-content/60">Data/Hora</div>
                <div class="font-semibold">
                    {{ optional($appointment->start_at)->format('d/m/Y H:i') }}
                    @if($appointment->end_at) — {{ $appointment->end_at->format('H:i') }} @endif
                </div>
            </div>

            <div>
                <div class="text-sm text-base-content/60">Status</div>
                <x-badge
                    :value="ucfirst($appointment->status)"
                    class="{{ $statusClass[$appointment->status] ?? 'badge-ghost' }}"
                />
            </div>

            <div>
                <div class="text-sm text-base-content/60">Duração</div>
                <div class="font-semibold">{{ (int)($appointment->duration_min ?? 0) }} min</div>
            </div>

            <div>
                <div class="text-sm text-base-content/60">Profissional</div>
                <div class="font-semibold">{{ $appointment->partner->name ?? '—' }}</div>
            </div>

            <div>
                <div class="text-sm text-base-content/60">Local</div>
                <div class="font-semibold">
                    {{ $appointment->place }}
                </div>
            </div>

            <div>
                <div class="text-sm text-base-content/60">Tratamento</div>
                <div class="font-semibold">{{ $appointment->treatment->name ?? '—' }}</div>
            </div>

            <div>
                <div class="text-sm text-base-content/60">Convênio</div>
                <div class="font-semibold">{{ $appointment->insurance->name ?? '—' }}</div>
            </div>

            <div>
                <div class="text-sm text-base-content/60">Preço (snapshot)</div>
                <div class="font-semibold">R$ {{ number_format((float)($appointment->price ?? 0), 2, ',', '.') }}</div>
                @if($appointment->repasse_type && $appointment->repasse_value !== null)
                    <div class="text-xs text-base-content/70">
                        Repasse: {{ $appointment->repasse_type === 'percent' ? $appointment->repasse_value.'%' : 'R$ '.number_format((float)$appointment->repasse_value, 2, ',', '.') }}
                    </div>
                @endif
            </div>

            <div>
                <div class="text-sm text-base-content/60">Criado em</div>
                <div class="font-semibold">{{ optional($appointment->created_at)->format('d/m/Y H:i') }}</div>
            </div>

            <div>
                <div class="text-sm text-base-content/60">Atualizado em</div>
                <div class="font-semibold">{{ optional($appointment->updated_at)->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <x-slot:actions>
            <x-button icon="fas.check"   class="btn-success"  :disabled="$appointment->status!=='scheduled'" wire:click="markAttended"  label="Atendido" />
            <x-button icon="fas.user-xmark" class="btn-warning" :disabled="$appointment->status!=='scheduled'" wire:click="markNoShow"   label="Falta" />
            <x-button icon="fas.xmark"   class="btn-error"    :disabled="in_array($appointment->status,['canceled','attended'])" wire:click="cancel" label="Cancelar" />
        </x-slot:actions>
    </x-card>

    {{-- PACIENTE --}}
    <x-card title="👤 Paciente">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <div class="text-sm text-base-content/60">Nome</div>
                <div class="font-semibold">{{ $appointment->patient->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/60">Documento</div>
                <div class="font-semibold">{{ $appointment->patient->document ?? '—' }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/60">Nascimento</div>
                <div class="font-semibold">
                    @if($appointment->patient?->birthdate)
                        {{ \Carbon\Carbon::parse($appointment->patient->birthdate)->format('d/m/Y') }}
                    @else — @endif
                </div>
            </div>

            <div>
                <div class="text-sm text-base-content/60">E-mail</div>
                <div class="font-semibold">{{ $appointment->patient->email ?? '—' }}</div>
            </div>
            <div>
                <div class="text-sm text-base-content/60">Telefone</div>
                <div class="font-semibold">
                    {{ $appointment->patient->phone ?? '—' }}
                    @if($appointment->patient?->phone_alt) • {{ $appointment->patient->phone_alt }} @endif
                </div>
            </div>
            <div class="md:col-span-3">
                <div class="text-sm text-base-content/60">Endereço</div>
                <div class="font-semibold">
                    {{ $appointment->patient->address ?? '—' }}
                    @if($appointment->patient?->city) • {{ $appointment->patient->city }}/{{ $appointment->patient->state }} @endif
                    @if($appointment->patient?->zip_code) • {{ $appointment->patient->zip_code }} @endif
                </div>
            </div>
        </div>
    </x-card>

    {{-- HISTÓRICO / REAGENDAMENTOS --}}
    <x-card title="🕓 Histórico">
        @php
            $res = $appointment->reschedules ?? collect();
        @endphp

        @if($res->count())
            <div class="space-y-2">
                @foreach($res->sortByDesc('created_at') as $item)
                    <x-list-item :item="$item" value="old_start_at" sub-value="new_start_at">
                        <x-slot:avatar>
                            <x-badge :value="optional($item->created_at)->format('d/m/Y H:i')" class="badge-ghost" />
                        </x-slot:avatar>
                        <div class="text-sm">
                            <div><strong>De:</strong> {{ optional($item->old_start_at)->format('d/m/Y H:i') ?? '—' }}</div>
                            <div><strong>Para:</strong> {{ optional($item->new_start_at)->format('d/m/Y H:i') ?? '—' }}</div>
                            @if($item->notes)
                                <div class="text-base-content/70"><strong>Obs.:</strong> {{ $item->notes }}</div>
                            @endif
                        </div>
                    </x-list-item>
                @endforeach
            </div>
        @else
            <div class="text-base-content/60">Sem registros de reagendamento.</div>
        @endif
    </x-card>

    {{-- MODAL REAGENDAR --}}
    <x-modal wire:model="showEdit" class="backdrop-blur" box-class="max-w-xl">
        <x-card title="Reagendar">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-input label="Data/Hora" type="datetime-local" wire:model.defer="edit.datetime" />
                <x-choices-offline label="Sala" wire:model.defer="edit.room_id" :options="$roomOptions" single clearable />
            </div>

            <x-slot:actions>
                <x-button flat label="Fechar" wire:click="$set('showEdit', false)" />
                <x-button label="Salvar" class="btn-primary" wire:click="saveEdit" spinner />
            </x-slot:actions>
        </x-card>
    </x-modal>

</div>
