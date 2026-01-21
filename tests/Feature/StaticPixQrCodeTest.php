<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\StaticPixQrCodeService;
use App\Models\Orcamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class StaticPixQrCodeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_saves_data_uri_base64_on_generate()
    {
        $orc = Orcamento::factory()->create([
            'forma_pagamento' => 'pix',
            'pix_chave_tipo' => 'telefone',
            'area_m2' => 10,
            'valor_m2' => 20,
        ]);

        $this->assertEquals('pix', $orc->forma_pagamento);
        $this->assertEquals('telefone', $orc->pix_chave_tipo);
        $this->assertGreaterThan(0, $orc->valor_total);

        $svc = app(StaticPixQrCodeService::class);
        $result = $svc->generate($orc);

        // Se o serviço falhar, queremos falhar o teste para debugar
        $this->assertTrue($result, 'StaticPixQrCodeService::generate falhou');

        $orc->refresh();

        $this->assertNotNull($orc->pix_qrcode_base64);
        $this->assertStringContainsString('base64,', $orc->pix_qrcode_base64);
    }
}
