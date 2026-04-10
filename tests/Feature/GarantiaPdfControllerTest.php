<?php

namespace Tests\Feature;

use App\Jobs\ProcessPdfJob;
use App\Http\Controllers\GarantiaPdfController;
use App\Models\Cadastro;
use App\Models\Garantia;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GarantiaPdfControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_gerar_por_ordem_servico_retorna_redirect_quando_os_nao_esta_concluida(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $cadastro = Cadastro::factory()->create();

        $ordemServico = OrdemServico::create([
            'cadastro_id' => $cadastro->id,
            'status' => 'em_andamento',
            'tipo_servico' => 'servico',
            'descricao_servico' => 'Teste OS pendente',
            'data_abertura' => now(),
            'valor_total' => 250,
            'criado_por' => $user->id,
        ]);

        $this->from('/admin/ordens-servico');
        $response = app(GarantiaPdfController::class)->gerarPorOrdemServico($ordemServico);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/admin/ordens-servico', $response->getTargetUrl());
        $this->assertDatabaseCount('garantias', 0);
    }

    public function test_gerar_por_ordem_servico_retorna_redirect_quando_nao_existe_perfil_de_garantia_configurado(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $cadastro = Cadastro::factory()->create();

        $ordemServico = OrdemServico::create([
            'cadastro_id' => $cadastro->id,
            'status' => 'concluida',
            'tipo_servico' => 'servico',
            'descricao_servico' => 'Teste sem perfil',
            'data_abertura' => now()->subDay(),
            'data_conclusao' => now(),
            'valor_total' => 500,
            'criado_por' => $user->id,
        ]);

        $this->from('/admin/ordens-servico');
        $response = app(GarantiaPdfController::class)->gerarPorOrdemServico($ordemServico);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/admin/ordens-servico', $response->getTargetUrl());
        $this->assertDatabaseMissing('garantias', [
            'ordem_servico_id' => $ordemServico->id,
        ]);
    }

    public function test_gerar_por_ordem_servico_utiliza_garantia_existente_sem_criar_nova(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_admin' => true, 'is_super_admin' => true]);
        $cadastro = Cadastro::factory()->create();

        $ordemServico = OrdemServico::create([
            'cadastro_id' => $cadastro->id,
            'status' => 'concluida',
            'tipo_servico' => 'servico',
            'descricao_servico' => 'Teste com garantia existente',
            'data_abertura' => now()->subDays(2),
            'data_conclusao' => now()->subDay(),
            'valor_total' => 750,
            'criado_por' => $user->id,
        ]);

        Garantia::create([
            'ordem_servico_id' => $ordemServico->id,
            'tipo_servico' => 'servico',
            'data_inicio' => now()->subDay(),
            'data_fim' => now()->addDays(90),
            'dias_garantia' => 90,
            'status' => 'ativa',
            'observacoes' => 'Garantia já existente',
        ]);

        $this->actingAs($user);
        $response = app(GarantiaPdfController::class)->gerarPorOrdemServico($ordemServico);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(1, Garantia::where('ordem_servico_id', $ordemServico->id)->count());
        Queue::assertPushed(ProcessPdfJob::class);
    }
}
