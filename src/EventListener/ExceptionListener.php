<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\EventListener;

use PhpErrorInsightBundle\Service\ErrorInsightService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ExceptionListener
{
    public function __construct(
        private readonly ErrorInsightService $errorInsightService,
        private readonly bool $enabled,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // Only handle if enabled and configured to override Symfony errors
        if (!$this->enabled) {
            return;
        }

        $exception = $event->getThrowable();

        try {
            $content = $this->errorInsightService->renderException($exception);

            if ('' === $content || '0' === $content) {
                // If we can't render with PHP Error Insight, let Symfony handle it
                return;
            }

            if ($exception instanceof HttpException) {
                $statusCode = $exception->getStatusCode();
            } else {
                $statusCode = $exception->getCode();
            }

            $response = new Response($content, $statusCode);
            $response->headers->set('Content-Type', 'text/html; charset=utf-8');

            $event->setResponse($response);
        } catch (\Throwable) {
            // If there's an error in rendering, don't interfere with Symfony's error handling
            // Log the error if possible but don't break the application
        }
    }
}
