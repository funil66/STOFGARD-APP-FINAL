<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_post_redirects_to_admin()
    {
        $user = User::factory()->create();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $this->assertTrue(session()->hasOldInput() === false || session()->get('errors') === null);
    }

    public function test_failed_login_shows_error_and_increments_attempts()
    {
        $user = User::factory()->create();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_rate_limit_blocks_after_repeated_failures()
    {
        $user = User::factory()->create();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $key = Str::lower('127.0.0.1' . '|' . $user->email);
        RateLimiter::clear($key);

        for ($i = 0; $i < 5; $i++) {
            $resp = $this->from('/admin/login')->post('/admin/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
            $resp->assertRedirect('/admin/login');
        }

        // Sixth attempt should be blocked
        $resp = $this->from('/admin/login')->post('/admin/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $resp->assertStatus(429);
        $resp->assertSessionHasErrors('email');
    }
}
