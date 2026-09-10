<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class RefreshState
{
    #[ORM\Id, ORM\Column(length: 5)]
    public string $zipCode;
    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $checkedAt = null;
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $error = null;
    /** IDs from the most recent successful response, including an empty response. */
    #[ORM\Column(type: 'json')]
    public array $observationIds = [];

    public function __construct(string $zipCode) { $this->zipCode = $zipCode; }
}
