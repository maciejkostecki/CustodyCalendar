<?php

namespace Tests\Feature;

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $repo = \Illuminate\Support\Env::getRepository();
        $repo->set('ALLOWED_PARENT_1_EMAIL', 'allowed@example.com');
        $repo->set('ALLOWED_PARENT_2_EMAIL', '');
        $repo->set('FRONTEND_URL', 'http://localhost:5174');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockSocialiteUser(string $email, string $name = 'Test User', string $avatar = 'https://example.com/avatar.jpg'): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getAvatar')->andReturn($avatar);

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_allowed_email_sets_session_and_redirects_to_frontend(): void
    {
        $this->mockSocialiteUser('allowed@example.com', 'Allowed User', 'https://example.com/avatar.jpg');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('http://localhost:5174/?auth=ok');
        $response->assertSessionHas('user', [
            'email'  => 'allowed@example.com',
            'name'   => 'Allowed User',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);
    }

    public function test_denied_email_redirects_to_access_denied_and_no_session(): void
    {
        $this->mockSocialiteUser('denied@example.com', 'Denied User', '');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('http://localhost:5174/login?error=access_denied');
        $response->assertSessionMissing('user');
    }

    public function test_oauth_exception_redirects_to_oauth_failed(): void
    {
        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('user')->andThrow(new \Exception('OAuth error'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('http://localhost:5174/login?error=oauth_failed');
    }

    public function test_me_returns_401_when_no_session(): void
    {
        $response = $this->getJson('/me');

        $response->assertStatus(401);
        $response->assertJsonStructure(['error']);
        $response->assertJson(['error' => 'Unauthenticated']);
    }

    public function test_me_returns_user_json_with_active_session(): void
    {
        $user = [
            'email'  => 'test@example.com',
            'name'   => 'Test',
            'avatar' => '',
        ];

        $response = $this->withSession(['user' => $user])->getJson('/me');

        $response->assertStatus(200);
        $response->assertJson($user);
    }

    public function test_logout_clears_session_and_returns_ok(): void
    {
        $user = [
            'email'  => 'test@example.com',
            'name'   => 'Test',
            'avatar' => '',
        ];

        $response = $this->withSession(['user' => $user])->postJson('/logout');

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
        $response->assertSessionMissing('user');
    }
}
