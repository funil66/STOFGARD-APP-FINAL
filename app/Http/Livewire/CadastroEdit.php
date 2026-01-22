<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Cliente;
use App\Models\Parceiro;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CadastroEdit extends Component
{
    use WithFileUploads;

    public $uuid;
    public $model;
    public $type;
    public $cadastroTipo;

    // Generic fields
    public $nome;
    public $email;
    public $telefone;
    public $celular;

    // Cliente specific
    public $cpf_cnpj;

    // Parceiro specific
    public $tipo; // loja|vendedor
    public $razao_social;
    public $cnpj_cpf;
    public $percentual_comissao;
    public $ativo;

    public $newUpload;

    public function mount($uuid)
    {
        $this->uuid = $uuid;

        $cliente = Cliente::withTrashed()->where('uuid', $uuid)->first();
        if ($cliente) {
            $this->model = $cliente;
            $this->type = 'cliente';
        } else {
            $parceiro = Parceiro::withTrashed()->where('uuid', $uuid)->first();
            if (! $parceiro) abort(404);
            $this->model = $parceiro;
            $this->type = 'parceiro';
        }

        $this->fillFromModel();

        // determine UI-level cadastro kind (cliente|loja|vendedor) now that model fields are filled
        if ($this->type === 'cliente') {
            $this->cadastroTipo = 'cliente';
        } else {
            $this->cadastroTipo = $this->tipo ?? 'loja';
        }
    }

    protected function fillFromModel()
    {
        $m = $this->model;
        $this->nome = $m->nome;
        $this->email = $m->email;
        $this->telefone = $m->telefone;
        $this->celular = $m->celular;

        if ($this->type === 'cliente') {
            $this->cpf_cnpj = $m->cpf_cnpj ?? null;
        } else {
            $this->tipo = $m->tipo;
            $this->razao_social = $m->razao_social ?? null;
            $this->cnpj_cpf = $m->cnpj_cpf ?? null;
            $this->percentual_comissao = $m->percentual_comissao ?? null;
            $this->ativo = (bool) ($m->ativo ?? false);
        }
    }

    public function updatedTipo($val)
    {
        // Keep server state consistent for dynamic type changes
        $this->tipo = $val;
    }

    public function updatedCadastroTipo($val)
    {
        // Map the UI-level selection (cliente|loja|vendedor)
        if ($val === 'cliente') {
            $this->type = 'cliente';
        } else {
            $this->type = 'parceiro';
            $this->tipo = $val;
        }
    }

    public function save()
    {
        // Basic validation; more rules can be added
        $rules = [
            'nome' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:50',
            'celular' => 'nullable|string|max:50',
            'newUpload' => 'nullable|file|max:5120',
        ];

        if ($this->type === 'cliente') {
            $rules['cpf_cnpj'] = 'nullable|string|max:255';
        } else {
            $rules['tipo'] = 'required|in:loja,vendedor';
            $rules['razao_social'] = 'nullable|string|max:255';
            $rules['cnpj_cpf'] = 'nullable|string|max:255';
            $rules['percentual_comissao'] = 'nullable|numeric';
            $rules['ativo'] = 'sometimes|boolean';
        }

        $data = $this->validate($rules);

        // Map fields to model
        $m = $this->model;
        $m->nome = $this->nome;
        $m->email = $this->email;
        $m->telefone = $this->telefone;
        $m->celular = $this->celular;

        if ($this->type === 'cliente') {
            $m->cpf_cnpj = $this->cpf_cnpj;
        } else {
            $m->tipo = $this->tipo;
            $m->razao_social = $this->razao_social;
            $m->cnpj_cpf = $this->cnpj_cpf;
            $m->percentual_comissao = $this->percentual_comissao;
            $m->ativo = $this->ativo ? 1 : 0;
        }

        if ($this->newUpload) {
            $path = $this->newUpload->store('clientes-arquivos', 'public');
            $arquivos = $m->arquivos ?? [];
            $arquivos[] = $path;
            $m->arquivos = $arquivos;
        }

        $m->save();

        session()->flash('success', 'Cadastro atualizado.');

        return redirect()->route('cadastros.show', ['uuid' => $m->uuid]);
    }

    public function removeArquivo($path)
    {
        if (! $this->model) return;
        if (method_exists($this->model, 'removeArquivo')) {
            $this->model->removeArquivo($path);
        } else {
            // fallback: remove from json and unlink
            $arquivos = $this->model->arquivos ?? [];
            $arquivos = array_filter($arquivos, fn($p) => $p !== $path);
            $this->model->arquivos = array_values($arquivos);
            $this->model->save();
            Storage::disk('public')->delete($path);
        }

        $this->fillFromModel();
        session()->flash('success', 'Arquivo removido.');
    }

    public function render()
    {
        return view('livewire.cadastro-edit');
    }
}
