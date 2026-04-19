<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;

test('a tela de login do Filament retorna HTTP 200', function () {
    $response = get('/admin/login');

    $response->assertStatus(200);
});

test('um usuario com credenciais invalidas e barrado', function () {
    $user = User::factory()->create([
        'email' => 'hacker@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = post('/admin/login', [
        'email' => 'hacker@example.com',
        'password' => 'wrongpassword',
    ]);

    assertGuest();
    $response->assertSessionHasErrors();
});

test('um usuario valido consegue logar e e redirecionado corretamente', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = post('/admin/login', [
        'email' => 'admin@example.com',
        'password' => 'password123',
    ]);

    assertAuthenticated();
    $response->assertRedirect('/admin');
});
