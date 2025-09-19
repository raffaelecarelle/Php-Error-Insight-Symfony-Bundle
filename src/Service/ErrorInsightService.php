<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Service;

use ErrorExplainer\Config;
use ErrorExplainer\ErrorExplainer;
use ErrorExplainer\Internal\ErrorHandler;

final class ErrorInsightService
{
    private ?ErrorExplainer $errorExplainer = null;

    public function __construct(
        private readonly bool $enabled,
        private readonly string $backend,
        private readonly ?string $model,
        private readonly string $language,
        private readonly string $output,
        private readonly bool $verbose,
        private readonly ?string $apiKey,
        private readonly ?string $apiUrl,
        private readonly ?string $template,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getErrorExplainer(): ?ErrorExplainer
    {
        if (!$this->enabled) {
            return null;
        }

        if (!$this->errorExplainer instanceof ErrorExplainer) {
            $config = [
                'enabled' => $this->enabled,
                'backend' => $this->backend,
                'model' => $this->model,
                'language' => $this->language,
                'output' => $this->output,
                'verbose' => $this->verbose,
                'apiKey' => $this->apiKey,
                'apiUrl' => $this->apiUrl,
                'template' => $this->template,
            ];

            $this->errorExplainer = ErrorExplainer::register($config);
        }

        return $this->errorExplainer;
    }

    public function getConfig(): ?Config
    {
        $explainer = $this->getErrorExplainer();
        if (!$explainer instanceof ErrorExplainer) {
            return null;
        }

        return ErrorExplainer::getConfig();
    }

    public function renderException(\Throwable $exception): string
    {
        $explainer = $this->getErrorExplainer();
        if (!$explainer instanceof ErrorExplainer) {
            return '';
        }

        // Capture the output from ErrorExplainer
        ob_start();

        try {
            // Trigger the error handler manually to get the rendered output
            $config = $this->getConfig();
            if ($config instanceof Config) {
                // For HTML output, we need to generate the error page
                $handler = new ErrorHandler($config);
                $handler->handleException($exception);
            }
        } catch (\Throwable) {
        } finally {
            $output = ob_get_clean();
        }

        return $output ?: '';
    }
}
