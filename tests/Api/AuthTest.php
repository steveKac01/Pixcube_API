<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthTest extends WebTestCase
{
    private const EMAIL = 'user@test.fr';
    private const PASSWORD = 'Password123!';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    private function register(string $email = self::EMAIL, string $password = self::PASSWORD): void
    {
        $this->client->jsonRequest('POST', '/api/register', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    private function login(string $email = self::EMAIL, string $password = self::PASSWORD): void
    {
        $this->client->jsonRequest('POST', '/api/login', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    private function getData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testHealthIsPublic(): void
    {
        $this->client->request('GET', '/api/health');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('API is working', $this->getData()['status']);
    }

    public function testRegisterCreatesUser(): void
    {
        $this->register();

        $this->assertResponseStatusCodeSame(201);
    }

    public function testRegisterNoDuplicateEmail(): void
    {
        $this->register();
        $this->register();

        $this->assertResponseStatusCodeSame(409);
    }

    public function testRegisterNoInvalidEmail(): void
    {
        $this->register(email: 'invalid-email');

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterNoWeakPassword(): void
    {
        $this->register(password: 'password');

        $this->assertResponseStatusCodeSame(422);
    }

    public function testLoginReturnsToken(): void
    {
        $this->register();
        $this->login();

        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayHasKey('token', $this->getData());
    }

    public function testLoginRejectsWrongPassword(): void
    {

        $this->register();
        $this->login(password: 'wrongPassword1!');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedRouteRequiresToken(): void
    {
        $this->client->request('GET', '/api/protected');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedRouteReturnsCurrentUser(): void
    {
        $this->register();
        $this->login();
        $token = $this->getData()['token'];

        $this->client->request('GET', '/api/protected', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(self::EMAIL, $this->getData()['email']);
    }
}
