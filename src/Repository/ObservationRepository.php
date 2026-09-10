<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Observation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Observation> */
final class ObservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Observation::class); }

    /** @return list<Observation> */
    public function history(string $zipCode): array
    {
        return $this->findBy(['zipCode' => $zipCode], ['dateObserved' => 'DESC', 'hourObserved' => 'DESC', 'parameterName' => 'ASC'], 200);
    }

    /** Most recent response batch; never blend pollutants from older refreshes. @return list<Observation> */
    public function latest(string $zipCode): array
    {
        $last = $this->findOneBy(['zipCode' => $zipCode], ['fetchedAt' => 'DESC']);
        return $last ? $this->findBy(['zipCode' => $zipCode, 'fetchedAt' => $last->fetchedAt], ['aqi' => 'DESC']) : [];
    }
}
