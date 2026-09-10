<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\RefreshState;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Survos\FetchBundle\Service\PersistentFetcher;
use Survos\FetchBundle\Retry\ExponentialBackoffRetry;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class DesktopFlowTest extends WebTestCase
{
    public function testSettingsRefreshHistoryAndFailure(): void
    {
        $_SERVER['AIRNOW_API_KEY'] = $_ENV['AIRNOW_API_KEY'] = ''; 
        $client = static::createClient();
        $client->disableReboot();
        $container = self::getContainer();
        $path = $container->getParameter('app.data_dir').'/settings.json';
        @unlink($path);
        $em = $container->get(EntityManagerInterface::class);
        $schema = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
        try {
            $client->request('GET', '/');
            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('h2', 'A little setup');
            $client->request('GET', '/settings');
            $client->submitForm('Save settings', ['settings[zipCode]' => 'oops']);
            self::assertSelectorTextContains('form', 'five-digit');
            self::assertFileDoesNotExist($path);
            $client->submitForm('Save settings', ['settings[zipCode]' => '20002', 'settings[apiKey]' => 'fixture-key']);
            self::assertResponseRedirects('/');
            $client->followRedirect();
            self::assertSelectorTextContains('h2', 'No observations');
            self::assertStringNotContainsString('fixture-key', $client->getResponse()->getContent());
            $http = new MockHttpClient([
                new MockResponse(file_get_contents(__DIR__.'/fixtures/observations.json')),
                new MockResponse('denied', ['http_code' => 401]),
                new MockResponse('[]'),
            ]);
            $container->set('survos_airnow.fetcher', new PersistentFetcher($http, new ArrayAdapter(), new ExponentialBackoffRetry()));
            $client->submitForm('Refresh now');
            $client->followRedirect();
            self::assertSelectorTextContains('.aqi', '35');
            self::assertSelectorCount(3, '.pollutants li');
            $client->submitForm('Refresh now');
            $client->followRedirect();
            self::assertSelectorTextContains('[role=alert]', 'previously saved');
            self::assertSelectorTextContains('.aqi', '35');
            $client->submitForm('Refresh now');
            $client->followRedirect();
            self::assertSelectorTextContains('h2', 'No observations');
            self::assertSelectorNotExists('.aqi');
            $client->request('GET', '/history');
            self::assertSelectorCount(3, '.history-row');
            $client->request('GET', '/settings');
            self::assertInputValueSame('settings[apiKey]', '');
            $client->submitForm('Save settings', ['settings[zipCode]' => '10001']);
            self::assertSame('fixture-key', json_decode(file_get_contents($path), true)['apiKey']);
            $client->followRedirect();
            self::assertSelectorNotExists('.aqi');
            $client->request('POST', '/refresh', ['_token' => 'invalid']);
            self::assertResponseStatusCodeSame(403);
        } finally {
            @unlink($path);
            $_SERVER['AIRNOW_API_KEY'] = $_ENV['AIRNOW_API_KEY'] = 'test-only';
        }
    }
}
