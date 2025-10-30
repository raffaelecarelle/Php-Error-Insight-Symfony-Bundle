<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Add conditionally extra phpstan rules
 * Typically used for specific PHP versions
 * Must be called in main PHPStan neon config file
 */

$includes = [];

if (str_starts_with(\Composer\InstalledVersions::getVersion('symfony/framework-bundle'), 'v7.')) {
    $includes[] = __DIR__ . '/phpstan-rules-sf-5.4.neon';
}

$config = [];
$config['includes'] = $includes;
$config['parameters']['phpVersion'] = PHP_VERSION_ID;

return $config;