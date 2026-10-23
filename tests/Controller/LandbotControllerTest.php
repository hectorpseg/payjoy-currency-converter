<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LandbotControllerTest extends WebTestCase
{
    public function testHomepageRendersLandbotDemo(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = (string) $client->getResponse()->getContent();

        self::assertStringContainsString('https://cdn.landbot.io/landbot-3/landbot-3.0.0.mjs', $content);
        self::assertStringContainsString('https://storage.googleapis.com/landbot.online/v3/H-3537829-6X9YPV6SP7UC7WBO/index.json', $content);
        self::assertStringContainsString('Landbot.Fullpage', $content);
    }
}
