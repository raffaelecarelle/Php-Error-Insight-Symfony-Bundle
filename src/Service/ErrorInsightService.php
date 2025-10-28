<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Service;

use PhpErrorInsight\Config;
use PhpErrorInsight\Internal\ErrorHandler;

final class ErrorInsightService
{
    public function __construct(
        private readonly bool $enabled,
        private readonly string $backend,
        private readonly ?string $model,
        private readonly string $language,
        private readonly string $output,
        private readonly bool $verbose,
        private readonly ?string $apiKey = null,
        private readonly ?string $apiUrl = null,
        private readonly ?string $template = null,
        private readonly ?string $editorUrl = null,
        private readonly ?string $projectRoot = null,
        private readonly ?string $hostRoot = null,
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
