<p><strong>Tipo:</strong> {{ ucfirst($item->tipo ?? '—') }}</p>
<p><strong>Razão Social:</strong> {{ $item->razao_social ?? '—' }}</p>
<p><strong>CNPJ/CPF:</strong> {{ $item->cnpj_cpf ?? $item->cpf_cnpj ?? '—' }}</p>
<p><strong>Percentual Comissão:</strong> {{ isset($item->percentual_comissao) ? number_format($item->percentual_comissao, 2, ',', '.') . '%' : '—' }}</p>
<p><strong>Ativo:</strong> {{ isset($item->ativo) ? ($item->ativo ? 'Sim' : 'Não') : '—' }}</p>
<p><strong>Telefone:</strong> {{ $item->telefone ?? '—' }}</p>
<p><strong>Celular:</strong> {{ $item->celular ?? '—' }}</p>
<p><strong>Endereço:</strong> {{ $item->logradouro ?? '' }} {{ $item->numero ?? '' }} {{ $item->complemento ?? '' }}, {{ $item->bairro ?? '' }}, {{ $item->cidade ?? '' }} - {{ $item->estado ?? '' }}</p>