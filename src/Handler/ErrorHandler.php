<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Handler;

use ErrorExplainer\Config;
use ErrorExplainer\ErrorExplainer;
use ErrorExplainer\Internal\ErrorHandler as CustomErrorHandler;

class ErrorHandler
{
    private readonly CustomErrorHandler $customHandler;

    public function __construct(?Config $config = null)
    {
        if (!$config instanceof Config) {
            $config = Config::fromEnvAndArray();
        }

        $this->customHandler = new CustomErrorHandler($config);
    }

    public static function register(bool $debug): self
    {
        ErrorExplainer::register(['verbose' => $debug]);

        return new self();
    }

    public function handleError(int $type, string $message, string $file, int $line): bool
    {
        return $this->customHandler->handleError($type, $message, $file, $line);
    }

    public function handleException(\Throwable $exception): void
    {
        $this->customHandler->handleException($exception);
    }
}
