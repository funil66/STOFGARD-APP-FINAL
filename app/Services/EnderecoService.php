<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EnderecoService
{
    /**
     * Busca um endereço pelo CEP usando a API ViaCEP.
     *
     * @param string|null $cep
     * @return array|null Retorna um array com os dados do endereço ou null se não encontrar.
     */
    public static function buscarCep(?string $cep): ?array
    {
        if (!$cep) {
            return null;
        }

        // Remove caracteres não numéricos
        $cepLimpo = preg_replace('/[^0-9]/', '', $cep);

        // Verifica se tem 8 dígitos
        if (strlen($cepLimpo) !== 8) {
            return null;
        }

        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cepLimpo}/json/");

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['erro'])) {
                    return null;
                }

                return [
                    'logradouro' => $data['logradouro'] ?? null,
                    'bairro' => $data['bairro'] ?? null,
                    'cidade' => $data['localidade'] ?? null,
                    'estado' => $data['uf'] ?? null,
                    'complemento' => $data['complemento'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Em caso de erro na requisição (timeout, etc), retorna null
            // Poderia logar o erro aqui se necessário: Log::error("Erro ao buscar CEP: " . $e->getMessage());
            return null;
        }

        return null;
    }
}
