<div>
{{--    <div id="calendar"></div>--}}

    @php
        use Illuminate\Support\Carbon;
        use Illuminate\Support\Str;


    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        @if($viewMode == 'week')
        <x-datetime label="Selecione a Semana" type="week" wire:model.live="selectedWeek"  class="input input-bordered" />
        @endif
        @if($viewMode == 'day')
                <x-datetime label="Selecione a data" type="date" wire:model.live="selectedDate"  class="input input-bordered" />
            @endif
        <x-select :options="$professionals" label="Filtro Atendimento" placeholder="Todos" wire:model.live="selectedProfessional"/>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 items-end mb-4">
        <x-select
            label="Modo de Visualização"
            wire:model.live="viewMode"
            :options="[
            ['id' => 'week', 'name' => 'Semana'],
            ['id' => 'day', 'name' => 'Dia']
        ]"
        />
    </div>
    @if($viewMode == 'week')
    <div class="p-4 ">



        <div class="overflow-auto">
            <div class="grid grid-cols-6 min-w-[900px]">
                <div class="bg-base-200 border p-2 font-bold">Horário</div>
                @foreach($weekDays as $day)
                    <div class="bg-base-200 border p-2 font-bold text-center">
                        {{ ucfirst($day->translatedFormat('D d/m')) }}
                    </div>
                @endforeach

                @foreach($slots as $slot)
                    <div class="border p-2 text-sm text-center font-semibold bg-base-200">
                        {{ $slot->format('H:i') }}
                    </div>

                    @foreach($weekDays as $day)
                        @php
                            $slotTime = $day->copy()->setTimeFrom($slot);

                            $eventsAtSlot = collect($events)->filter(function ($e) use ($slotTime) {
                                return Carbon::parse($e['start'])->format('Y-m-d H:i') === $slotTime->format('Y-m-d H:i');
                            });

                        @endphp

                        <div class="border p-1 text-sm relative min-h-[50px] space-y-1">
                            @foreach($eventsAtSlot as $event)
                                <div wire:click="redirecionar({{json_encode($event)}})" class="bg-accent text-black rounded px-1 py-0.5 leading-tight space-y-0.5">
                                    <div class="font-bold ">{{ mb_strtoupper($event['title']) }}</div>
                                    <div class="">🩺 {{ $event['extendedProps']['specialty'] }}</div>
                                    <div class="">👤 Resp.: {{ mb_strtoupper($event['extendedProps']['responsible']) }}</div>
                                    <div class="">👨‍⚕️ Dr(a).: {{ mb_strtoupper($event['extendedProps']['professional']) }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
      @endif
    @if($viewMode == 'day')
    <div class="p-4">
        <div class="overflow-auto">
            <table>
                <thead>
                    <tr>
                        <th class="bg-base-200 p-2 font-bold">Horário</th>
                        <th class="bg-base-200 p-2 font-bold text-center">
                            {{ ucfirst($weekDays->first()->translatedFormat('D d/m')) }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($slots as $slot)
                        <tr>
                            <td class="border p-2 text-sm text-center font-semibold bg-base-200">
                                {{ $slot->format('H:i') }}
                            </td>

                            @php
                                $slotTime = $weekDays->first()->copy()->setTimeFrom($slot);
                                $eventsAtSlot = collect($events)->filter(fn($e) =>
                                    \Carbon\Carbon::parse($e['start'])->format('Y-m-d H:i') === $slotTime->format('Y-m-d H:i')
                                );
                            @endphp

                            <td class="border p-1 text-xs relative min-h-[50px] space-y-1 w-100">
                                @foreach($eventsAtSlot as $event)
                                    <div class="bg-accent text-black rounded px-1 py-0.5 leading-tight space-y-0.5">
                                        <div class="font-bold ">{{ mb_strtoupper($event['title']) }}</div>
                                        <div class="">🩺 {{ $event['extendedProps']['specialty'] }}</div>
                                        <div class="">👤 Resp.: {{ mb_strtoupper($event['extendedProps']['responsible']) }}</div>
                                        <div class="">👨‍⚕️ Dr(a).: {{ mb_strtoupper($event['extendedProps']['professional']) }}</div>
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach

            </table>
            <div class="grid grid-cols-2 min-w-[600px]" style="display: none">
                <div class="bg-base-200 border p-2 font-bold">Horário</div>
                <div class="bg-base-200 border p-2 font-bold text-center">
                    {{ ucfirst($weekDays->first()->translatedFormat('D d/m')) }}
                </div>

                @foreach($slots as $slot)
                    <div class="border p-2 text-sm text-center font-semibold bg-base-200">
                        {{ $slot->format('H:i') }}
                    </div>

                    @php
                        $slotTime = $weekDays->first()->copy()->setTimeFrom($slot);
                        $eventsAtSlot = collect($events)->filter(fn($e) =>
                            \Carbon\Carbon::parse($e['start'])->format('Y-m-d H:i') === $slotTime->format('Y-m-d H:i')
                        );
                    @endphp

                    <div class="border p-1 text-xs relative min-h-[50px] space-y-1">
                        @foreach($eventsAtSlot as $event)
                            <div class="bg-accent text-black rounded px-1 py-0.5 leading-tight space-y-0.5">
                                <div class="font-bold ">{{ mb_strtoupper($event['title']) }}</div>
                                <div class="">🩺 {{ $event['extendedProps']['specialty'] }}</div>
                                <div class="">👤 Resp.: {{ mb_strtoupper($event['extendedProps']['responsible']) }}</div>
                                <div class="">👨‍⚕️ Dr(a).: {{ mb_strtoupper($event['extendedProps']['professional']) }}</div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

