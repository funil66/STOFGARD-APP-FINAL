<?php

use App\Models\User;
use App\Models\Cadastro;
use Filament\Pages\Dashboard;
use function Pest\Laravel\{actingAs, get};

// ♻️ Reseta o banco a cada teste
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('redireciona para login se tentar acessar sem autenticação', function () {
    get('/admin/cadastros')->assertRedirect('/admin/login');
});

test('admin consegue acessar a listagem de cadastros', function () {
    // 1. Arrange (Preparar)
    $admin = User::firstOrCreate(
        ['email' => 'admin@stofgard.com'],
        ['name' => 'Admin', 'password' => bcrypt('password'), 'is_admin' => true]
    );

    // Cria 3 cadastros no banco pra ter o que mostrar
    $cadastros = Cadastro::factory()->count(3)->create();

    // 2. Act & Assert (Agir e Validar)
    actingAs($admin)
        ->get('/admin/cadastros') // A URL padrão do Resource do Filament
        ->assertSuccessful() // Status 200
        ->assertSee($cadastros->first()->nome); // Verifica se o nome do cliente aparece na tela (HTML)
});

test('admin consegue ver detalhes de um cadastro específico', function () {
    $admin = User::firstOrCreate(
        ['email' => 'admin@stofgard.com'],
        ['name' => 'Admin', 'password' => bcrypt('password'), 'is_admin' => true]
    );
    $loja = Cadastro::factory()->loja()->create();

    actingAs($admin)
        ->get("/admin/cadastros/{$loja->id}") // Rota de View/Edit
        ->assertSuccessful()
        ->assertSee($loja->email);
});

test('admin consegue ver botões de ações na listagem', function () {
    $admin = User::firstOrCreate(
        ['email' => 'admin@stofgard.com'],
        ['name' => 'Admin', 'password' => bcrypt('password'), 'is_admin' => true]
    );
    $cadastro = Cadastro::factory()->create();

    actingAs($admin)
        ->get('/admin/cadastros')
        ->assertSuccessful()
        ->assertSee(route('cadastro.pdf', $cadastro)) // Verifica link do PDF
        ->assertSee("/admin/cadastros/{$cadastro->id}") // Verifica link de View
        ->assertSee("/admin/cadastros/{$cadastro->id}/edit"); // Verifica link de Edit
});
