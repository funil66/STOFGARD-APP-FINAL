<?php

namespace Tests\Unit;

use App\Models\Financeiro;
use App\Services\ProLaboreCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProLaboreCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcular_lucro_liquido()
    {
        // Arrange
        $inicio = Carbon::now()->startOfMonth();
        $fim = Carbon::now()->endOfMonth();

        // Receita paga dentro do período
        Financeiro::factory()->create([
            'tipo' => 'entrada',
            'status' => 'pago',
            'valor_pago' => 1000,
            'data_pagamento' => $inicio->copy()->addDay(),
        ]);

        // Receita paga fora do período (não deve contar)
        Financeiro::factory()->create([
            'tipo' => 'entrada',
            'status' => 'pago',
            'valor_pago' => 500,
            'data_pagamento' => $inicio->copy()->subDay(),
        ]);

        // Despesa paga dentro do período
        Financeiro::factory()->create([
            'tipo' => 'saida',
            'status' => 'pago',
            'valor_pago' => 300,
            'data_pagamento' => $inicio->copy()->addDay(),
        ]);

        // Despesa pendente (não deve contar)
        Financeiro::factory()->create([
            'tipo' => 'saida',
            'status' => 'pendente',
            'valor' => 200,
            'data_pagamento' => null,
            'data' => $inicio->copy()->addDay(),
        ]);

        $calculator = new ProLaboreCalculator();

        // Act
        $lucro = $calculator->calcularLucroLiquido($inicio, $fim);

        // Assert
        // 1000 (receita) - 300 (despesa) = 700
        $this->assertEquals(700, $lucro);
    }

    public function test_calcular_reserva()
    {
        $calculator = new ProLaboreCalculator();

        // Lucro 1000, 20% reserva = 200
        $this->assertEquals(200, $calculator->calcularReserva(1000, 20));

        // Lucro 0, 20% reserva = 0
        $this->assertEquals(0, $calculator->calcularReserva(0, 20));

        // Lucro -100, 20% reserva = 0 (não deve haver reserva sobre prejuízo)
        $this->assertEquals(0, $calculator->calcularReserva(-100, 20));
    }

    public function test_calcular_distribuicao()
    {
        $calculator = new ProLaboreCalculator();

        $lucroDisponivel = 1000;
        $socios = [
            ['nome' => 'Sócio A', 'percentual' => 60],
            ['nome' => 'Sócio B', 'percentual' => 40],
        ];

        $distribuicao = $calculator->calcularDistribuicao($lucroDisponivel, $socios);

        $this->assertCount(2, $distribuicao);

        // Sócio A
        $this->assertEquals('Sócio A', $distribuicao[0]['nome']);
        $this->assertEquals(600, $distribuicao[0]['valor']);

        // Sócio B
        $this->assertEquals('Sócio B', $distribuicao[1]['nome']);
        $this->assertEquals(400, $distribuicao[1]['valor']);
    }
}
