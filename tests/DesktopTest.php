<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DesktopTest extends WebTestCase
{
    public function testHello(): void
    {
        $client = static::createClient();
        $client->request('GET', '/hello');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Hello from Symfony Desktop', $client->getResponse()->getContent());
    }

    public function testDesktopRequiresTokenAndExchangesItForCookie(): void
    {
        $_SERVER['DESKTOP_TOKEN'] = 'test-token';
        try {
            $client = static::createClient();
            $client->request('GET', '/');
            self::assertResponseStatusCodeSame(403);
            $client->request('GET', '/?desktop_token=test-token');
            self::assertResponseRedirects('/');
            $client->request('GET', '/hello');
            self::assertResponseIsSuccessful();
        } finally {
            unset($_SERVER['DESKTOP_TOKEN']);
        }
    }
}
