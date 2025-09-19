<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Service;

use ErrorExplainer\Config;
use ErrorExplainer\ErrorExplainer;

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

    /**
     * @return null|array{message: string, file: string, line: int, trace: array<mixed>, class: string, severity: null}
     */
    public function handleException(\Throwable $exception): ?array
    {
        $explainer = $this->getErrorExplainer();
        if (!$explainer instanceof ErrorExplainer) {
            return null;
        }

        // Get the Internal\Explainer instance to generate explanation
        $config = $this->getConfig();
        if (!$config instanceof Config) {
            return null;
        }

        // Create a simple explanation array that can be used by Symfony
        return [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
            'class' => $exception::class,
            'severity' => null, // Exceptions don't have severity levels
        ];
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
            if ($config instanceof Config && Config::OUTPUT_HTML === $config->output) {
                // For HTML output, we need to generate the error page
                $explanation = $this->handleException($exception);
                if (null !== $explanation) {
                    // This would render using the same template system as the main package
                    return $this->renderHtmlError($explanation);
                }
            }
        } finally {
            $output = ob_get_clean();
        }

        return $output ?: '';
    }

    /**
     * @param array{message: string, file: string, line: int, trace: array<mixed>, class: string, severity: null} $explanation
     */
    private function renderHtmlError(array $explanation): string
    {
        // Basic HTML template for now - can be enhanced with Twig later
        $template = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        .error-container { max-width: 800px; margin: 0 auto; }
        .error-header { background: #dc3545; color: white; padding: 1rem; border-radius: 4px; }
        .error-details { background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 1rem; }
        .trace { background: #e9ecef; padding: 1rem; border-radius: 4px; margin-top: 1rem; overflow-x: auto; }
        pre { margin: 0; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <h1>Application Error</h1>
            <p><strong>{$explanation['class']}</strong></p>
        </div>
        <div class="error-details">
            <h2>Message</h2>
            <p>{$explanation['message']}</p>
            <h3>Location</h3>
            <p><strong>File:</strong> {$explanation['file']}<br>
            <strong>Line:</strong> {$explanation['line']}</p>
        </div>
        <div class="trace">
            <h3>Stack Trace</h3>
            <pre>{\$this->formatTrace(\$exception->getTraceAsString())}</pre>
        </div>
    </div>
</body>
</html>
HTML;

        return $template;
    }
}
