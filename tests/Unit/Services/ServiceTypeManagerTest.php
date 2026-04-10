<?php

namespace Tests\Unit\Services;

use App\Enums\ServiceType;
use App\Models\Categoria;
use App\Models\PerfilGarantia;
use App\Models\Setting;
use App\Services\ServiceTypeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTypeManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_all_retorna_tipos_padrao_do_enum_quando_nao_ha_customizacao(): void
    {
        $all = ServiceTypeManager::getAll();

        $this->assertGreaterThanOrEqual(count(ServiceType::cases()), $all->count());

        foreach (ServiceType::cases() as $serviceType) {
            $item = $all->firstWhere('slug', $serviceType->value);

            $this->assertNotNull($item);
            $this->assertEquals($serviceType->getLabel(), $item['label']);
        }
    }

    public function test_settings_tem_prioridade_sobre_categoria_e_enum(): void
    {
        $perfil = PerfilGarantia::create([
            'nome' => 'Garantia Premium',
            'dias_garantia' => 120,
        ]);

        Categoria::create([
            'tipo' => 'servico_tipo',
            'nome' => 'Higienização DB',
            'slug' => 'higienizacao',
            'cor' => 'danger',
            'icone' => 'heroicon-o-fire',
            'descricao' => 'Descrição via categoria',
            'ativo' => true,
            'ordem' => 1,
        ]);

        Setting::set('system_service_types', [
            [
                'slug' => 'higienizacao',
                'label' => 'Higienização Custom',
                'color' => 'success',
                'icon' => 'heroicon-o-star',
                'descricao_pdf' => 'Descrição via settings',
                'perfil_garantia_id' => $perfil->id,
            ],
        ], 'sistema', 'json');

        $item = ServiceTypeManager::get('higienizacao');

        $this->assertNotNull($item);
        $this->assertEquals('Higienização Custom', $item['label']);
        $this->assertEquals('success', $item['color']);
        $this->assertEquals('heroicon-o-star', $item['icon']);
        $this->assertEquals('Descrição via settings', $item['descricao_pdf']);
        $this->assertEquals($perfil->id, $item['perfil_garantia_id']);
        $this->assertEquals(120, $item['dias_garantia']);

        $this->assertEquals('Higienização Custom', ServiceTypeManager::getLabel('higienizacao'));
        $this->assertEquals('success', ServiceTypeManager::getColor('higienizacao'));
        $this->assertEquals(120, ServiceTypeManager::getDiasGarantia('higienizacao'));
        $this->assertEquals($perfil->id, ServiceTypeManager::getPerfilGarantiaId('higienizacao'));
    }
}
