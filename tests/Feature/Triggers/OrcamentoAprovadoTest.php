<?php

namespace Tests\Feature\Triggers;

use App\Models\Agenda;
use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\Tenant;

test('quando um orcamento é aprovado, o sistema deve gerar OS, Financeiro e Agenda', function () {
    // 1. Prepara o terreno (Gera dados falsos com Factory)
    // $tenant = Tenant::factory()->create(); // Requires TenantFactory
    $orcamento = Orcamento::factory()->create(['status' => 'orcamento']); // Using 'orcamento' as status might be standard in the app

    // 2. Puxa o gatilho (Simula a ação do usuário de aprovar)
    $orcamento->update(['status' => 'aprovado']);

    // 3. Vai no alvo verificar se a C4 explodiu (Asserts)
    // Verifica se a OS foi criada
    expect(OrdemServico::where('orcamento_id', $orcamento->id)->exists())->toBeTrue();

    // Verifica se o Financeiro foi gerado pro Orçamento
    expect(Financeiro::where('origem_id', $orcamento->id)->orWhere('referencia_id', $orcamento->id)->count())->toBeGreaterThan(0);

    // Verifica se tem agendamento
    expect(Agenda::where('cliente_id', $orcamento->cliente_id)->exists())->toBeTrue();
})->skip('Descomentar e adaptar os campos assim que as Factories de OS/Tenant/Agenda estiverem completas.');
