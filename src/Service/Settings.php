<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

final class Settings
{
    public function __construct(
        private readonly Filesystem $filesystem,
        #[Autowire('%app.data_dir%')] private readonly string $dataDir,
        #[Autowire('%env(AIRNOW_API_KEY)%')] private readonly string $defaultApiKey,
        #[Autowire('%env(AIRNOW_ZIP)%')] private readonly string $defaultZip,
    ) {}

    public function zipCode(): string { return $this->read()['zipCode'] ?? $this->defaultZip; }
    public function apiKey(): string { return $this->read()['apiKey'] ?? $this->defaultApiKey; }
    public function hasApiKey(): bool { return trim($this->apiKey()) !== ''; }

    public function save(string $zipCode, ?string $apiKey): void
    {
        $data = $this->read();
        $data['zipCode'] = $zipCode;
        if ($apiKey !== null && trim($apiKey) !== '') { $data['apiKey'] = trim($apiKey); }
        $this->filesystem->mkdir($this->dataDir, 0700);
        $this->filesystem->dumpFile($this->dataDir.'/settings.json', json_encode($data, JSON_THROW_ON_ERROR));
        $this->filesystem->chmod($this->dataDir.'/settings.json', 0600);
    }

    private function read(): array
    {
        $path = $this->dataDir.'/settings.json';
        return is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];
    }
}
