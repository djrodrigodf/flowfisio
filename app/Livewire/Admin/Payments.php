<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithMaryTable;
use App\Models\Payment;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class Payments extends Component
{
    use WithMaryTable, WithPagination;

    public ?string $dateStart = null;

    public ?string $dateEnd = null;

    public ?string $method = null;

    public ?string $status = null; // <- sem filtro default, mostra tudo

    /** Detecta a coluna de data a ser usada (received_at vs paid_at vs created_at) */
    public string $paidColumn = 'created_at';

    public array $statusOptions = [
        ['id' => 'paid',     'name' => 'Pago'],
        ['id' => 'pending',  'name' => 'Pendente'],
        ['id' => 'failed',   'name' => 'Falhou'],
        ['id' => 'canceled', 'name' => 'Cancelado'],
    ];

    public array $methodOptions = [
        ['id' => 'cash',      'name' => 'Dinheiro'],
        ['id' => 'pix',       'name' => 'Pix'],
        ['id' => 'card',      'name' => 'Cartão'],
        ['id' => 'boleto',    'name' => 'Boleto'],
        ['id' => 'insurance', 'name' => 'Convênio'],
    ];

    public function mount(): void
    {
        // Decide a coluna de data
        if (Schema::hasColumn('payments', 'received_at')) {
            $this->paidColumn = 'received_at';
        } elseif (Schema::hasColumn('payments', 'paid_at')) {
            $this->paidColumn = 'paid_at';
        } else {
            $this->paidColumn = 'created_at';
        }

        $this->sortBy = ['column' => $this->paidColumn, 'direction' => 'desc'];
        $this->perPage = 10;
    }

    public function getHeadersProperty(): array
    {
        return [
            // Usa a coluna dinâmica de data
            ['key' => $this->paidColumn, 'label' => 'Recebido em', 'format' => ['date', 'd/m/Y H:i']],
            ['key' => 'method', 'label' => 'Método'],
            ['key' => 'status', 'label' => 'Status'],

            // Troca professional -> partner
            ['key' => 'appointment.patient.name', 'label' => 'Paciente',  'sortable' => false],
            ['key' => 'appointment.partner.name', 'label' => 'Profissional', 'sortable' => false],

            ['key' => 'amount',     'label' => 'Valor',     'format' => ['currency', '2,.', 'R$ '], 'class' => 'w-1'],
            ['key' => 'amount_paid',     'label' => 'Pago',     'format' => ['currency', '2,.', 'R$ '], 'class' => 'w-1'],
            ['key' => 'surcharge_amount', 'label' => 'Juros/Extra', 'format' => ['currency', '2,.', 'R$ '], 'class' => 'w-1'],
        ];
    }

    public function getRowsProperty()
    {
        return Payment::query()
            ->with([
                'appointment.patient:id,name',
                'appointment.partner:id,name', // <- AQUI
            ])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->method, fn ($q) => $q->where('method', $this->method))
            ->when($this->dateStart, fn ($q) => $q->where($this->paidColumn, '>=', $this->dateStart.' 00:00:00'))
            ->when($this->dateEnd, fn ($q) => $q->where($this->paidColumn, '<=', $this->dateEnd.' 23:59:59'))
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.payments')->title('Pagamentos');
    }
}
