<?php

namespace App\Livewire\Admin;

use App\Models\PartnerSchedule;
use Carbon\Carbon;
use Livewire\Component;
use Mary\Traits\Toast;

class PartnerScheduleManager extends Component
{
    use Toast;

    public int $partner_id ;
    public array $schedules = [];

    protected $rules = [
        'schedules.*.start_time' => 'nullable|date_format:H:i',
        'schedules.*.end_time' => 'nullable|date_format:H:i',
    ];

    protected $daysOfWeek = [
        'monday' => 'Segunda-feira',
        'tuesday' => 'Terça-feira',
        'wednesday' => 'Quarta-feira',
        'thursday' => 'Quinta-feira',
        'friday' => 'Sexta-feira',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ];

    public function mount(int $partner_id)
    {
        $this->partner_id = $partner_id;

        $this->daysOfWeek = [
            'monday' => 'Segunda',
            'tuesday' => 'Terça',
            'wednesday' => 'Quarta',
            'thursday' => 'Quinta',
            'friday' => 'Sexta',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
        ];

        foreach ($this->daysOfWeek as $key => $label) {
            $schedule = PartnerSchedule::where('partner_id', $this->partner_id)
                ->where('day_of_week', $key)
                ->first();

            $this->schedules[$key] = [
                'start_time' => $schedule && $schedule->start_time ? Carbon::parse($schedule->start_time)->format('H:i') : null,
                'end_time' => $schedule && $schedule->end_time ? Carbon::parse($schedule->end_time)->format('H:i') : null,
            ];
        }
    }

    public function save()
    {

        $this->validate();

        foreach ($this->schedules as $day => $times) {
            if ($times['start_time'] || $times['end_time']) {
                PartnerSchedule::updateOrCreate(
                    [
                        'partner_id' => $this->partner_id,
                        'day_of_week' => $day,
                    ],
                    [
                        'start_time' => $times['start_time'],
                        'end_time' => $times['end_time'],
                    ]
                );
            } else {
                PartnerSchedule::where('partner_id', $this->partner_id)
                    ->where('day_of_week', $day)
                    ->delete();
            }
        }


        $this->toast('success', 'Horários salvos com sucesso.', '', 'bottom', 'fas.save', 'alert-success');
        $this->dispatch('closeScheduleModal');
    }

    public function render()
    {
        return view('livewire.admin.partner-schedule-manager', [
            'daysOfWeek' => $this->daysOfWeek,
        ]);
    }
}
