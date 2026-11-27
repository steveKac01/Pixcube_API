<?php

namespace App\Tests\Api;

use App\Entity\RefreshToken;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
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

    public function testLoginReturnsTokens(): void
    {
        $this->register();
        $this->login();

        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayHasKey('token', $this->getData());
        $this->assertArrayHasKey('refresh_token', $this->getData());
    }

    public function testLoginRejectGetMethod(): void
    {
        $this->client->jsonRequest('GET', '/api/login', [
            'email' => 'user@test.fr',
            'password' => 'Password123!',
        ]);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testLoginRejectsWrongPassword(): void
    {

        $this->register();
        $this->login(password: 'wrongPassword1!');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeRouteRequiresToken(): void
    {
        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRouteRefreshTokenGiveNewToken(): void
    {
        $this->register();
        $this->login();
        $token = $this->getData()['token'];
        $refresh_token = $this->getData()['refresh_token'];

        sleep(1); // For let the timestamp change

        // Utilisation de jsonRequest pour envoyer le refresh_token au format JSON
        $this->client->jsonRequest('POST', '/api/token/refresh', [
            'refresh_token' => $refresh_token
        ]);

        $data = $this->getData();

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotSame($token, $data['token']);
        $this->assertNotEmpty($data['refresh_token']);
        $this->assertSame($refresh_token, $data['refresh_token']);
    }

    public function testRouteRefreshRejectInvalidRefreshToken(): void
    {
        $this->client->jsonRequest('POST', '/api/token/refresh', [
            'refresh_token' => 'invalid_token'
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRouteRefreshRejectRefreshTokenExpired(): void
    {
        $this->register();
        $this->login();
        $refresh_token = $this->getData()['refresh_token'];

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $refreshTokenEntity = $em->getRepository(RefreshToken::class)->findOneBy(['refreshToken' => $refresh_token]);

        if ($refreshTokenEntity) {
            $refreshTokenEntity->setValid(new \DateTime('-31 day'));
            $em->flush();
        }

        $this->client->jsonRequest('POST', '/api/token/refresh', [
            'refresh_token' => $refresh_token
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeRouteErrorWithBadToken(): void
    {
        $this->client->request('GET', '/api/me', server: [
            'HTTP_AUTHORIZATION' => 'Bearer notAToken',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeRouteReturnsCurrentUserInfos(): void
    {
        $this->register();
        $this->login();
        $token = $this->getData()['token'];

        $this->client->request('GET', '/api/me', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(self::EMAIL, $this->getData()['email']);
        $this->assertArrayHasKey('roles', $this->getData());
        $this->assertContains('ROLE_USER', $this->getData()['roles']);
    }
}
