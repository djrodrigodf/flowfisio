<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Insurance;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Treatment;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\Table;

class MassAppointments extends Command
{
    protected $signature = 'appointments:mass
        {--pro= : ID ou email do profissional (padrão: ana@flowfisio.test)}
        {--date= : Data inicial (Y-m-d). Padrão: hoje}
        {--days=1 : Quantidade de dias a preencher}
        {--treatment= : ID ou slug do tratamento (padrão: sessao-de-fisioterapia-ortopedica)}
        {--insurances= : Lista separada por vírgula. Ex.: PARTICULAR,UNIMED (opcional)}
        {--room-id= : Forçar uma sala específica (senão usa a do schedule)}
        {--limit= : Limitar número de agendamentos a criar}
        {--create-patients= : Criar N pacientes fake se não houver pacientes}
        {--notes= : Observação para os agendamentos (padrão: "Mass seeder")}
        {--dry-run : Não grava nada; apenas simula}
        {--cleanup : Remove os agendamentos criados ao final}';

    protected $description = 'Popula a agenda com agendamentos em massa para testes (M4).';

    private array $createdIds = [];

    public function handle(AvailabilityService $avail, AppointmentService $apptSvc): int
    {
        // -------- Resolver parâmetros --------
        $proKey = $this->option('pro') ?? 'ana@flowfisio.test';
        $pro = $this->resolveProfessional($proKey);
        if (! $pro) {
            $this->error("Profissional não encontrado para --pro={$proKey}");

            return self::FAILURE;
        }

        $startDate = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : now()->toDateString();
        $days = max(1, (int) ($this->option('days') ?? 1));
        $endDate = Carbon::parse($startDate)->addDays($days - 1)->toDateString();

        $treatmentKey = $this->option('treatment') ?? 'sessao-de-fisioterapia-ortopedica';
        $treatment = $this->resolveTreatment($treatmentKey);
        if (! $treatment) {
            $this->error("Tratamento não encontrado para --treatment={$treatmentKey}");

            return self::FAILURE;
        }

        $insCodes = $this->parseListOption($this->option('insurances'));
        $insList = $this->resolveInsurances($insCodes); // array de Insurance
        $roomId = $this->option('room-id') ? (int) $this->option('room-id') : null;
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $notes = $this->option('notes') ?? 'Mass seeder';
        $dryRun = (bool) $this->option('dry-run');

        // Pacientes
        $patients = Patient::query()->inRandomOrder()->get(['id', 'insurance_id']);
        if ($patients->isEmpty() && $this->option('create-patients')) {
            $this->warn('Sem pacientes; criando pacientes fake...');
            $this->createFakePatients((int) $this->option('create-patients'));
            $patients = Patient::query()->inRandomOrder()->get(['id', 'insurance_id']);
        }
        if ($patients->isEmpty()) {
            $this->error('Nenhum paciente encontrado. Crie pacientes ou use --create-patients=N.');

            return self::FAILURE;
        }

        $this->line('');
        $this->info('=== Disponibilidade alvo ===');
        $this->comment("Profissional: {$pro->name} (ID {$pro->id}) | Período: {$startDate} → {$endDate} | Sala: ".($roomId ?: 'schedule-default'));
        $slots = $avail->getAvailability($pro->id, $startDate, $endDate, $roomId);
        $this->printSlotsPreview($slots);

        if (empty($slots)) {
            $this->warn('Nenhum slot disponível no período informado.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->info('=== Começando a preencher slots ===');
        $created = 0;
        $skipped = 0;
        $errors = 0;
        $patientIdx = 0;
        $insuranceIdx = 0;

        // Local padrão (primeira unidade, se existir)
        $locationId = Location::query()->value('id');

        foreach ($slots as $slot) {
            if ($limit !== null && $created >= $limit) {
                break;
            }

            $patient = $patients[$patientIdx % $patients->count()];
            $patientIdx++;

            // insurance: prioridade para lista informada; senão usa a do paciente
            $insurance = null;
            if (! empty($insList)) {
                $insurance = $insList[$insuranceIdx % count($insList)];
                $insuranceIdx++;
            } elseif ($patient->insurance_id) {
                $insurance = Insurance::find($patient->insurance_id);
            }

            // tentar criar
            try {
                if ($dryRun) {
                    $this->line("DRY-RUN: Criaria {$slot['start']} → {$slot['end']} | patient #{$patient->id} | ins=".($insurance?->code ?? 'NULL'));
                    $created++;

                    continue;
                }

                $appt = $apptSvc->create([
                    'patient_id' => $patient->id,
                    'professional_id' => $pro->id,
                    'treatment_id' => $treatment->id,
                    'insurance_id' => $insurance?->id,
                    'location_id' => $locationId,
                    'room_id' => $slot['room_id'] ?? $roomId,
                    'start_at' => $slot['start'],
                    'end_at' => $slot['end'],
                    'notes' => $notes,
                ]);

                $this->line("OK #{$appt->id}: {$appt->start_at} → {$appt->end_at} | patient #{$appt->patient_id} | price={$appt->price_final} | payout={$appt->payout_value_snapshot}");
                $this->createdIds[] = $appt->id;
                $created++;

            } catch (\Throwable $e) {
                // conflito inesperado ou validação
                $this->warn("SKIP: {$slot['start']} → {$slot['end']} | Motivo: ".$e->getMessage());
                $skipped++;
                $errors++;
            }
        }

        $this->line('');
        $this->info('=== Resumo ===');
        $this->table(['Criados', 'Pulados', 'Erros', 'Dry-run', 'Período'],
            [[$created, $skipped, $errors, $dryRun ? 'sim' : 'não', "{$startDate} → {$endDate}"]]
        );

        if ($this->option('cleanup') && ! empty($this->createdIds) && ! $dryRun) {
            $this->warn('Limpando agendamentos criados...');
            Appointment::whereIn('id', $this->createdIds)->delete();
            $this->info('Limpeza concluída.');
        }

        $this->line('');
        $this->info('✅ Mass appointments finalizado.');

        return self::SUCCESS;
    }

    // ---------------- Helpers ----------------

    private function resolveProfessional(string $key): ?Professional
    {
        if (ctype_digit($key)) {
            return Professional::find((int) $key);
        }

        return Professional::where('email', $key)->orWhere('id', $key)->first();
    }

    private function resolveTreatment(string $key): ?Treatment
    {
        if (ctype_digit($key)) {
            return Treatment::find((int) $key);
        }

        return Treatment::where('slug', $key)->orWhere('id', $key)->first();
    }

    private function parseListOption($opt): array
    {
        if (! $opt) {
            return [];
        }
        if (is_array($opt)) {
            return array_filter(array_map('trim', Arr::flatten([$opt])));
        }

        return array_filter(array_map('trim', explode(',', (string) $opt)));
    }

    /** @return Insurance[] */
    private function resolveInsurances(array $codesOrIds): array
    {
        if (empty($codesOrIds)) {
            return [];
        }
        $out = [];
        foreach ($codesOrIds as $k) {
            $ins = ctype_digit($k)
                ? Insurance::find((int) $k)
                : Insurance::where('code', $k)->orWhere('id', $k)->first();
            if ($ins) {
                $out[] = $ins;
            }
        }

        return array_values($out);
    }

    private function printSlotsPreview(array $slots): void
    {
        $this->comment('Total de slots livres: '.count($slots));
        $preview = array_slice($slots, 0, 12);
        if (empty($preview)) {
            return;
        }

        $rows = array_map(fn ($s) => [$s['start'], $s['end'], $s['room_id'] ?? '—'], $preview);

        (new Table($this->output))
            ->setHeaders(['Início', 'Fim', 'Sala'])
            ->setRows($rows)
            ->render();

        if (count($slots) > 12) {
            $this->line('... (mostrando só os 12 primeiros)');
        }
    }

    private function createFakePatients(int $n): void
    {
        // Gerador simples sem factories (para não depender de stubs)
        for ($i = 0; $i < $n; $i++) {
            \App\Models\Patient::create([
                'name' => 'Paciente Teste '.Str::padLeft((string) ($i + 1), 3, '0'),
                'gender' => ['M', 'F', 'O'][array_rand(['M', 'F', 'O'])],
                'phone' => '(31) 9'.rand(1000, 9999).'-'.rand(1000, 9999),
                'active' => true,
            ]);
        }
    }
}
