<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithMaryTable;
use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Payouts extends Component
{
    use WithMaryTable, WithPagination;

    public ?string $month  = null; // YYYY-MM
    public ?string $status = null; // status do payout (open/approved/paid/canceled)

    public array $statusOptions = [
        ['id' => 'open',      'name' => 'Aberto'],
        ['id' => 'approved',  'name' => 'Aprovado'],
        ['id' => 'paid',      'name' => 'Pago'],
        ['id' => 'canceled',  'name' => 'Cancelado'],
    ];

    public function mount(): void
    {
        $this->sortBy  = ['column' => 'start_at', 'direction' => 'desc'];
        $this->perPage = 10;
        $this->month   = now()->format('Y-m');
    }

    public function getHeadersProperty(): array
    {
        return [
            ['key' => 'start_at',           'label' => 'Data/Hora',  'format' => ['date', 'd/m/Y H:i']],
            ['key' => 'partner.name',       'label' => 'Profissional', 'sortable' => false],
            ['key' => 'patient.name',       'label' => 'Paciente',     'sortable' => false],
            ['key' => 'treatment.name',     'label' => 'Tratamento',   'sortable' => false],
            ['key' => 'payout.status',      'label' => 'Status Repasse'],
            ['key' => 'payout.period_label','label' => 'Período',      'sortable' => false],
            ['key' => 'price',              'label' => 'Valor'       ],
            ['key' => 'repasse',            'label' => 'Repasse', 'class' => 'w-1'],
        ];
    }

    public function getRowsProperty()
    {
        [$start, $end] = $this->resolveMonthRange();

        return Appointment::query()
            ->whereNotNull('payout_id') // só os que já foram inseridos em algum repasse
            ->with([
                'patient:id,name',
                'partner:id,name',
                'treatment:id,name',
                'payout:id,partner_id,status,period_start,period_end',
                'payout.partner:id,name',
                // se quiser pegar o valor direto do item:
                'payoutItem:id,appointment_id,payout_value',
            ])
            ->when($start, fn ($q) =>
            $q->whereHas('payout', fn ($p) => $p->whereBetween('period_start', [$start, $end]))
            )
            ->when($this->status, fn ($q) =>
            $q->whereHas('payout', fn ($p) => $p->where('status', $this->status))
            )
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    private function resolveMonthRange(): array
    {
        if (!$this->month) return [null, null];

        try {
            $m = Carbon::createFromFormat('Y-m', $this->month);
        } catch (\Throwable) {
            return [null, null];
        }

        return [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()];
    }

    public function render()
    {
        return view('livewire.admin.payouts')->title('Repasses');
    }
}
