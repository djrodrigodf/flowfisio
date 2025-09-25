<?php

namespace App\Console\Commands;

use App\Services\ReportsService;
use Illuminate\Console\Command;
use SplFileObject;

class ReportsPayments extends Command
{
    protected $signature = 'reports:payments
        {--start= : Início recebimento (Y-m-d) [default: 1º dia do mês]}
        {--end=   : Fim recebimento (Y-m-d) [default: hoje]}
        {--status=paid : Status do pagamento (paid|pending|failed|canceled)}
        {--method= : Método (cash|pix|card|boleto|insurance)}
        {--pro= : ID do profissional}
        {--insurance= : ID do convênio}
        {--location= : ID da unidade}
        {--room= : ID da sala}
        {--min-amount= : Valor mínimo pago}
        {--path= : pasta de saída [default: storage/app/exports]}
        {--file= : nome do arquivo (opcional)}
    ';

    protected $description = 'Exporta payments detalhados (linha a linha) em CSV.';

    public function handle(ReportsService $rep): int
    {
        $start = $this->option('start') ?: now()->startOfMonth()->toDateString();
        $end = $this->option('end') ?: now()->toDateString();

        $filters = [
            'status' => $this->option('status') ?: null,
            'method' => $this->option('method') ?: null,
            'professional_id' => $this->option('pro') ? (int) $this->option('pro') : null,
            'insurance_id' => $this->option('insurance') ? (int) $this->option('insurance') : null,
            'location_id' => $this->option('location') ? (int) $this->option('location') : null,
            'room_id' => $this->option('room') ? (int) $this->option('room') : null,
            'min_amount' => $this->option('min-amount') ? (float) $this->option('min-amount') : null,
        ];

        $rows = $rep->datasetPayments($start, $end, $filters);
        if (empty($rows)) {
            $this->warn('Nenhum registro no período/filtros.');

            return self::SUCCESS;
        }

        $dir = $this->option('path') ?: storage_path('app/exports');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fname = $this->option('file') ?: "payments_{$start}_{$end}.csv";
        $fp = new SplFileObject(rtrim($dir, '/').'/'.$fname, 'w');

        $headers = [
            'payment_id', 'payment_status', 'method', 'amount', 'payment_discount', 'amount_paid',
            'applied_to_due', 'surcharge_amount', 'received_at', 'reference',
            'appointment_id', 'appointment_date', 'start_time', 'end_time', 'appointment_status', 'financial_status',
            'price_final', 'appt_discount', 'paid_total',
            'patient', 'professional', 'treatment', 'insurance', 'insurance_code', 'location', 'room',
        ];
        $fp->fputcsv($headers, ';');

        foreach ($rows as $r) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = $r[$h] ?? null;
            }
            $fp->fputcsv($line, ';');
        }

        $this->info("OK: {$dir}/{$fname} (".count($rows).' linhas)');

        return self::SUCCESS;
    }
}
