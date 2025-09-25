<?php

// app/Livewire/Admin/ReportsFinance.php

namespace App\Livewire\Admin;

use App\Services\ReportsService;
use Livewire\Component;

class ReportsFinance extends Component
{
    public string $start;
    public string $end;

    public ?string $status = 'paid';
    public ?string $method = null;

    // antes: public ?int $professional_id = null;
    public ?int $partner_id = null;

    public ?int $insurance_id = null;

    public array $statusOptions = [
        ['id' => 'paid', 'name' => 'Pago'],
        ['id' => 'pending', 'name' => 'Pendente'],
        ['id' => 'failed', 'name' => 'Falhou'],
        ['id' => 'canceled', 'name' => 'Cancelado'],
    ];

    public array $methodOptions = [
        ['id' => 'cash', 'name' => 'Dinheiro'],
        ['id' => 'pix', 'name' => 'Pix'],
        ['id' => 'card', 'name' => 'Cartão'],
        ['id' => 'boleto', 'name' => 'Boleto'],
        ['id' => 'insurance', 'name' => 'Convênio'],
    ];

    // antes: $proOptions
    public array $partnerOptions = [];
    public array $insOptions = [];

    public array $headers = [
        ['key' => 'received_at', 'label' => 'Recebido em'],
        ['key' => 'method', 'label' => 'Método'],
        ['key' => 'payment_status', 'label' => 'Status'],
        ['key' => 'patient', 'label' => 'Paciente'],
        // antes: professional -> agora partner
        ['key' => 'partner', 'label' => 'Profissional'],
        ['key' => 'treatment', 'label' => 'Tratamento'],
        ['key' => 'insurance', 'label' => 'Convênio'],
        ['key' => 'amount_paid', 'label' => 'Pago', 'format' => ['currency', '2,.', 'R$ ']],
        ['key' => 'applied_to_due', 'label' => 'Aplicado', 'format' => ['currency', '2,.', 'R$ ']],
        ['key' => 'surcharge_amount', 'label' => 'Juros/Extra', 'format' => ['currency', '2,.', 'R$ ']],
    ];

    public array $rows = [];

    public function mount(): void
    {
        $this->start = now()->startOfMonth()->toDateTimeString();
        $this->end   = now()->endOfDay()->toDateTimeString();

        if (class_exists(\App\Models\Partner::class)) {
            $this->partnerOptions = \App\Models\Partner::query()
                ->select('id', 'name')->orderBy('name')->get()->toArray();
        }
        if (class_exists(\App\Models\Insurance::class)) {
            $this->insOptions = \App\Models\Insurance::query()
                ->select('id', 'name')->orderBy('name')->get()->toArray();
        }

        $this->loadRows();
    }

    public function updatedStart(){ $this->loadRows(); }
    public function updatedEnd(){ $this->loadRows(); }
    public function updatedStatus(){ $this->loadRows(); }
    public function updatedMethod(){ $this->loadRows(); }

    // antes: updatedProfessionalId
    public function updatedPartnerId(){ $this->loadRows(); }
    public function updatedInsuranceId(){ $this->loadRows(); }

    private function loadRows(): void
    {
        $svc = app(ReportsService::class);

        $filters = [
            'status'      => $this->status,
            'method'      => $this->method,
            'partner_id'  => $this->partner_id,
            'insurance_id'=> $this->insurance_id,
        ];

        $rows = $svc->datasetPayments($this->start, $this->end, $filters);


        foreach ($rows as &$r) {
            $r['received_at'] = $r['received_at']
                ? \Illuminate\Support\Carbon::parse($r['received_at'])->format('d/m/Y H:i')
                : '—';
        }
        $this->rows = $rows;
    }

    public function render()
    {
        return view('livewire.admin.reports-finance')->title('Relatórios Financeiros');
    }
}
