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
use Symfony\Component\Console\Helper\Table;

class TestAppointments extends Command
{
    protected $signature = 'appointments:test
        {--pro= : ID do profissional ou e-mail (padrão: ana@flowfisio.test)}
        {--date= : Data inicial Y-m-d (padrão: hoje)}
        {--days=1 : Quantidade de dias a testar}
        {--patient-id= : ID do paciente (padrão: primeiro paciente)}
        {--treatment= : ID ou slug do tratamento (padrão: sessao-de-fisioterapia-ortopedica)}
        {--insurance= : ID ou code do convênio (opcional; p.ex. PARTICULAR)}
        {--location-id= : ID da unidade (opcional)}
        {--room-id= : ID da sala (opcional; senão usa a do schedule)}
        {--cleanup : Apaga os agendamentos criados ao final}';

    protected $description = 'Roteiro automatizado de testes do módulo de Agendamentos (M4)';

    private array $createdIds = [];

    public function handle(AvailabilityService $avail, AppointmentService $apptSvc): int
    {
        try {
            // ---------- Resolver parâmetros ----------
            $proKey = $this->option('pro') ?? 'ana@flowfisio.test';
            $pro = $this->resolveProfessional($proKey);
            if (! $pro) {
                $this->error("Profissional não encontrado para --pro={$proKey}");

                return self::FAILURE;
            }

            $startDate = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : now()->toDateString();
            $days = (int) $this->option('days') ?: 1;
            $endDate = Carbon::parse($startDate)->addDays($days - 1)->toDateString();

            $patientId = $this->option('patient-id') ?: Patient::query()->value('id');
            if (! $patientId) {
                $this->error('Nenhum paciente encontrado (crie/seed um paciente ou use --patient-id).');

                return self::FAILURE;
            }

            $treatmentKey = $this->option('treatment') ?? 'sessao-de-fisioterapia-ortopedica';
            $treatment = $this->resolveTreatment($treatmentKey);
            if (! $treatment) {
                $this->error("Tratamento não encontrado para --treatment={$treatmentKey}");

                return self::FAILURE;
            }

            $insurance = $this->resolveInsurance($this->option('insurance'));
            $locationId = $this->option('location-id') ? (int) $this->option('location-id') : (Location::query()->value('id') ?? null);
            $roomId = $this->option('room-id') ? (int) $this->option('room-id') : null;

            $this->line('');
            $this->info('=== Cenário: Listar disponibilidade ===');
            $this->comment("Profissional: {$pro->name} (ID {$pro->id}) | Período: {$startDate} → {$endDate} | Sala: ".($roomId ?: 'schedule-default'));

            $slots = $avail->getAvailability($pro->id, $startDate, $endDate, $roomId);
            $this->printSlots($slots);

            if (empty($slots)) {
                $this->warn('Nenhum slot disponível no período informado. Ajuste data/dias/sala e tente novamente.');

                return self::SUCCESS;
            }

            // Pega um slot do primeiro dia
            $slot = $slots[0];

            $this->line('');
            $this->info('=== Cenário: Cotar preço/repasse (sem desconto) ===');
            $quote = $apptSvc->quote($treatment->id, $insurance?->id, $slot['start']);
            $this->table(['Base', 'Final', 'Repasse'], [[
                number_format($quote['price_base'], 2, ',', '.'),
                number_format($quote['price_final'], 2, ',', '.'),
                number_format($quote['payout'], 2, ',', '.'),
            ]]);

            $this->line('');
            $this->info('=== Cenário: Criar agendamento no primeiro slot ===');
            $appt = $apptSvc->create([
                'patient_id' => $patientId,
                'professional_id' => $pro->id,
                'treatment_id' => $treatment->id,
                'insurance_id' => $insurance?->id,
                'location_id' => $locationId,
                'room_id' => $slot['room_id'] ?? $roomId,
                'start_at' => $slot['start'],
                'end_at' => $slot['end'],
                'notes' => 'Agendamento de teste via command.',
            ]);
            $this->createdIds[] = $appt->id;

            $this->info("Agendamento criado: #{$appt->id} {$appt->start_at} → {$appt->end_at}");
            $this->table(['Preço Base', 'Preço Final', 'Repasse'], [[
                $appt->price_base, $appt->price_final, $appt->payout_value_snapshot,
            ]]);

            $this->line('');
            $this->info('=== Cenário: Disponibilidade após criar (slot deve desaparecer) ===');
            $slotsAfter = $avail->getAvailability($pro->id, $startDate, $endDate, $roomId);
            $this->comment('Antes: '.count($slots).' | Depois: '.count($slotsAfter));

            // Cenário: tentar duplo-agendamento no mesmo slot (deve falhar)
            $this->line('');
            $this->info('=== Cenário: Duplo-agendamento (deve FALHAR) ===');
            try {
                $apptSvc->create([
                    'patient_id' => $patientId,
                    'professional_id' => $pro->id,
                    'treatment_id' => $treatment->id,
                    'insurance_id' => $insurance?->id,
                    'location_id' => $locationId,
                    'room_id' => $slot['room_id'] ?? $roomId,
                    'start_at' => $slot['start'],
                    'end_at' => $slot['end'],
                    'notes' => 'Tentativa de conflito (esperado falhar).',
                ]);
                $this->error('ERRO: O duplo-agendamento NÃO falhou (isso não deveria acontecer).');
            } catch (\Throwable $e) {
                $this->info('OK: Conflito detectado corretamente.');
                $this->comment($e->getMessage());
            }

            // Cenário: cotação com desconto percentual
            $this->line('');
            $this->info('=== Cenário: Cotar com desconto percentual (10%) ===');
            $qPercent = $apptSvc->quote($treatment->id, $insurance?->id, $slot['start'], 10, 'percent');
            $this->table(['Base', 'Final (-10%)', 'Repasse'], [[
                number_format($qPercent['price_base'], 2, ',', '.'),
                number_format($qPercent['price_final'], 2, ',', '.'),
                number_format($qPercent['payout'], 2, ',', '.'),
            ]]);

            // Cenário: cotação com desconto fixo
            $this->line('');
            $this->info('=== Cenário: Cotar com desconto fixo (R$ 20,00) ===');
            $qFixed = $apptSvc->quote($treatment->id, $insurance?->id, $slot['start'], 20, 'fixed');
            $this->table(['Base', 'Final (-20,00)', 'Repasse'], [[
                number_format($qFixed['price_base'], 2, ',', '.'),
                number_format($qFixed['price_final'], 2, ',', '.'),
                number_format($qFixed['payout'], 2, ',', '.'),
            ]]);

            // Cenário: tentar criar com horário inválido (fora dos slots)
            $this->line('');
            $this->info('=== Cenário: Criar fora de um slot válido (deve FALHAR) ===');
            $invalidStart = Carbon::parse($slot['start'])->subMinutes(5)->toDateTimeString();
            $invalidEnd = Carbon::parse($slot['end'])->subMinutes(5)->toDateTimeString();
            try {
                $apptSvc->create([
                    'patient_id' => $patientId,
                    'professional_id' => $pro->id,
                    'treatment_id' => $treatment->id,
                    'insurance_id' => $insurance?->id,
                    'location_id' => $locationId,
                    'room_id' => $slot['room_id'] ?? $roomId,
                    'start_at' => $invalidStart,
                    'end_at' => $invalidEnd,
                    'notes' => 'Horário inválido (esperado falhar).',
                ]);
                $this->error('ERRO: Criou fora do slot (isso não deveria acontecer).');
            } catch (\Throwable $e) {
                $this->info('OK: Rejeitou criar fora de slot válido.');
                $this->comment($e->getMessage());
            }

            // Cleanup opcional
            if ($this->option('cleanup') && ! empty($this->createdIds)) {
                $this->line('');
                $this->warn('=== Limpeza: removendo agendamentos criados ===');
                Appointment::whereIn('id', $this->createdIds)->delete();
                $this->info('Limpeza concluída.');
            }

            $this->line('');
            $this->info('✅ Teste concluído com sucesso.');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->line('');
            $this->error('Falha no teste: '.$e->getMessage());
            if (config('app.debug')) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    // --------- Helpers ---------

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

    private function resolveInsurance(?string $key): ?Insurance
    {
        if (! $key) {
            return null;
        }
        if (ctype_digit($key)) {
            return Insurance::find((int) $key);
        }

        return Insurance::where('code', $key)->orWhere('id', $key)->first();
    }

    private function printSlots(array $slots): void
    {
        $this->comment('Total de slots: '.count($slots));
        $preview = array_slice($slots, 0, 10);
        if (empty($preview)) {
            return;
        }

        $rows = array_map(function ($s) {
            return [$s['start'], $s['end'], $s['room_id'] ?? '—'];
        }, $preview);

        (new Table($this->output))
            ->setHeaders(['Início', 'Fim', 'Sala'])
            ->setRows($rows)
            ->render();

        if (count($slots) > 10) {
            $this->line('... (mostrando só os 10 primeiros)');
        }
    }
}
