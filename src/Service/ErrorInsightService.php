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

    public function getConfig(): ?Config
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
            'verbose' => $this->verbose,
            'apiKey' => $this->apiKey,
            'apiUrl' => $this->apiUrl,
            'template' => $this->template,
            'editorUrl' => $this->editorUrl,
            'projectRoot' => $this->projectRoot,
            'hostProjectRoot' => $this->hostRoot,
        ]);
    }

    public function renderException(\Throwable $exception): string
    {
        if (!$this->enabled) {
            return '';
        }

        ob_start();

        try {
            $config = $this->getConfig();
            if (!$config instanceof Config) {
                return '';
            }

            $handler = new ErrorHandler($config);
            $handler->handleException($exception);
        } catch (\Throwable) {
        } finally {
            $output = ob_get_clean();
        }

        return $output ?: '';
    }
}
