<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Observation;
use App\Repository\ObservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Survos\AirNowBundle\Service\AirNowClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;

final readonly class AqiMonitor
{
    public function __construct(private AirNowClient $client, private Settings $settings, private ObservationRepository $observations, private EntityManagerInterface $em, private LockFactory $locks) {}

    public function refresh(bool $force = false): int
    {
        $lock = $this->locks->createLock('airnow-refresh');
        if (!$lock->acquire()) { return 0; }
        try {
            $zip = $this->settings->zipCode();
            $rows = $this->client->currentObservationsByZip($zip, force: $force);
            $fetchedAt = new \DateTimeImmutable();
            foreach ($rows as $dto) {
                $entity = $this->observations->find(Observation::identity($zip, $dto));
                if ($entity === null) { $entity = new Observation($zip, $dto); $this->em->persist($entity); }
                else { $entity->update($dto); }
                $entity->fetchedAt = $fetchedAt;
            }
            $this->em->flush();
            return count($rows);
        } finally { $lock->release(); }
    }

    #[AsCommand('aqi:fetch', 'Fetch and retain current AirNow observations')]
    public function fetch(SymfonyStyle $io, #[Option('Bypass the cached AirNow response')] bool $force = false): int
    {
        try {
            $io->success(sprintf('Saved %d observations for %s.', $this->refresh($force), $this->settings->zipCode()));
            return Command::SUCCESS;
        } catch (\Survos\AirNowBundle\Exception\AirNowException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
