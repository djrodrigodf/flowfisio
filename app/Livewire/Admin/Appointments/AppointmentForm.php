<?php

namespace App\Livewire\Admin\Appointments;

use App\Domain\Pricing\Services\ResolvePrice;
use App\Models\Appointment;
use App\Models\Insurance;
use App\Models\Location;
use App\Models\Partner;
use App\Models\Patient;
use App\Models\Restriction;
use App\Models\Room;
use App\Models\Treatment;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class AppointmentForm extends Component
{
    use Toast;

    #[Validate('required|exists:patients,id')]
    public ?int $patient_id = null;

    #[Validate('required|exists:partners,id')]
    public ?int $partner_id = null;

    #[Validate('required|exists:locations,id')]
    public ?int $location_id = null;

    #[Validate('nullable|exists:rooms,id')]
    public ?int $room_id = null;

    #[Validate('required|exists:treatments,id')]
    public ?int $treatment_id = null;

    #[Validate('nullable|exists:insurances,id')]
    public ?int $insurance_id = null;

    #[Validate('nullable|date_format:Y-m-d H:i:s')]
    public ?string $start_at = null; // <x-datetime> (type="datetime-local")

    public array $patientOptions = [];

    public array $partnerOptions = [];

    public array $locationOptions = [];

    public array $roomOptions = [];

    public array $treatmentOptions = [];

    public array $insuranceOptions = [];

    // Data & seleção de horário
    public ?string $date = null;        // 'Y-m-d'

    public ?string $time = null;        // 'H:i'

    public array $timeSlots = [];

    // Hardcoded por enquanto
    protected int $slotDurationMin = 40;

    protected string $workStart = '08:00';

    protected string $workEnd = '18:00';

    protected string $lunchStart = '12:00';

    protected string $lunchEnd = '14:00';

    public ?string $end_at = null; // 'Y-m-d H:i:s'

    public function mount(): void
    {
        $this->patientOptions = Patient::select('id', 'name')->orderBy('name')->get()->toArray();

        $this->partnerOptions = Partner::whereHas('role', fn ($q) => $q->where('is_specialty', 1))
            ->select('id', 'name')->orderBy('name')->get()->toArray();

        $this->insuranceOptions = class_exists(Insurance::class)
            ? Insurance::select('id', 'name')->orderBy('name')->get()->toArray()
            : [];

        // location/room/treatment ficam dinâmicos conforme o partner/location
    }

    /** Ao trocar o partner, carregamos locais e tratamentos permitidos */
    public function updatedPartnerId($id): void
    {
        $this->location_id = null;
        $this->room_id = null;
        $this->treatment_id = null;

        if (! $id) {
            $this->locationOptions = $this->roomOptions = $this->treatmentOptions = [];
            $this->refreshTreatmentsForPartner($id);

            return;
        }

        $partner = Partner::with(['locations:id,name', 'treatments:id,name'])->find($id);
        $this->locationOptions = $partner?->locations?->toArray() ?? [];
        $this->treatmentOptions = $partner?->treatments?->toArray() ?? [];
        $this->generateTimeSlots();
    }

    public function updatedRoomId()
    {
        $this->generateTimeSlots();
    }

    private function refreshTreatmentsForPartner(?int $partnerId): void
    {
        if (! $partnerId) {
            $this->treatmentOptions = [];

            return;
        }

        $partner = \App\Models\Partner::with('treatments', 'role')->find($partnerId);
        if (! $partner) {
            $this->treatmentOptions = [];

            return;
        }

        // 1) se o parceiro tiver tratamentos explícitos, usa esses
        $tQuery = $partner->treatments()->select('treatments.id', 'treatments.name')->orderBy('name');

        if ($tQuery->exists()) {
            $this->treatmentOptions = $tQuery->get()->toArray();

            return;
        }

        // 2) caso contrário, usa os da especialidade (role_treatment)
        $roleId = $partner->role_id;
        $ids = \DB::table('role_treatment')->where('role_id', $roleId)->pluck('treatment_id');
        if ($ids->count() > 0) {
            $this->treatmentOptions = \App\Models\Treatment::whereIn('id', $ids)->select('id', 'name')->orderBy('name')->get()->toArray();

            return;
        }

        // 3) fallback (se não houver mapeamento): lista todos
        $this->treatmentOptions = \App\Models\Treatment::select('id', 'name')->orderBy('name')->get()->toArray();
    }

    /** Ao trocar a unidade, filtramos as salas */
    public function updatedLocationId($id): void
    {
        $this->room_id = null;
        $this->roomOptions = $id
            ? Room::where('location_id', $id)->where('active', 1)->select('id', 'name')->orderBy('name')->get()->toArray()
            : [];
        $this->generateTimeSlots();
    }

    public function selectTime(string $hhmm): void
    {
        if (! $this->date) {
            return;
        }

        $start = \Carbon\Carbon::parse($this->date.' '.$hhmm.':00');
        $end = (clone $start)->addMinutes($this->slotDurationMin);

        // segurança: só aceita se ainda estiver disponível
        if (! $this->isSlotAvailable($start, $end)) {
            return;
        }

        $this->time = $hhmm;
        $this->start_at = $start->format('Y-m-d H:i:s');
        $this->end_at = $end->format('Y-m-d H:i:s');
    }

    private function generateTimeSlots(): void
    {
        $this->timeSlots = [];

        if (! $this->date) {
            return;
        }

        $dayStart = \Carbon\Carbon::parse($this->date.' 00:00:00');
        $dayEnd = \Carbon\Carbon::parse($this->date.' 23:59:59');

        $workStart = \Carbon\Carbon::parse($this->date.' '.$this->workStart.':00');
        $workEnd = \Carbon\Carbon::parse($this->date.' '.$this->workEnd.':00');

        $lunchStart = \Carbon\Carbon::parse($this->date.' '.$this->lunchStart.':00');
        $lunchEnd = \Carbon\Carbon::parse($this->date.' '.$this->lunchEnd.':00');

        // varre de 40 em 40
        for ($t = $workStart->copy(); $t->lt($workEnd); $t->addMinutes($this->slotDurationMin)) {
            $slotStart = $t->copy();
            $slotEnd = $t->copy()->addMinutes($this->slotDurationMin);

            // não passar do fim do expediente
            if ($slotEnd->gt($workEnd)) {
                break;
            }

            // pular almoço (se sobrepõe qualquer parte do intervalo)
            $overlapsLunch = $slotStart->lt($lunchEnd) && $slotEnd->gt($lunchStart);
            if ($overlapsLunch) {
                continue;
            }

            $available = $this->isSlotAvailable($slotStart, $slotEnd);

            $this->timeSlots[] = [
                'time' => $slotStart->format('H:i'),
                'available' => $available,
            ];
        }
    }

    private function isSlotAvailable(\Carbon\Carbon $start, \Carbon\Carbon $end): bool
    {
        $conflictAppt = Appointment::query()
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->when($this->partner_id || $this->room_id, function ($q) {
                $q->where(function ($q) {
                    if ($this->partner_id) {
                        $q->orWhere('partner_id', $this->partner_id);
                    }
                    if ($this->room_id) {
                        $q->orWhere('room_id', $this->room_id);
                    }
                });
            })
            ->exists();

        if ($conflictAppt) {
            return false;
        }

        // Restrições
        if (class_exists(\App\Models\Restriction::class)) {
            $conflictRestr = \App\Models\Restriction::query()
                ->active()
                ->overlapping($start, $end)
                ->where(function ($q) {
                    $q->where('scope', 'global');
                    if ($this->location_id) {
                        $q->orWhere(fn ($w) => $w->where('scope', 'location')->where('scope_id', $this->location_id));
                    }
                    if ($this->room_id) {
                        $q->orWhere(fn ($w) => $w->where('scope', 'room')->where('scope_id', $this->room_id));
                    }
                    if ($this->partner_id) {
                        $q->orWhere(fn ($w) => $w->where('scope', 'professional')->where('scope_id', $this->partner_id)); // use 'professional'
                    }
                })
                ->exists();

            if ($conflictRestr) {
                return false;
            }
        }

        return true;
    }

    public function save(ResolvePrice $resolver): void
    {
        // validações essenciais
        $this->validate([
            'patient_id' => 'required|exists:patients,id',
            'partner_id' => 'required|exists:partners,id',
            'location_id' => 'required|exists:locations,id',
            'treatment_id' => 'required|exists:treatments,id',
            'start_at' => 'required|date_format:Y-m-d H:i:s', // <- obrigatório no formato que você está salvando
            'room_id' => 'nullable|exists:rooms,id',
            'insurance_id' => 'nullable|exists:insurances,id',
        ]);

        $start = Carbon::parse($this->start_at);

        // busca preços/duração pela engine
        $pricing = $resolver->handle(
            treatmentId: $this->treatment_id,
            insuranceId: $this->insurance_id,
            locationId: $this->location_id,
            partnerId: $this->partner_id,
            date: $start
        );

        // FALLBACK: tratamento base
        $treatment = Treatment::select('id', 'valor_base')->findOrFail($this->treatment_id);

        $duration = (int) ($pricing['duration_min'] ?? $treatment->duration_min ?? 40);
        $end = (clone $start)->addMinutes($duration);
        $this->end_at = $end->format('Y-m-d H:i:s');

        $price = $pricing['price'] ?? 0;
        $repasseType = $pricing['repasse_type'] ?? null;
        $repasseValue = $pricing['repasse_value'] ?? null;
        $tableId = $pricing['table_id'] ?? null;

        // conflitos com agenda/sala
        if (Appointment::where('partner_id', $this->partner_id)->overlapping($start, $end)->exists()) {
            $this->error('Conflito de horário com o profissional.');

            return;
        }
        if ($this->room_id && Appointment::where('room_id', $this->room_id)->overlapping($start, $end)->exists()) {
            $this->error('Conflito de horário com a sala.');

            return;
        }

        // restrições (note o scope 'professional' que você já usa no resto do app)
        $hasRestriction = Restriction::active()
            ->overlapping($start, $end)
            ->where(function ($q) {
                $q->where('scope', 'global')
                    ->orWhere(fn ($w) => $w->where('scope', 'location')->where('scope_id', $this->location_id))
                    ->orWhere(fn ($w) => $w->where('scope', 'room')->where('scope_id', $this->room_id))
                    ->orWhere(fn ($w) => $w->where('scope', 'professional')->where('scope_id', $this->partner_id));
            })
            ->exists();

        if ($hasRestriction) {
            $this->error('Existe uma restrição para este período/escopo.');

            return;
        }

        // persistência com snapshot financeiro preenchido
        Appointment::create([
            'patient_id' => $this->patient_id,
            'partner_id' => $this->partner_id,
            'treatment_id' => $this->treatment_id,
            'insurance_id' => $this->insurance_id,
            'location_id' => $this->location_id,
            'room_id' => $this->room_id,
            'duration_min' => $duration,
            'price' => $price,
            'repasse_type' => $repasseType,
            'repasse_value' => $repasseValue,
            'treatment_table_id' => $tableId,
            'created_by' => auth()->id(),
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'status' => 'scheduled',
        ]);

        $this->success('Agendamento criado!', redirectTo: route('admin.appointments.index'));
    }

    public function render()
    {
        return view('livewire.admin.appointments.appointment-form')->title('Novo Agendamento');
    }
}
