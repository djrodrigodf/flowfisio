<?php

namespace App\Livewire\Admin;

use App\Services\BiService;
use Livewire\Component;

class BiDashboard extends Component
{
    public string $start;

    public string $end;

    public array $summary = [];

    public array $rev = [];

    public array $att = [];

    public function mount(BiService $bi): void
    {
        $this->start = now()->startOfMonth()->toDateString();
        $this->end   = now()->endOfMonth()->toDateString(); // ← antes era toDateString() do “hoje”

        $this->hydrateData($bi);
        $this->renderCharts();
    }

    public function updatedStart(): void
    {
        $this->refreshData();
    }

    public function updatedEnd(): void
    {
        $this->refreshData();
    }

    private function refreshData(): void
    {
        $bi = app(BiService::class);
        $this->hydrateData($bi);
        $this->renderCharts();
    }

    private function hydrateData(BiService $bi): void
    {
        $this->summary = $bi->summary($this->start, $this->end);
        $this->rev = $bi->seriesRevenue($this->start, $this->end);
        $this->att = $bi->seriesAttendance($this->start, $this->end);

    }

    private function renderCharts(): void
    {
        $rev = json_encode($this->rev);
        $att = json_encode($this->att);

        $this->js(<<<JS
            (function(){
                // Receita
                const r = {$rev};
                const rLabels = r.map(d=>d.date);
                const rApplied = r.map(d=>d.applied);
                const rSurcharge = r.map(d=>d.surcharge);
                const ctxR = document.getElementById('revChart')?.getContext('2d');
                if(ctxR){
                    if(window._revChart){ window._revChart.destroy(); }
                    window._revChart = new Chart(ctxR,{
                        type:'bar',
                        data:{ labels:rLabels, datasets:[
                            {label:'Aplicado', data:rApplied, stack:'rev'},
                            {label:'Juros/Extra', data:rSurcharge, stack:'rev'},
                        ]},
                        options:{ responsive:true, plugins:{legend:{position:'bottom'}}, scales:{x:{stacked:true}, y:{stacked:true}} }
                    });
                }

                // Presença
                const a = {$att};
                const aLabels = a.map(d=>d.date);
                const aAtt = a.map(d=>d.attended);
                const aNo = a.map(d=>d.no_show);
                const ctxA = document.getElementById('attChart')?.getContext('2d');
                if(ctxA){
                    if(window._attChart){ window._attChart.destroy(); }
                    window._attChart = new Chart(ctxA,{
                        type:'bar',
                        data:{ labels:aLabels, datasets:[
                            {label:'Atendidos', data:aAtt},
                            {label:'Faltas', data:aNo},
                        ]},
                        options:{ responsive:true, plugins:{legend:{position:'bottom'}} }
                    });
                }
            })();
        JS);
    }

    public function render()
    {
        return view('livewire.admin.bi-dashboard')->title('Dashboard BI');
    }
}
