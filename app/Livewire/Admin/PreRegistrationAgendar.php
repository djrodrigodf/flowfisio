<?php

namespace App\Livewire\Admin;

use App\Models\Partner;
use App\Models\PreRegistration;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mary\Traits\Toast;

class PreRegistrationAgendar extends Component
{
    use Toast;

    public ?int $id = null;

    public ?PreRegistration $registration = null;

    public string $scheduled_at = '';

    public bool $showAgendarModal = false;

    public string $selectedDate = '';

    public string $selectedTime = '';

    public array $availableTimes = [];

    public $professional_id;

    public $profissionaisAnamnese;

    protected $rules = [
        'scheduled_at' => 'required|date_format:Y-m-d\TH:i',
    ];

    protected $listeners = ['abrirAgendamento'];

    public function updatedSelectedDate()
    {
        if (! $this->selectedDate) {
            $this->availableTimes = [];

            return;
        }

        $partnerId = $this->professional_id;
        $dayOfWeek = strtolower(\Carbon\Carbon::parse($this->selectedDate)->englishDayOfWeek); // ex: monday

        $schedule = \App\Models\PartnerSchedule::where('partner_id', $partnerId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (! $schedule) {
            $this->availableTimes = [];

            return;
        }

        // Gera intervalos de 40min
        $start = \Carbon\Carbon::parse($this->selectedDate.' '.$schedule->start_time);
        $end = \Carbon\Carbon::parse($this->selectedDate.' '.$schedule->end_time);

        $allTimes = [];
        while ($start->lt($end)) {
            $allTimes[] = $start->format('H:i');
            $start->addMinutes(40);
        }

        // Horários já ocupados pelo profissional nesse dia
        $busyTimes = PreRegistration::whereDate('scheduled_at', $this->selectedDate)
            ->where('professional_id', $partnerId)
            ->pluck('scheduled_at')
            ->map(fn ($time) => \Carbon\Carbon::parse($time)->format('H:i'));

        // Remove os ocupados
        $this->availableTimes = collect($allTimes)->diff($busyTimes)->values()->toArray();
    }

    public function abrirAgendamento(int $id): void
    {
        $this->id = $id;
        $this->registration = PreRegistration::with('link')->findOrFail($id);

        $this->profissionaisAnamnese = Partner::whereHas('role', function ($query) {
            $query->where('is_specialty', true)
                ->where('name', $this->registration->link->specialty);
        })->where('is_anamnese', true)->get();

        $this->scheduled_at = now()->format('Y-m-d\TH:i');
        $this->showAgendarModal = true;
    }

    public function agendar()
    {
        $this->validate([
            'selectedDate' => 'required|date',
            'selectedTime' => 'required',
        ]);

        $datetime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "{$this->selectedDate} {$this->selectedTime}");

        $this->registration->update([
            'scheduled_at' => $datetime,
            'scheduled_by' => Auth::id(),
            'professional_id' => $this->professional_id,
            'status' => 'agendado',
        ]);

        $this->toast('success', 'Pré-cadastro agendado com sucesso!');
        $this->dispatch('fecharAgendamento');
        $this->showAgendarModal = false;
        $this->dispatch('refreshComponent');
        $this->reset();
    }

    public function render()
    {
        return view('livewire.admin.pre-registration-agendar');
    }
}
