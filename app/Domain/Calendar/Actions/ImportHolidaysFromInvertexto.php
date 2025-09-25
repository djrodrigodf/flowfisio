<?php

namespace App\Domain\Calendar\Actions;

use App\Domain\Calendar\Exceptions\AlreadyImportedException;
use App\Models\Holiday;
use App\Models\HolidayImportLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportHolidaysFromInvertexto
{
    /**
     * @return array{created:int,updated:int,year:int,state:?string}
     *
     * @throws AlreadyImportedException
     */
    public function handle(
        int $year,
        ?string $state = null,
        bool $includeOptional = true,
        ?string $scopeType = null, // null|'location'|'room'|'professional'
        ?int $scopeId = null
    ): array {
        $cfg = config('holidays.invertexto');
        $state = $state ?: $cfg['state'];
        $token = $cfg['token'];
        if (blank($token)) {
            throw new \InvalidArgumentException('INVERTEXTO_TOKEN não configurado.');
        }

        // Evitar duplicidade por provider+ano+UF+escopo
        $exists = HolidayImportLog::query()
            ->where(compact('year', 'state'))
            ->where('provider', 'invertexto')
            ->where('scope', $scopeType)
            ->where('scope_id', $scopeId)
            ->exists();

        if ($exists) {
            throw new AlreadyImportedException("Feriados de {$year} (UF: {$state}) já importados para este escopo.");
        }

        $url = rtrim($cfg['base_url'], '/')."/v1/holidays/{$year}";
        $resp = Http::timeout($cfg['timeout'] ?? 15)
            ->acceptJson()
            ->get($url, ['token' => $token, 'state' => $state]);

        if (! $resp->ok()) {
            throw new \RuntimeException("Falha API Invertexto: HTTP {$resp->status()} — {$resp->body()}");
        }

        $items = $resp->json();
        if (! is_array($items)) {
            throw new \RuntimeException('Resposta da API inesperada (não é array).');
        }

        $created = 0;
        $updated = 0;

        // feriados móveis: não marcar como recorrentes
        $movable = ['Carnaval', 'Quarta-feira de Cinzas', 'Sexta-feira Santa', 'Corpus Christi'];

        DB::transaction(function () use (
            $items, $year, $state, $scopeType, $scopeId,
            $includeOptional, $movable, &$created, &$updated
        ) {
            foreach ($items as $it) {
                $date = data_get($it, 'date');       // 'YYYY-MM-DD'
                $name = trim((string) data_get($it, 'name', 'Feriado'));
                $kind = data_get($it, 'type');       // 'feriado'|'facultativo'
                if (! $date || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    continue;
                }

                if (! $includeOptional && $kind === 'facultativo') {
                    continue;
                }

                $dt = Carbon::createFromFormat('Y-m-d', $date);
                $isRecurring = ! in_array($name, $movable, true);

                // Chave estável por nome+data+escopo (global ou específico)
                $where = [
                    'name' => $name,
                    'date' => $dt->toDateString(),
                    'scope' => $scopeType,
                    'scope_id' => $scopeId,
                ];

                $payload = [
                    'is_recurring' => $isRecurring,
                    'active' => true,
                ];

                $row = Holiday::where($where)->first();
                if ($row) {
                    $row->fill($payload);
                    if ($row->isDirty()) {
                        $row->save();
                        $updated++;
                    }
                } else {
                    Holiday::create($where + $payload);
                    $created++;
                }
            }

            HolidayImportLog::create([
                'provider' => 'invertexto',
                'year' => $year,
                'state' => $state,
                'scope' => $scopeType,
                'scope_id' => $scopeId,
                'created_count' => $created,
                'updated_count' => $updated,
                'created_by' => auth()->id(),
            ]);
        });

        return compact('created', 'updated', 'year', 'state');
    }
}
