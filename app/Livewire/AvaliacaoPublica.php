<?php

namespace App\Livewire;

use App\Models\Avaliacao;
use Livewire\Component;

/**
 * Página pública de avaliação NPS — acessível via token único enviado ao cliente.
 */
class AvaliacaoPublica extends Component
{
    public ?Avaliacao $avaliacao = null;
    public ?int $nota = null;
    public string $comentario = '';
    public bool $enviada = false;
    public bool $invalida = false;

    public function mount(string $token): void
    {
        $this->avaliacao = Avaliacao::where('token', $token)->first();

        if (! $this->avaliacao) {
            $this->invalida = true;
            return;
        }

        if ($this->avaliacao->respondida_em) {
            $this->enviada = true;
            $this->nota = $this->avaliacao->nota;
            $this->comentario = $this->avaliacao->comentario ?? '';
        }
    }

    public function selecionarNota(int $nota): void
    {
        $this->nota = $nota;
    }

    public function enviar(): void
    {
        $this->validate([
            'nota' => 'required|integer|min:0|max:10',
            'comentario' => 'nullable|string|max:2000',
        ]);

        $this->avaliacao->update([
            'nota' => $this->nota,
            'comentario' => $this->comentario,
            'respondida_em' => now(),
        ]);

        $this->enviada = true;
    }

    public function render()
    {
        return view('livewire.avaliacao-publica')
            ->layout('components.layouts.guest', [
                'title' => 'Avaliação do Serviço',
            ]);
    }
}
