<?php

use Illuminate\Support\Facades\Cache;

it('valida selo digital existente', function () {
    $hash = 'abc123hashvalido';

    Cache::put('digital_seal:' . $hash, [
        'tipo' => 'garantia',
        'modelo_id' => '10',
        'company_name' => 'Empresa Teste',
        'generated_at' => now()->format('d/m/Y H:i:s'),
        'hash' => $hash,
        'validation_url' => url('/validar/' . $hash),
    ], now()->addMinutes(10));

    $this->get('/validar/' . $hash)
        ->assertOk()
        ->assertSee('Certificado válido')
        ->assertSee('Empresa Teste');
});

it('retorna 404 para selo inexistente', function () {
    $this->get('/validar/hash-inexistente')
        ->assertNotFound()
        ->assertSee('Certificado não encontrado');
});
