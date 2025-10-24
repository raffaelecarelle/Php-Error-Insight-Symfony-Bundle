<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\EventListener;

use PhpErrorInsightBundle\Service\ErrorInsightService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ExceptionListener extends ErrorListener
{
    /**
     * @param array<class-string, string>                                                                                   $controller
     * @param array<class-string, array{log_level: null|string, status_code: null|int<100, 599>, log_channel: null|string}> $exceptionsMapping
     */
    public function __construct(
        private readonly ErrorInsightService $errorInsightService,
        private readonly bool $enabled,
        string|object|array|null $controller = null,
        ?LoggerInterface $logger = null,
        bool $debug = false,
        array $exceptionsMapping = [],
    ) {
        parent::__construct($controller, $logger, $debug, $exceptionsMapping);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $throwable = $event->getThrowable();

        try {
            $content = $this->errorInsightService->renderException($throwable);

            if ('' === $content || '0' === $content) {
                // If we can't render with PHP Error Insight, let Symfony handle it
                return;
            }

            if ($throwable instanceof HttpException) {
                $statusCode = $throwable->getStatusCode();
                $response = new Response($content, $statusCode);
                $response->headers->set('Content-Type', 'text/html; charset=utf-8');
            } else {
                $request = $this->duplicateRequest($throwable, $event->getRequest());
                $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);
                $response->setContent($content);
            }

            $event->setResponse($response);

            $event->stopPropagation();
        } catch (\Throwable) {
            // If there's an error in rendering, don't interfere with Symfony's error handling
            // Log the error if possible but don't break the application
        }
    }
}
