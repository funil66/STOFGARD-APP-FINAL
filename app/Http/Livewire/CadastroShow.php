<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Parceiro;
use Illuminate\Support\Facades\Storage;

class CadastroShow extends Component
{
    public $uuidOrId;
    public $model;
    public $type;
    public $filesExist = [];

    public function mount($uuidOrId)
    {
        $this->uuidOrId = $uuidOrId;

        // Resolve by numeric legacy id -> redirect to canonical uuid
        if (is_numeric($uuidOrId)) {
            $found = Cliente::withTrashed()->find($uuidOrId) ?? Parceiro::withTrashed()->find($uuidOrId);
            if ($found && ! empty($found->uuid)) {
                redirect()->route('cadastros.show', ['uuid' => $found->uuid], 301)->send();
                return;
            }
        }

        $cliente = Cliente::withTrashed()->where('uuid', $uuidOrId)->first();
        if ($cliente) {
            $this->model = $cliente;
            $this->type = 'cliente';
        } else {
            $parceiro = Parceiro::withTrashed()->where('uuid', $uuidOrId)->first();
            if (! $parceiro) {
                abort(404);
            }
            $this->model = $parceiro;
            $this->type = 'parceiro';
        }

        foreach ($this->model->arquivos ?? [] as $p) {
            $this->filesExist[$p] = Storage::disk('public')->exists($p);
        }
    }

    public function downloadArquivo($base64)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $path = base64_decode($base64);
        if (! in_array($path, $this->model->arquivos ?? [], true)) {
            abort(404);
        }

        // Use existing controller route for download (keeps behavior centralized)
        return redirect()->route('cadastros.arquivo.download', ['uuid' => $this->model->uuid, 'path' => $base64]);
    }

    public function render()
    {
        return view('livewire.cadastro-show');
    }
}
