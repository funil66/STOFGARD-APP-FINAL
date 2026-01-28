<?php

namespace App\Http\Controllers;

use App\Models\Cadastro;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CadastroPdfController extends Controller
{
    public function gerarPdf(Cadastro $cadastro)
    {
        // Carrega configurações (Logo, etc)
        $config = Setting::all()->pluck('value', 'key')->toArray();

        // Se tiver relacionamento com loja (para vendedores), carrega
        $cadastro->load('loja');

        $pdf = Pdf::loadView('pdf.cadastro_ficha', [
            'cadastro' => $cadastro,
            'config' => $config,
        ]);

        // nome de arquivo seguro
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '-', $cadastro->nome);

        return $pdf->stream("Ficha-Cadastral-{$safeName}.pdf");
    }
}
