<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    public function testHomePage(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'Accueil');
        self::assertSelectorTextContains('body', 'Hello world');
        self::assertSelectorExists('script[type="importmap"]');
        self::assertSelectorExists('link[rel="icon"]');
    }
}
