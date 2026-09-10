<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getCacheDir(): string
    {
        return ($_SERVER['APP_DATA_DIR'] ?? $_ENV['APP_DATA_DIR'] ?? $this->getProjectDir().'/var').'/cache/'.($_SERVER['APP_BUILD_ID'] ?? 'web').'/'.$this->environment;
    }

    public function getShareDir(): ?string
    {
        return ($_SERVER['APP_DATA_DIR'] ?? $_ENV['APP_DATA_DIR'] ?? $this->getProjectDir().'/var').'/share/'.($_SERVER['APP_BUILD_ID'] ?? 'web').'/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return ($_SERVER['APP_DATA_DIR'] ?? $_ENV['APP_DATA_DIR'] ?? $this->getProjectDir().'/var').'/log';
    }

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
