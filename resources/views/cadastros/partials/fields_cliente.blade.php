<p><strong>Email:</strong> {{ $item->email ?? '—' }}</p>
<p><strong>Telefone:</strong> {{ $item->telefone ?? '—' }}</p>
<p><strong>Celular:</strong> {{ $item->celular ?? '—' }}</p>
<p><strong>CPF/CNPJ:</strong> {{ $item->cpf_cnpj ?? '—' }}</p>
<p><strong>CEP:</strong> {{ $item->cep ?? '—' }}</p>
<p><strong>Endereço:</strong> {{ $item->logradouro ?? '' }} {{ $item->numero ?? '' }} {{ $item->complemento ?? '' }}, {{ $item->bairro ?? '' }}, {{ $item->cidade ?? '' }} - {{ $item->estado ?? '' }}</p>