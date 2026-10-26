<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_credentials_return_a_bearer_token(): void
    {
        $user = User::factory()->create(['password' => 'password', 'is_admin' => true]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Success')
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.is_admin', true);
        $this->assertNotEmpty($response->json('authorization.token'));
    }
}
