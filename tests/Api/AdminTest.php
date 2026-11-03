<?php

namespace App\Tests\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminTest extends WebTestCase
{

    private const EMAIL = 'user@test.fr';
    private const PASSWORD = 'Password123!';
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    private function loginAs(bool $admin): string
    {

        $this->client->jsonRequest('POST', '/api/register', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);

        if ($admin) {
            $em = static::getContainer()->get(EntityManagerInterface::class);
            $user = $em->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
            $user->setRoles([User::ROLE_ADMIN]);
            $em->flush();
        }

        $this->client->jsonRequest('POST', '/api/login', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);

        return json_decode($this->client->getResponse()->getContent(), true)['token'];
    }

    public function testAdminRouteRequiresToken(): void
    {
        $this->client->request('GET', '/api/admin/check');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testAdminRouteRejectRegularUser(): void
    {
        $token = $this->loginAs(false);
        $this->client->request('GET', '/api/admin/check', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminRouteAllowedForAdmin(): void
    {
        $token = $this->loginAs(true);
        $this->client->request('GET', '/api/admin/check', server: [
            'HTTP_AUTHORIZATION' => 'bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(200);
    }
}
