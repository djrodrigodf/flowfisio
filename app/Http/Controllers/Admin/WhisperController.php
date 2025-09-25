<?php

namespace App\Http\Controllers\Admin;

use App\Models\PreRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhisperController
{
    public function transcribe(Request $request)
    {
        if (! $request->hasFile('audio')) {
            return response()->json(['error' => 'No audio file found'], 422);
        }

        $file = $request->file('audio');
        $preRegistration = PreRegistration::findOrFail($request->get('paciente_id'));

        $baseName = 'anamnese_'.now()->format('Ymd_His');
        $extension = $file->getClientOriginalExtension();

        // Salva o áudio via Spatie Media Library
        $media = $preRegistration->addMedia($file)
            ->usingName($baseName)
            ->usingFileName("{$baseName}.{$extension}")
            ->toMediaCollection('anamnese');

        // Obtém o caminho real do arquivo salvo
        $audioPath = $media->getPath();

        if (! file_exists($audioPath)) {
            return response()->json(['error' => 'Arquivo de áudio salvo não encontrado.'], 500);
        }

        // Envia para Whisper
        $response = Http::withToken(config('services.openai.key'))
            ->attach('file', file_get_contents($audioPath), $media->file_name)
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
                'language' => 'pt',
                'response_format' => 'json',
            ]);

        return [
            'text' => $response->json('text'),
            'audio_url' => $media->getUrl(),
            'media_id' => $media->id,
        ];
    }
}
