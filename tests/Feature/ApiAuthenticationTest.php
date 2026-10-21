<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_password_is_hashed(): void
    {
        $response = $this->postJson('/api/register', [
            'nome' => 'Fulano da Silva',
            'email' => 'FULANO@gmail.com',
            'password' => 'EDW999#senha',
            'password_confirmation' => 'EDW999#senha',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.nome', 'Fulano da Silva')
            ->assertJsonPath('user.email', 'fulano@gmail.com')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.password_confirmation');

        $this->assertDatabaseHas('users', ['email' => 'fulano@gmail.com']);
        $this->assertDatabaseMissing('users', ['password' => 'EDW999#senha']);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'fulano@gmail.com']);

        $this->postJson('/api/register', [
            'nome' => 'Outro usuário',
            'email' => 'FULANO@gmail.com',
            'password' => 'EDW999#senha',
            'password_confirmation' => 'EDW999#senha',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_validates_required_fields_email_and_confirmation(): void
    {
        $this->postJson('/api/register', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['nome', 'email', 'password', 'password_confirmation']);

        $this->postJson('/api/register', [
            'nome' => 'Fulano',
            'email' => 'email-invalido',
            'password' => 'EDW999#senha',
            'password_confirmation' => 'outra-senha',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_can_login_and_receive_bearer_token(): void
    {
        User::factory()->create([
            'email' => 'fulano@gmail.com',
            'password' => 'EDW999#senha',
        ]);

        $this->postJson('/api/login', [
            'email' => 'FULANO@gmail.com',
            'password' => 'EDW999#senha',
        ])->assertOk()
            ->assertJsonPath('user.email', 'fulano@gmail.com')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonMissingPath('user.password');
    }

    public function test_login_rejects_wrong_password_and_unknown_user_with_same_error(): void
    {
        User::factory()->create(['email' => 'fulano@gmail.com']);

        $wrongPassword = $this->postJson('/api/login', [
            'email' => 'fulano@gmail.com',
            'password' => 'senha-incorreta',
        ]);
        $unknownUser = $this->postJson('/api/login', [
            'email' => 'inexistente@gmail.com',
            'password' => 'senha-incorreta',
        ]);

        $wrongPassword->assertUnauthorized()->assertExactJson([
            'message' => 'As credenciais informadas são inválidas.',
        ]);
        $unknownUser->assertUnauthorized()->assertExactJson([
            'message' => 'As credenciais informadas são inválidas.',
        ]);
    }

    public function test_login_validates_required_and_malformed_fields(): void
    {
        $this->postJson('/api/login', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);

        $this->postJson('/api/login', [
            'email' => 'nao-e-email',
            'password' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_private_route_rejects_missing_and_invalid_tokens(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();

        $this->withHeader('Authorization', 'Bearer token-invalido')
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_me_without_exposing_password(): void
    {
        $user = User::factory()->create(['nome' => 'Fulano']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.nome', 'Fulano')
            ->assertJsonMissingPath('user.password');
    }

    public function test_logout_revokes_current_token_and_prevents_reuse(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('teste')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_all_revokes_every_user_token(): void
    {
        $user = User::factory()->create();
        $firstToken = $user->createToken('primeiro')->plainTextToken;
        $user->createToken('segundo');

        $this->withToken($firstToken)->postJson('/api/logout-all')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->app['auth']->forgetGuards();
        $this->withToken($firstToken)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => 'inexistente@gmail.com',
                'password' => 'senha-incorreta',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'inexistente@gmail.com',
            'password' => 'senha-incorreta',
        ])->assertTooManyRequests();
    }
}
