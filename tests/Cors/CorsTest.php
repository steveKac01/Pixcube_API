<?php

namespace App\Tests\Cors;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CorsTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testAllowedOriginGetsCorsHeaders(): void
    {

        $this->client->request('OPTIONS', '/api/games', server: [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertResponseHeaderSame('Access-Control-Allow-Origin', 'http://localhost:5173');
    }

    public function testUnknownOriginGetsNoCorsHeaders(): void
    {

        $this->client->request('OPTIONS', '/api/games', server: [
            'HTTP_ORIGIN' => 'https://evil.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertResponseNotHasHeader('Access-Control-Allow-Origin');
    }
}
