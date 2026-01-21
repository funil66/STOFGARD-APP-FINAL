<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Parceiro;

class CadastroResourceFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_loja_vinculada_field_shown_for_vendedor_on_edit()
    {
        $loja = Parceiro::factory()->create(['tipo' => 'loja', 'nome' => 'Loja Teste']);
        $vendedor = Parceiro::factory()->create(['tipo' => 'vendedor', 'nome' => 'Vendedor Teste', 'loja_id' => $loja->id]);

        $admin = User::factory()->create(['is_admin' => true]);

        // Request the resource view page (read-only) which should include the 'Loja vinculada' field in the record details for a vendedor
        $response = $this->actingAs($admin)
            ->get(\App\Filament\Resources\ParceiroResource::getUrl('view', ['record' => $vendedor->uuid]));

        // Request the resource edit page to verify the 'Loja vinculada' select is shown for vendedores
        $response = $this->actingAs($admin)
            ->get(\App\Filament\Resources\ParceiroResource::getUrl('edit', ['record' => $vendedor->uuid]));

        $response->assertSeeText('Loja vinculada');
    }
}
