<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\EventListener;

use PhpErrorInsightBundle\Service\ErrorInsightService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ExceptionListener
{
    public function __construct(
        private readonly ErrorInsightService $errorInsightService,
        private readonly bool $enabled,
        private readonly bool $overrideSymfonyErrors,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // Only handle if enabled and configured to override Symfony errors
        if (!$this->enabled || !$this->overrideSymfonyErrors) {
            return;
        }

        // Don't handle in production environment for security
        if ('prod' === ($_SERVER['APP_ENV'] ?? 'prod')) {
            return;
        }

        $exception = $event->getThrowable();

        // For CLI/console, let the original handler manage it
        if ('cli' === \PHP_SAPI) {
            return;
        }

        try {
            $content = $this->errorInsightService->renderException($exception);

            if ('' === $content || '0' === $content) {
                // If we can't render with PHP Error Insight, let Symfony handle it
                return;
            }

            $statusCode = 500;
            $headers = [];

            // If it's an HTTP exception, preserve the status code and headers
            if ($exception instanceof HttpExceptionInterface) {
                $statusCode = $exception->getStatusCode();
                $headers = $exception->getHeaders();
            }

            $response = new Response($content, $statusCode, $headers);
            $response->headers->set('Content-Type', 'text/html; charset=utf-8');

            $event->setResponse($response);
        } catch (\Throwable $throwable) {
            // If there's an error in rendering, don't interfere with Symfony's error handling
            // Log the error if possible but don't break the application
            error_log(\sprintf(
                'PHP Error Insight Bundle failed to render exception: %s',
                $throwable->getMessage()
            ));
        }
    }
}
