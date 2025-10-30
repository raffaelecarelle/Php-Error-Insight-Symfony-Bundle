<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\EventListener;

use PhpErrorInsightBundle\Service\ErrorInsightService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\LazyResponseException;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Http\Firewall\ExceptionListener as SymfonyExceptionListener;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

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

        // Only handle main requests; let Symfony handle sub-requests and special flows
        if (!$event->isMainRequest()) {
            return;
        }

        // Defer to Symfony Security when it is responsible for handling the exception
        if ($this->shouldDeferToSecurity($event)) {
            return;
        }

        $throwable = $event->getThrowable();

        try {
            // Use Symfony's debug flag to drive verbosity at runtime
            $content = $this->errorInsightService->renderException($throwable, $this->debug);

            if ('' === $content || '0' === $content) {
                // If we can't render with PHP Error Insight, let Symfony handle it
                return;
            }

            if ($throwable instanceof HttpException) {
                $statusCode = $throwable->getStatusCode();
                $response = new Response($content, $statusCode);
            } else {
                $request = $this->duplicateRequest($throwable, $event->getRequest());
                $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);
                $response->setContent($content);
            }

            $event->setResponse($response);
        } catch (\Throwable) {
            // If there's an error in rendering, don't interfere with Symfony's error handling
            // Log the error if possible but don't break the application
        }
    }

    private function shouldDeferToSecurity(ExceptionEvent $event): bool
    {
        $throwable = $event->getThrowable();

        if (
            !class_exists(SecurityBundle::class)
            || !class_exists(SymfonyExceptionListener::class)
        ) {
            return false;
        }

        // 1) Exception chain includes Security-related exceptions
        for ($e = $throwable; $e instanceof \Throwable; $e = $e->getPrevious()) {
            if ($e instanceof AuthenticationException
                || $e instanceof AccessDeniedException
                || $e instanceof LogoutException
                || $e instanceof LazyResponseException) {
                return true;
            }
        }

        // 2) Standard 401/403 HttpException should be handled by Security (entry points/denied handlers)
        if ($throwable instanceof HttpException) {
            $status = $throwable->getStatusCode();
            if (401 === $status || 403 === $status) {
                return true;
            }
        }

        // 3) Best-effort request attributes set by Security flows
        $request = $event->getRequest();
        return $request->attributes->has(SecurityRequestAttributes::AUTHENTICATION_ERROR)
            || $request->attributes->has(SecurityRequestAttributes::ACCESS_DENIED_ERROR);
    }
}
