<?php

namespace App\Console\Commands;

use App\Services\BiService;
use Illuminate\Console\Command;
use SplFileObject;

class ReportsExport extends Command
{
    protected $signature = 'reports:export
        {--type=daily-revenue : Tipo (daily-revenue|attendance|top-treatments|top-professionals|top-insurances|outstanding)}
        {--start= : Data inicial (Y-m-d). Padrão: início do mês}
        {--end= : Data final (Y-m-d). Padrão: hoje}
        {--limit=10 : Limite para tops/outstanding}
        {--path= : Caminho de saída (padrão: storage/app/exports)}
    ';

    protected $description = 'Exporta relatórios BI em CSV para storage/app/exports.';

    public function handle(BiService $bi): int
    {
        $start = $this->option('start') ?: now()->startOfMonth()->toDateString();
        $end = $this->option('end') ?: now()->toDateString();
        $type = $this->option('type');
        $limit = (int) $this->option('limit');
        $dir = $this->option('path') ?: storage_path('app/exports');

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        switch ($type) {
            case 'daily-revenue':
                $data = $bi->seriesRevenue($start, $end);
                $headers = ['date', 'applied', 'surcharge', 'total'];
                $fname = "daily_revenue_{$start}_{$end}.csv";
                break;
            case 'attendance':
                $data = $bi->seriesAttendance($start, $end);
                $headers = ['date', 'attended', 'no_show', 'total', 'attendance_rate'];
                $fname = "attendance_{$start}_{$end}.csv";
                break;
            case 'top-treatments':
                $top = $bi->topTreatments($start, $end, $limit);
                // Gerar dois CSVs: by_revenue e by_volume
                $this->exportCsv($dir, "top_treatments_revenue_{$start}_{$end}.csv", ['id', 'name', 'revenue'], $top['by_revenue']);
                $this->exportCsv($dir, "top_treatments_volume_{$start}_{$end}.csv", ['id', 'name', 'qty'], $top['by_volume']);
                $this->info("OK: exports em {$dir}");

                return self::SUCCESS;
            case 'top-professionals':
                $top = $bi->topProfessionals($start, $end, $limit);
                $this->exportCsv($dir, "top_professionals_revenue_{$start}_{$end}.csv", ['id', 'name', 'revenue'], $top['by_revenue']);
                $this->exportCsv($dir, "top_professionals_volume_{$start}_{$end}.csv", ['id', 'name', 'qty'], $top['by_volume']);
                $this->info("OK: exports em {$dir}");

                return self::SUCCESS;
            case 'top-insurances':
                $top = $bi->topInsurances($start, $end, $limit);
                $this->exportCsv($dir, "top_insurances_revenue_{$start}_{$end}.csv", ['id', 'name', 'code', 'revenue'], $top['by_revenue']);
                $this->exportCsv($dir, "top_insurances_volume_{$start}_{$end}.csv", ['id', 'name', 'code', 'qty'], $top['by_volume']);
                $this->info("OK: exports em {$dir}");

                return self::SUCCESS;
            case 'outstanding':
                $data = $bi->outstandingByPatient($end, $limit);
                $headers = ['patient_id', 'patient_name', 'due'];
                $fname = "outstanding_by_patient_{$end}.csv";
                break;
            default:
                $this->error('Tipo inválido.');

                return self::FAILURE;
        }

        $this->exportCsv($dir, $fname, $headers, $data);
        $this->info("OK: {$dir}/{$fname}");

        return self::SUCCESS;
    }

    private function exportCsv(string $dir, string $filename, array $headers, array $rows): void
    {
        $file = new SplFileObject(rtrim($dir, '/').'/'.$filename, 'w');
        $file->fputcsv($headers, ';');

        foreach ($rows as $row) {
            // força ordem conforme headers
            $line = [];
            foreach ($headers as $h) {
                $line[] = is_bool($row[$h] ?? null) ? (int) $row[$h] : ($row[$h] ?? null);
            }
            $file->fputcsv($line, ';');
        }
    }
}
