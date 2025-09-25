<?php

namespace App\Livewire\Admin;

use App\Models\PreRegistration;
use Livewire\Component;

class Dashboard extends Component
{
    public array $chartCadastro = [];

    public string $mesSelecionado; // formato: '2024-07'

    public array $mesesDisponiveis = [];

    public array $chartStatusPreCadastro = [];

    public function mount()
    {
        $preCadastrosPorDia = PreRegistration::query()
            ->whereDate('created_at', '>=', now()->subDays(6))
            ->get()
            ->groupBy(fn ($item) => $item->created_at->format('d/m'))
            ->sortKeys() // garante ordem correta
            ->map(fn ($group) => $group->count());

        $this->chartCadastro = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($preCadastrosPorDia->toArray()),
                'datasets' => [[
                    'label' => 'Cadastros',
                    'data' => array_values($preCadastrosPorDia->toArray()),
                    'backgroundColor' => '#3b82f6',
                ]],
            ],
        ];

        // Formato inicial: mês atual
        $this->mesSelecionado = now()->format('Y-m');

        // Buscar todos os meses com dados
        $this->mesesDisponiveis = PreRegistration::query()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as mes, DATE_FORMAT(created_at, "%m/%Y") as label')
            ->groupBy('mes', 'label')
            ->orderByDesc('mes')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->mes,
                'name' => $item->label,
            ])
            ->toArray();

        $this->atualizarGraficoStatus();

    }

    public function atualizarGraficoStatus()
    {
        [$ano, $mes] = explode('-', $this->mesSelecionado);

        $statusCounts = PreRegistration::query()
            ->whereYear('created_at', $ano)
            ->whereMonth('created_at', $mes)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $this->chartStatusPreCadastro = [
            'type' => 'pie',
            'data' => [
                'labels' => ['Aguardando', 'Agendado', 'Cancelado', 'Concluído'],
                'datasets' => [[
                    'data' => [
                        $statusCounts['aguardando'] ?? 0,
                        $statusCounts['agendado'] ?? 0,
                        $statusCounts['cancelado'] ?? 0,
                        $statusCounts['concluido'] ?? 0,
                    ],
                    'backgroundColor' => ['#fbbf24', '#3b82f6', '#ef4444', '#22c55e'],
                ]],
            ],
        ];
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'agendaHoje' => PreRegistration::with(['professional'])->whereDate('scheduled_at', now())->orderBy('scheduled_at')->get(),
            'totalPreCadastros' => PreRegistration::count(),
            'preCadastrosHoje' => PreRegistration::whereDate('scheduled_at', now())->count(),
            'anamnesesGeradas' => PreRegistration::whereNotNull('anamnese_gerada')->count(),
            'transcricoes' => PreRegistration::whereNotNull('anamnese_transcricao')->count(),
            'ultimos' => PreRegistration::latest()->take(5)->get(),
            'semAnamnese' => PreRegistration::whereNull('anamnese_gerada')->whereNotNull('anamnese_transcricao')->latest()->take(5)->get(),
            'ultimasGravacoes' => PreRegistration::all()->flatMap->getMedia('anamnese')->sortByDesc('created_at')->take(5),
        ]);
    }
}
