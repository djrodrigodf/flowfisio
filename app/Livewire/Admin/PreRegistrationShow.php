<?php

namespace App\Livewire\Admin;

use App\Models\PreRegistration;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Mary\Traits\Toast;

class PreRegistrationShow extends Component
{
    use Toast;

    public PreRegistration $preRegistration;

    public ?string $anamnese = null;

    public bool $loading = false;

    public string $gravacao = '';

    public string $anamneseGerada = '';

    protected $listeners = ['transcricaoPronta' => 'setTranscricao'];

    public string $transcricao = '';

    public function setTranscricao($dados)
    {
        $this->transcricao = $dados['texto'] ?? '';
    }

    public function mount(PreRegistration $preRegistration)
    {
        $this->preRegistration = $preRegistration;
        if ($this->preRegistration->anamnese_gerada) {
            $raw = $this->preRegistration->anamnese_gerada;

            // Remove blocos de código Markdown como ```html ... ```
            $limpo = preg_replace('/^```html\s*(.*?)\s*```$/s', '$1', trim($raw));

            $this->anamneseGerada = $limpo;
        }
    }

    public function concluir()
    {
        $this->preRegistration->update([
            'status' => 'concluido',
        ]);

        $this->toast('success', 'Anamnese concluída com sucesso.');
    }

    public function salvarTranscricao()
    {
        $this->preRegistration->update([
            'anamnese_transcricao' => $this->transcricao,
        ]);
    }

    public function salvarAnamnese()
    {
        $this->preRegistration->update([
            'anamnese_gerada' => $this->anamnese,
        ]);
    }

    public function iniciarAnamnese()
    {
        $this->loading = true;

        $this->anamnese = app(\App\Services\AnamneseService::class)->gerar(
            $this->transcricao,
            $this->preRegistration
        );
        $this->salvarAnamnese();
        $this->loading = false;
    }

    public function transcreverGravacao(int $mediaId)
    {
        $media = $this->preRegistration->getMedia('anamnese')->firstWhere('id', $mediaId);

        if (! $media || ! file_exists($media->getPath())) {
            $this->dispatch('toast')->to('Erro: gravação não encontrada.');

            return;
        }

        $response = Http::withToken(config('services.openai.key'))
            ->attach('file', file_get_contents($media->getPath()), $media->file_name)
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
                'language' => 'pt',
                'response_format' => 'json',
            ]);

        $text = $response->json('text');

        if ($text) {
            $this->transcricao = $text;
            $this->salvarTranscricao();

            $this->js(<<<'JS'
        const el = document.querySelector('textarea[name="transcricao"]');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.focus();
        }
    JS);
        } else {
            $this->dispatch('toast')->to('Falha ao transcrever áudio selecionado.');
        }
    }

    public function render()
    {
        return view('livewire.admin.pre-registration-show');
    }
}
