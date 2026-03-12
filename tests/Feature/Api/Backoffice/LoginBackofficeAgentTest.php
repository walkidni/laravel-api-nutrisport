<?php

namespace Tests\Feature\Api\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginBackofficeAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_logs_in_a_backoffice_agent_with_email_and_password(): void
    {
        $response = $this->postJson('/v1/backoffice/auth/login', [
            'email' => 'agent@example.com',
            'password' => 'secret-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                ],
            ]);
    }
}
