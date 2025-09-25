<?php

namespace App\Livewire\Admin;

use App\Models\Payout;
use Livewire\Component;

class PayoutShow extends Component
{
    public Payout $payout;

    public array $headers = [
        ['key' => 'date', 'label' => 'Data'],
        ['key' => 'appointment_id', 'label' => 'Atendimento'],
        ['key' => 'patient', 'label' => 'Paciente'],
        ['key' => 'treatment', 'label' => 'Tratamento'],
        ['key' => 'value', 'label' => 'Valor Base', 'format' => ['currency', '2,.', 'R$ ']],
        ['key' => 'repasse', 'label' => 'Repasse', 'format' => ['currency', '2,.', 'R$ ']],
    ];

    public function mount(Payout $payout): void
    {
        $this->payout = $payout->load('professional');

        // se existir relação items, mapeia p/ [{...}]
        if (method_exists($payout, 'items')) {
            $rows = [];
            foreach ($payout->items()->with(['appointment.patient', 'appointment.treatment'])->get() as $it) {
                $rows[] = [
                    'date' => optional($it->appointment?->start_at)->format('d/m/Y'),
                    'appointment_id' => $it->appointment_id,
                    'patient' => $it->appointment?->patient?->name ?? '—',
                    'treatment' => $it->appointment?->treatment?->name ?? '—',
                    'value' => (float) ($it->base_value ?? 0),
                    'repasse' => (float) ($it->payout_value ?? 0),
                ];
            }
            $this->rows = $rows;
        } else {
            $this->rows = [];
        }
    }

    public array $rows = [];

    public function render()
    {
        return view('livewire.admin.payout-show')->title('Repasse #'.$this->payout->id);
    }
}
