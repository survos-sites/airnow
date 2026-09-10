<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ObservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Survos\AirNowBundle\Dto\Observation as AirNowObservation;

#[ORM\Entity(repositoryClass: ObservationRepository::class)]
#[ORM\Index(columns: ['zip_code', 'date_observed', 'hour_observed'])]
final class Observation
{
    #[ORM\Id, ORM\Column(length: 64)]
    public readonly string $id;
    #[ORM\Column(length: 5)] public string $zipCode;
    #[ORM\Column(length: 10)] public string $dateObserved;
    #[ORM\Column(length: 5)] public string $hourObserved;
    #[ORM\Column(length: 10)] public string $localTimeZone;
    #[ORM\Column(length: 40)] public string $siteId;
    #[ORM\Column(length: 255)] public string $siteName;
    #[ORM\Column(length: 255)] public string $reportingAreaName;
    #[ORM\Column(length: 30)] public string $parameterName;
    #[ORM\Column] public int $aqi;
    #[ORM\Column(length: 80)] public string $categoryName;
    #[ORM\Column(type: 'json')] public array $source;
    #[ORM\Column] public \DateTimeImmutable $fetchedAt;

    public function __construct(string $zipCode, AirNowObservation $dto)
    {
        $this->id = self::identity($zipCode, $dto);
        $this->zipCode = $zipCode;
        $this->update($dto);
    }

    public static function identity(string $zipCode, AirNowObservation $dto): string
    {
        return hash('sha256', implode('|', [$zipCode, $dto->dateObserved, $dto->hourObserved, $dto->localTimeZone, $dto->siteId, $dto->parameterName]));
    }

    public function update(AirNowObservation $dto): void
    {
        foreach (['dateObserved', 'hourObserved', 'localTimeZone', 'siteId', 'siteName', 'reportingAreaName', 'parameterName', 'aqi', 'categoryName'] as $field) {
            $this->$field = $dto->$field;
        }
        $this->source = get_object_vars($dto);
        $this->fetchedAt = new \DateTimeImmutable();
    }
}
