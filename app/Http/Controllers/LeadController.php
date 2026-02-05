<?php

namespace App\Http\Controllers;

use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * LeadController - Processa solicitações de leads do site
 *
 * Este controller é simples e delega toda lógica de negócio ao LeadService.
 */
class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    /**
     * Exibe o formulário de solicitação de orçamento
     */
    public function create()
    {
        return view('landing.solicitar-orcamento', [
            'servicos' => LeadService::getServicosDisponiveis(),
        ]);
    }

    /**
     * Processa a solicitação de orçamento
     */
    public function store(Request $request)
    {
        try {
            $resultado = $this->leadService->processarSolicitacao($request);

            return back()->with('success', $resultado['message']);

        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->validator)
                ->withInput();

        } catch (\Exception $e) {
            report($e);

            return back()
                ->with('error', 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.')
                ->withInput();
        }
    }
}
