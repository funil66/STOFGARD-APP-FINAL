<?php

namespace App\Http\Controllers;

use App\Models\CadastroView;
use App\Models\Cliente;
use App\Models\Parceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CadastroController extends Controller
{
    public function index(Request $request)
    {
        $query = CadastroView::query();

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('celular', 'like', "%{$search}%");
            });
        }

        $cadastros = $query->orderBy('nome')->paginate(25);

        return view('cadastros.index', compact('cadastros'));
    }

    public function lojas(Request $request)
    {
        $query = CadastroView::query()->where('tipo', 'loja');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('celular', 'like', "%{$search}%");
            });
        }

        $cadastros = $query->orderBy('nome')->paginate(25);

        return view('cadastros.index', compact('cadastros'))->with('view_filter', 'lojas');
    }

    public function vendedores(Request $request)
    {
        $query = CadastroView::query()->where('tipo', 'vendedor');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('celular', 'like', "%{$search}%");
            });
        }

        $cadastros = $query->orderBy('nome')->paginate(25);

        return view('cadastros.index', compact('cadastros'))->with('view_filter', 'vendedores');
    }

    protected function findByUuid(string $uuid)
    {
        $cliente = Cliente::withTrashed()->where('uuid', $uuid)->first();
        if ($cliente) {
            return ['model' => 'cliente', 'instance' => $cliente];
        }

        $parceiro = Parceiro::withTrashed()->where('uuid', $uuid)->first();
        if ($parceiro) {
            return ['model' => 'parceiro', 'instance' => $parceiro];
        }

        return null;
    }

    protected function isAdmin(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return settings()->isAdmin(auth()->user());
    }

    public function downloadArquivo(Request $request, $uuid)
    {
        $found = $this->findByUuid($uuid);
        if (!$found) {
            abort(404);
        }

        if (!auth()->check()) {
            abort(403);
        }

        $model = $found['instance'];

        $encoded = $request->query('path');
        if (!$encoded) {
            return redirect()->route('cadastros.show', ['uuid' => $uuid])->with('error', 'Arquivo não especificado.');
        }

        $path = base64_decode($encoded);
        $files = $model->arquivos ?? [];

        if (!in_array($path, $files, true)) {
            abort(404);
        }

        $filePath = Storage::disk('public')->path($path);
        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->download($filePath, basename($path));
    }

    public function show($uuid)
    {
        // Accept legacy numeric IDs and redirect to canonical uuid when found
        if (is_numeric($uuid)) {
            $cliente = Cliente::withTrashed()->find($uuid);
            if ($cliente && !empty($cliente->uuid)) {
                return redirect()->route('cadastros.show', ['uuid' => $cliente->uuid], 301);
            }

            $parceiro = Parceiro::withTrashed()->find($uuid);
            if ($parceiro && !empty($parceiro->uuid)) {
                return redirect()->route('cadastros.show', ['uuid' => $parceiro->uuid], 301);
            }
        }

        $found = $this->findByUuid($uuid);
        if (!$found) {
            abort(404);
        }

        // Pass uuid through so Livewire can mount by uuid or id as needed
        return view('cadastros.show', ['item' => $found['instance'], 'type' => $found['model'], 'uuid' => $uuid]);
    }

    public function edit($uuid)
    {
        $found = $this->findByUuid($uuid);
        if (!$found) {
            abort(404);
        }

        if (!$this->isAdmin()) {
            abort(403);
        }

        return view('cadastros.edit', ['item' => $found['instance'], 'type' => $found['model']]);
    }

    public function update(Request $request, $uuid)
    {
        $found = $this->findByUuid($uuid);
        if (!$found) {
            abort(404);
        }

        if (!$this->isAdmin()) {
            abort(403);
        }

        $rules = [
            'nome' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:50',
            'celular' => 'nullable|string|max:50',
            'cpf_cnpj' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:20',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:50',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:4',
            'observacoes' => 'nullable|string',
            'arquivos.*' => 'nullable|file|max:5120',
        ];

        // Additional validation for parceiros (lojas/vendedores)
        if ($found['model'] === 'parceiro') {
            $rules = array_merge($rules, [
                'tipo' => 'required|in:loja,vendedor',
                'razao_social' => 'nullable|string|max:255',
                'cnpj_cpf' => 'nullable|string|max:255',
                'percentual_comissao' => 'nullable|numeric',
                'ativo' => 'sometimes|boolean',
            ]);
        }

        $data = $request->validate($rules);

        $model = $found['instance'];

        // Handle field name differences between Cliente and Parceiro
        if ($found['model'] === 'parceiro') {
            if (isset($data['cpf_cnpj'])) {
                $data['cnpj_cpf'] = $data['cpf_cnpj'];
                unset($data['cpf_cnpj']);
            }
        }

        // Handle files upload and append to arquivos JSON
        if ($request->hasFile('arquivos')) {
            $arquivos = $model->arquivos ?? [];
            foreach ($request->file('arquivos') as $file) {
                $path = $file->store('clientes-arquivos', 'public');
                $arquivos[] = $path;
            }
            $data['arquivos'] = $arquivos;
        }

        $model->fill($data);
        $model->save();

        return redirect()->route('cadastros.show', ['uuid' => $model->uuid])->with('success', 'Cadastro atualizado.');
    }

    public function destroy(Request $request, $uuid)
    {
        $found = $this->findByUuid($uuid);
        if (!$found) {
            abort(404);
        }

        if (!$this->isAdmin()) {
            abort(403);
        }

        $model = $found['instance'];

        $model->delete();

        return redirect()->route('cadastros.index')->with('success', 'Cadastro excluído.');
    }

    public function destroyArquivo(Request $request, $uuid)
    {
        $found = $this->findByUuid($uuid);
        if (!$found) {
            abort(404);
        }

        if (!$this->isAdmin()) {
            abort(403);
        }

        $model = $found['instance'];

        $path = $request->input('path');
        if (!$path) {
            return back()->with('error', 'Arquivo não especificado.');
        }

        if ($model->removeArquivo($path)) {
            return back()->with('success', 'Arquivo removido.');
        }

        return back()->with('error', 'Falha ao remover arquivo.');
    }

    public function bulkDestroy(Request $request)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $selected = $request->input('selected', []);
        if (empty($selected)) {
            return redirect()->route('cadastros.index')->with('error', 'Nenhum cadastro selecionado.');
        }

        $deleted = 0;
        foreach ($selected as $uuid) {
            $found = $this->findByUuid($uuid);
            if ($found) {
                $found['instance']->delete();
                $deleted++;
            }
        }

        return redirect()->route('cadastros.index')->with('success', "{$deleted} cadastros excluídos.");
    }

    public function downloadArquivos($uuid)
    {
        if (!auth()->check()) {
            abort(403);
        }

        $found = $this->findByUuid($uuid);
        if (!$found) {
            abort(404);
        }

        $model = $found['instance'];
        $files = $model->arquivos ?? [];

        if (empty($files)) {
            return redirect()->route('cadastros.show', ['uuid' => $uuid])->with('error', 'Nenhum arquivo para download.');
        }

        $zip = new ZipArchive;
        $zipFileName = 'arquivos_' . $uuid . '.zip';
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $zipPath = $tempDir . '/' . $zipFileName;

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                $filePath = Storage::disk('public')->path($file);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, basename($file));
                }
            }
            $zip->close();

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } else {
            return redirect()->route('cadastros.show', ['uuid' => $uuid])->with('error', 'Erro ao criar arquivo zip.');
        }
    }
}
