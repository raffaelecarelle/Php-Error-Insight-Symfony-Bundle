<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Service;

use PhpErrorInsight\Config;
use PhpErrorInsight\Internal\ErrorHandler;

final readonly class ErrorInsightService
{
    public function __construct(
        private bool $enabled,
        private string $backend,
        private ?string $model,
        private string $language,
        private string $output,
        private bool $verbose,
        private ?string $apiKey = null,
        private ?string $apiUrl = null,
        private ?string $template = null,
        private ?string $editorUrl = null,
        private ?string $projectRoot = null,
        private ?string $hostRoot = null,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Build the effective config, optionally overriding verbosity at runtime.
     */
    public function getConfig(?bool $verboseOverride = null): ?Config
    {
        if (!$this->enabled) {
            return null;
        }

        return new Config([
            'enabled' => $this->enabled,
            'backend' => $this->backend,
            'model' => $this->model,
            'language' => $this->language,
            'output' => $this->output,
            'verbose' => $verboseOverride ?? $this->verbose,
            'apiKey' => $this->apiKey,
            'apiUrl' => $this->apiUrl,
            'template' => $this->template,
            'editorUrl' => $this->editorUrl,
            'projectRoot' => $this->projectRoot,
            'hostProjectRoot' => $this->hostRoot,
        ]);
    }

    /**
     * Render the exception according to the effective configuration.
     *
     * When $verboseOverride is provided, it controls whether to include extra
     * details like stack traces. This allows wiring Symfony's debug/verbosity
     * flags to the core library at runtime.
     */
    public function renderException(\Throwable $exception, ?bool $verboseOverride = null): string
    {
        if (!$this->enabled) {
            return '';
        }

        ob_start();

        try {
            $config = $this->getConfig($verboseOverride);
            if (!$config instanceof Config) {
                return '';
            }

            $handler = new ErrorHandler($config);
            $handler->handleException($exception);
        } catch (\Throwable) {
            // Swallow to avoid interfering with host error handling; let Symfony continue.
        } finally {
            $output = ob_get_clean();
        }

        return $output ?: '';
    }
}
