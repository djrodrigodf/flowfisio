<?php

namespace App\Livewire\Admin;

use App\Models\Appointment;
use App\Models\Location;
use App\Models\Partner;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Livewire\Component;

class AppointmentsCalendar extends Component
{
    /** Qualquer data dentro da semana selecionada (YYYY-mm-dd) */
    public string $week = '';

    public ?int $partner_id = null;

    public ?int $location_id = null;

    public ?int $room_id = null;

    /** @var array<string, array> Mapa: 'YYYY-mm-dd' => [eventos...] */
    public array $eventsByDay = [];

    /** Options [{id, name}] */
    public array $partnerOptions = [];

    public array $locationOptions = [];

    public array $roomOptions = [];

    public bool $showWeekend = false;

    public function mount(): void
    {
        $this->week = now()->format('o-\WW'); // ex: 2025-W39
        // Parceiros com role de especialidade
        $this->partnerOptions = Partner::query()
            ->whereHas('role', fn ($q) => $q->where('is_specialty', 1))
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->locationOptions = Location::query()
            ->where('active', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->roomOptions = Room::query()
            ->where('active', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->loadWeek();
    }

    public function updatedWeek(): void
    {
        $this->loadWeek();
    }

    public function updatedPartnerId(): void
    {
        $this->loadWeek();
    }

    public function updatedLocationId(): void
    {
        $this->loadWeek();
    }

    public function updatedRoomId(): void
    {
        $this->loadWeek();
    }

    /** Dias da semana atual (seg-dom) com label compacto */
    public function getDaysProperty(): array
    {
        $start = $this->weekStart();
        $range = collect(range(0, 6))
            ->map(fn ($i) => $start->copy()->addDays($i));

        // Remover sáb/dom quando $showWeekend = false (independente de qual é o 1º dia)
        if (! $this->showWeekend) {
            $range = $range->reject(fn (Carbon $d) => $d->isWeekend()); // sáb/dom
        }

        return $range
            ->map(fn (Carbon $d) => [
                'iso' => $d->toDateString(),
                'label' => $d->translatedFormat('D, d/m'),
            ])
            ->all();
    }

    private function weekStart(): Carbon
    {
        $w = $this->week ?: now()->toDateString();

        // Quando vem do <input type="week">: "YYYY-Www"
        if (preg_match('/^\d{4}-W\d{2}$/', $w)) {
            [$year, $wk] = explode('-W', $w);

            // ISO week já é seg–dom; ainda assim, forçamos:
            return Carbon::now()->setISODate((int) $year, (int) $wk)->startOfWeek(Carbon::MONDAY);
        }

        // Fallback para datas simples
        return Carbon::parse($w)->startOfWeek(Carbon::MONDAY);
    }

    private function loadWeek(): void
    {
        $start = $this->weekStart();
        $end = (clone $start)->endOfWeek(Carbon::SUNDAY);

        $q = Appointment::query()
            ->with([
                'patient:id,name',
                'partner:id,name',
                'treatment:id,name',
                'location:id,name',
                'room:id,name',
            ])
            ->whereBetween('start_at', [$start, $end])
            ->orderBy('start_at');

        if ($this->partner_id) {
            $q->where('partner_id', $this->partner_id);
        }
        if ($this->location_id) {
            $q->where('location_id', $this->location_id);
        }
        if ($this->room_id) {
            $q->where('room_id', $this->room_id);
        }

        $this->eventsByDay = [];

        foreach ($q->get() as $a) {
            $key = $a->start_at->toDateString();

            $this->eventsByDay[$key][] = [
                'id' => $a->id,
                'time' => $a->start_at->format('H:i'),
                'end' => optional($a->end_at)->format('H:i'),
                'pac' => $a->patient?->name ?? '—',
                'pro' => $a->partner?->name ?? '—',
                'trat' => $a->treatment?->name ?? '—',
                'status' => $a->status,
            ];
        }
    }

    public function render()
    {
        return view('livewire.admin.appointments-calendar')
            ->title('Calendário');
    }
}
