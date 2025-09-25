<?php

namespace App\Console\Commands;

use App\Services\ReportsService;
use Illuminate\Console\Command;
use SplFileObject;

class ReportsAppointments extends Command
{
    protected $signature = 'reports:appointments
        {--start= : Início (Y-m-d) [default: 1º dia do mês]}
        {--end=   : Fim (Y-m-d) [default: hoje]}
        {--status= : Filtrar status do agendamento}
        {--financial= : Filtrar financial_status}
        {--pro= : ID do profissional}
        {--patient= : ID do paciente}
        {--treatment= : ID do tratamento}
        {--insurance= : ID do convênio}
        {--location= : ID da unidade}
        {--room= : ID da sala}
        {--path= : pasta de saída [default: storage/app/exports]}
        {--file= : nome do arquivo (opcional)}
    ';

    protected $description = 'Exporta appointments detalhados (linha a linha) em CSV.';

    public function handle(ReportsService $rep): int
    {
        $start = $this->option('start') ?: now()->startOfMonth()->toDateString();
        $end = $this->option('end') ?: now()->toDateString();

        $filters = [
            'status' => $this->option('status') ?: null,
            'financial_status' => $this->option('financial') ?: null,
            'professional_id' => $this->option('pro') ? (int) $this->option('pro') : null,
            'patient_id' => $this->option('patient') ? (int) $this->option('patient') : null,
            'treatment_id' => $this->option('treatment') ? (int) $this->option('treatment') : null,
            'insurance_id' => $this->option('insurance') ? (int) $this->option('insurance') : null,
            'location_id' => $this->option('location') ? (int) $this->option('location') : null,
            'room_id' => $this->option('room') ? (int) $this->option('room') : null,
        ];

        $rows = $rep->datasetAppointments($start, $end, $filters);
        if (empty($rows)) {
            $this->warn('Nenhum registro no período/filtros.');

            return self::SUCCESS;
        }

        $dir = $this->option('path') ?: storage_path('app/exports');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fname = $this->option('file') ?: "appointments_{$start}_{$end}.csv";
        $fp = new SplFileObject(rtrim($dir, '/').'/'.$fname, 'w');

        $headers = [
            'appointment_id', 'date', 'start_time', 'end_time', 'status', 'financial_status',
            'patient', 'professional', 'treatment', 'insurance', 'insurance_code', 'location', 'room',
            'price_final', 'paid_total', 'payout_snapshot',
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
