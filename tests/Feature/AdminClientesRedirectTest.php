<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminClientesRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_clientes_redirects_to_admin_cadastros()
    {
        $resp = $this->get('/admin/clientes');

        $resp->assertStatus(302);
        $resp->assertRedirect('/admin/cadastros');
    }
}
