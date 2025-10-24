<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle;

use ErrorExplainer\Config;
use ErrorExplainer\ErrorExplainer;
use PhpErrorInsightBundle\DependencyInjection\PhpErrorInsightExtension;
use PhpErrorInsightBundle\Handler\ErrorHandler;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\ErrorHandler\ErrorHandler as SymfonyErrorHandler;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Debug\ErrorHandlerConfigurator;

final class PhpErrorInsightBundle extends Bundle
{
    private ?SymfonyErrorHandler $errorHandler = null;

    public function __construct()
    {
        $this->registerErrorHandler();
    }

    private function registerErrorHandler(): void
    {
        ErrorExplainer::register();

        $customHandler = new ErrorHandler(Config::fromEnvAndArray());

        $this->errorHandler = SymfonyErrorHandler::register($customHandler);
    }

    public function boot(): void
    {
        if ($this->errorHandler && $this->container?->has('debug.error_handler_configurator')) {
            /** @var null|ErrorHandlerConfigurator $configurator */
            $configurator = $this->container->get('debug.error_handler_configurator');
            $configurator?->configure($this->errorHandler);
        }
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new PhpErrorInsightExtension();
        }

        return $this->extension instanceof ExtensionInterface ? $this->extension : null;
    }
}
