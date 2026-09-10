<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use App\Service\Settings;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/** Resolve editable preferences when the ordinary bundle client is instantiated. */
final readonly class AirNowKeyProcessor implements EnvVarProcessorInterface
{
    public function __construct(private Settings $settings) {}
    public function getEnv(string $prefix, string $name, \Closure $getEnv): string { return $this->settings->apiKey(); }
    public static function getProvidedTypes(): array { return ['airnow_key' => 'string']; }
}
