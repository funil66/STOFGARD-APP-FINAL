<?php

namespace App\Services;

use App\Models\Cadastro;
use App\Models\Orcamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * LeadService - Responsável pela captação e processamento de leads
 *
 * Move toda a lógica de negócio que estava no web.php para cá,
 * implementando validação robusta e tratamento de exceções.
 */
class LeadService
{
    /**
     * Serviços disponíveis para solicitação de orçamento
     */
    public const SERVICOS_DISPONIVEIS = [
        'limpeza_estofados' => 'Limpeza de Estofados',
        'impermeabilizacao' => 'Impermeabilização',
        'higienizacao_colchao' => 'Higienização de Colchão',
        'limpeza_tapetes' => 'Limpeza de Tapetes',
        'limpeza_carpetes' => 'Limpeza de Carpetes',
        'limpeza_cortinas' => 'Limpeza de Cortinas',
        'outro' => 'Outro Serviço',
    ];

    /**
     * Processa a solicitação de orçamento do lead
     *
     * @throws ValidationException
     */
    public function processarSolicitacao(Request $request): array
    {
        // 1. Validação robusta dos dados
        $dados = $this->validarDados($request);

        return DB::transaction(function () use ($dados) {
            // 2. Busca ou cria o cliente
            $cliente = $this->buscarOuCriarCliente($dados);

            // 3. Cria o orçamento inicial
            $orcamento = $this->criarOrcamentoInicial($cliente, $dados);

            return [
                'success' => true,
                'cliente_id' => $cliente->id,
                'orcamento_id' => $orcamento->id,
                'message' => 'Solicitação recebida com sucesso! Em breve entraremos em contato.',
            ];
        });
    }

    /**
     * Valida os dados da solicitação com regras robustas
     *
     * @throws ValidationException
     */
    protected function validarDados(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'nome' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^[\pL\s\'\-\.]+$/u', // Permite letras, espaços, apóstrofos, hífens e pontos
            ],
            'celular' => [
                'required',
                'string',
                'min:10',
                'max:20',
                // Aceita formatos: (11) 99999-9999, 11999999999, +55 11 99999-9999
                'regex:/^[\+]?[(]?[0-9]{1,3}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{4,6}[-\s\.]?[0-9]{3,5}$/',
            ],
            'servico' => [
                'required',
                'string',
                'max:100',
            ],
            'cidade' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'endereco' => [
                'nullable',
                'string',
                'max:500',
            ],
            'mensagem' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'nome.required' => 'Por favor, informe seu nome.',
            'nome.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'nome.regex' => 'O nome contém caracteres inválidos.',
            'celular.required' => 'Por favor, informe seu celular/WhatsApp.',
            'celular.regex' => 'Formato de celular inválido. Use: (11) 99999-9999',
            'celular.min' => 'O celular deve ter pelo menos 10 dígitos.',
            'servico.required' => 'Por favor, selecione o serviço desejado.',
            'cidade.required' => 'Por favor, informe sua cidade.',
            'cidade.min' => 'A cidade deve ter pelo menos 2 caracteres.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Busca cliente existente pelo celular ou cria um novo
     */
    protected function buscarOuCriarCliente(array $dados): Cadastro
    {
        // Limpa caracteres do celular para busca
        $celularLimpo = preg_replace('/\D/', '', $dados['celular']);

        // Busca cliente existente (busca flexível)
        $cliente = Cadastro::where(function ($query) use ($celularLimpo, $dados) {
            $query->where('celular', 'LIKE', "%{$celularLimpo}%")
                ->orWhere('celular', $dados['celular']);

            // Se email foi informado, também busca por email
            if (!empty($dados['email'])) {
                $query->orWhere('email', $dados['email']);
            }
        })->first();

        if ($cliente) {
            // Atualiza dados se necessário (nome mais completo, email faltando, etc.)
            $atualizacoes = [];

            if (strlen($dados['nome']) > strlen($cliente->nome)) {
                $atualizacoes['nome'] = $dados['nome'];
            }

            if (empty($cliente->email) && !empty($dados['email'])) {
                $atualizacoes['email'] = $dados['email'];
            }

            if (!empty($atualizacoes)) {
                $cliente->update($atualizacoes);
            }

            return $cliente;
        }

        // Cria novo cliente
        return Cadastro::create([
            'nome' => $this->formatarNome($dados['nome']),
            'celular' => $dados['celular'],
            'cidade' => $dados['cidade'],
            'endereco' => $dados['endereco'] ?? null,
            'tipo' => 'cliente',
            'email' => $dados['email'] ?? $this->gerarEmailPlaceholder(),
            'origem' => 'lead_site',
        ]);
    }

    /**
     * Cria o orçamento inicial no funil de vendas
     */
    protected function criarOrcamentoInicial(Cadastro $cliente, array $dados): Orcamento
    {
        $observacoes = $this->montarObservacoes($dados);

        return Orcamento::create([
            'cadastro_id' => $cliente->id,
            'data_orcamento' => now(),
            'data_validade' => now()->addDays(7),
            'status' => 'rascunho',
            'etapa_funil' => 'novo',
            'tipo_servico' => $dados['servico'],
            'criado_por' => 'Sistema (Lead Page)',
            'valor_total' => 0.00,
            'observacoes' => $observacoes,
        ]);
    }

    /**
     * Formata o nome do cliente (capitalização correta)
     */
    protected function formatarNome(string $nome): string
    {
        // Palavras que não devem ser capitalizadas (preposições)
        $excecoes = ['de', 'da', 'do', 'das', 'dos', 'e'];

        $palavras = explode(' ', mb_strtolower(trim($nome)));
        $resultado = [];

        foreach ($palavras as $index => $palavra) {
            if ($index === 0 || !in_array($palavra, $excecoes)) {
                $resultado[] = mb_convert_case($palavra, MB_CASE_TITLE);
            } else {
                $resultado[] = $palavra;
            }
        }

        return implode(' ', $resultado);
    }

    /**
     * Gera um email placeholder único para clientes sem email
     */
    protected function gerarEmailPlaceholder(): string
    {
        return 'lead.' . uniqid() . '@placeholder.local';
    }

    /**
     * Monta as observações do orçamento com os dados do lead
     */
    protected function montarObservacoes(array $dados): string
    {
        $linhas = [
            '📍 Solicitação via Site',
            "🏙️ Cidade: {$dados['cidade']}",
            "🔧 Interesse: {$dados['servico']}",
        ];

        if (!empty($dados['endereco'])) {
            $linhas[] = "📫 Endereço: {$dados['endereco']}";
        }

        if (!empty($dados['mensagem'])) {
            $linhas[] = "";
            $linhas[] = "💬 Mensagem do cliente:";
            $linhas[] = $dados['mensagem'];
        }

        $linhas[] = "";
        $linhas[] = "⏰ Recebido em: " . now()->format('d/m/Y H:i');

        return implode("\n", $linhas);
    }

    /**
     * Retorna os serviços disponíveis para o formulário
     */
    public static function getServicosDisponiveis(): array
    {
        // Pode ser estendido para buscar do banco de dados (TabelaPreco)
        return self::SERVICOS_DISPONIVEIS;
    }
}
