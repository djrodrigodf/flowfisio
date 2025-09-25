<?php

namespace App\Services;

use App\Models\PreRegistration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;

class AnamneseService
{
    public function gerar(string $transcricao, PreRegistration $paciente): string
    {
        // Renderiza o prompt
        $prompt = View::make('prompts.anamnese-prompt', [
            'transcricao' => $transcricao,
            'paciente' => $paciente,
        ])->render();

        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.4,
            ]);

        return $response->json('choices.0.message.content') ?? '❌ Erro ao gerar anamnese.';
    }
}
