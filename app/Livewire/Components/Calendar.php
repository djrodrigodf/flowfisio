<?php

namespace App\Livewire\Components;

use App\Models\Partner;
use App\Models\PreRegistration;
use Carbon\Carbon;
use Livewire\Component;

class Calendar extends Component
{
    public array $events = [];
    public $selectedDate;
    public $selectedWeek;
    public \Illuminate\Support\Collection $weekDays;
    public \Illuminate\Support\Collection $slots;
    public $professionals = [];
    public ?int $selectedProfessional = null;
    public string $viewMode = 'week'; // 'day' or 'week'


    public function updatedSelectedProfessional($value)
    {
        $this->selectedProfessional = $value;
        $this->loadDate($this->selectedDate);
    }
    protected function toJsEvents(): string
    {
        return json_encode($this->events);
    }

    public function updatedViewMode($value)
    {
        $this->viewMode = $value;

        if ($this->viewMode === 'week') {
            $this->selectedWeek = now()->format('o-\WW');
            $this->selectedDate = now()->startOfWeek()->format('Y-m-d');
            $this->loadDate(now()->startOfWeek());
        } elseif ($this->viewMode === 'day') {
            $this->selectedDate = now()->format('Y-m-d');
            $this->loadDate($this->selectedDate);
        }
    }

    public function updatedSelectedDate($value)
    {

        $this->selectedDate = Carbon::parse($value)->format("Y-m-d");
        $this->selectedDate = now()->startOfWeek()->format('Y-m-d');
        $this->loadDate($this->selectedDate);
    }

    public function updatedSelectedWeek($value)
    {
        // Extrai o ano e a semana da string (ex: 2025-W29)
        [$year, $week] = explode('-W', $value);
        $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $this->selectedWeek = $value;
        $this->loadDate($startOfWeek);
    }

    public function redirecionar($id) {
        $this->redirect(route('admin.pre-registration.show', $id['id']));
    }

    public function mount() {

        $this->professionals = Partner::whereHas('role', function ($query) {
            $query->where('is_specialty', true);
        })->get();

        if ($this->viewMode === 'week') {
            $this->selectedWeek = now()->format('o-\WW');
            $this->selectedDate = now()->startOfWeek()->format('Y-m-d');
            $this->loadDate(now()->startOfWeek());
        }
        if ($this->viewMode === 'day') {
            $this->selectedWeek = now()->format('o-\WW');
            $this->selectedDate = now()->format('Y-m-d');
            $this->loadDate($this->selectedDate);
        }
    }

    public function loadEvents()
    {
        $query = PreRegistration::where('status', 'agendado')
            ->whereNotNull('scheduled_at');

        if ($this->selectedProfessional) {

            $query->where('professional_id', $this->selectedProfessional);
        }

        $this->events = $query->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->child_name,
                    'start' => $item->scheduled_at,
                    'extendedProps' => [
                        'responsible' => $item->responsible_name,
                        'specialty' => $item->link->specialty ?? 'nada',
                        'professional' => $item->professional_id ? $item->professional->name : 'Não informado',
                    ],
                ];
            })->toArray();

    }

    public function loadDate($startDate)
    {
        $this->loadEvents();

        if ($this->viewMode === 'day') {
            $this->weekDays = collect([Carbon::parse($startDate)]);
        } else {
            $startOfWeek = Carbon::parse($startDate)->startOfWeek();
            $this->weekDays = collect(range(1, 5))->map(fn($i) => $startOfWeek->copy()->addDays($i));
        }

        // Slots de 40min entre 08h e 18h, sem 12h-14h
        $this->slots = collect();
        $current = Carbon::createFromTime(8, 0);
        while ($current->lessThan(Carbon::createFromTime(18, 0))) {
            if ($current->between(Carbon::createFromTime(12, 0), Carbon::createFromTime(13, 59), false)) {
                $current->addMinutes(40);
                continue;
            }
            $this->slots->push($current->copy());
            $current->addMinutes(40);
        }
    }

    public function render()
    {
        return view('livewire.components.calendar', [
            'jsonEvents' => $this->events,
        ]);
    }
}
