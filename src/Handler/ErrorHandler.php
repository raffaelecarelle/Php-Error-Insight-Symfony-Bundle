<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Handler;

use ErrorExplainer\Config;
use ErrorExplainer\Internal\ErrorHandler as CustomErrorHandler;
use Symfony\Component\ErrorHandler\ErrorHandler as SymfonyErrorHandler;

class ErrorHandler extends SymfonyErrorHandler
{
    private readonly CustomErrorHandler $customHandler;

    public function __construct(?Config $config = null)
    {
        parent::__construct();

        if (!$config instanceof \ErrorExplainer\Config) {
            $config = Config::fromEnvAndArray();
        }

        $this->customHandler = new CustomErrorHandler($config);
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
