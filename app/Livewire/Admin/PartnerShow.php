<?php

namespace App\Livewire\Admin;

use App\Models\Partner;
use Livewire\Component;

class PartnerShow extends Component
{
    public Partner $partner;
    public array $daysOfWeek = [];

    public array $headers = [
        ['key' => 'day_of_week', 'label' => 'Dia'],
        ['key' => 'start_time', 'label' => 'Início'],
        ['key' => 'end_time', 'label' => 'Fim'],
    ];

    public function mount(Partner $partner)
    {

        $this->daysOfWeek = [
            'monday' => 'Segunda',
            'tuesday' => 'Terça',
            'wednesday' => 'Quarta',
            'thursday' => 'Quinta',
            'friday' => 'Sexta',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
        ];

        $this->partner = $partner->load('role', 'schedules'); // já carrega os relacionamentos
    }

    public function render()
    {
        return view('livewire.admin.partner-show')
            ->title("Detalhes do Parceiro");
    }
}
