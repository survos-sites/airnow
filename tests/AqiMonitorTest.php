<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Observation;
use App\Repository\ObservationRepository;
use App\Service\AqiMonitor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Survos\FetchBundle\Service\PersistentFetcher;
use Survos\FetchBundle\Retry\ExponentialBackoffRetry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class AqiMonitorTest extends KernelTestCase
{
    public function testRefreshPersistsAllPollutantsWithoutDuplicatingHistory(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $schema = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
        $http = new MockHttpClient(new MockResponse(file_get_contents(__DIR__.'/fixtures/observations.json')));
        $container->set('survos_airnow.fetcher', new PersistentFetcher($http, new ArrayAdapter(), new ExponentialBackoffRetry()));
        $monitor = $container->get(AqiMonitor::class);
        self::assertSame(3, $monitor->refresh());
        self::assertSame(3, $monitor->refresh());
        self::assertSame(1, $http->getRequestsCount());
        $em->clear();
        $repository = $container->get(ObservationRepository::class);
        self::assertSame(3, $repository->count([]));
        $latest = $repository->latest('20002');
        self::assertCount(3, $latest);
        self::assertSame(35, $latest[0]->aqi);
        self::assertSame('110010041', $latest[0]->siteId);
        self::assertSame('07:00', $latest[0]->hourObserved);
    }
}
