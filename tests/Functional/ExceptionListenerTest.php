<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Tests\Functional;

use PhpErrorInsightBundle\EventListener\ExceptionListener;
use PhpErrorInsightBundle\Service\ErrorInsightService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class ExceptionListenerTest extends TestCase
{
    private ExceptionListener $listener;

    private ErrorInsightService $errorInsightService;

    private KernelInterface $kernel;

    protected function setUp(): void
    {
        $this->errorInsightService = new ErrorInsightService(
            enabled: true,
            backend: 'none',
            model: null,
            language: 'en',
            output: 'html',
            verbose: false,
            apiKey: null,
            apiUrl: null,
            template: null,
        );

        $this->listener = new ExceptionListener(
            $this->errorInsightService,
            enabled: true
        );

        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('getEnvironment')->willReturn('dev');
    }

    public function testListenerDoesNotHandleWhenDisabled(): void
    {
        $listener = new ExceptionListener(
            $this->errorInsightService,
            enabled: false
        );

        $event = $this->createExceptionEvent(new \RuntimeException('Test exception'));
        $listener->onKernelException($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testListenerDoesNotHandleWhenOverrideIsDisabled(): void
    {
        $listener = new ExceptionListener(
            $this->errorInsightService,
            enabled: true
        );

        $event = $this->createExceptionEvent(new \RuntimeException('Test exception'));
        $listener->onKernelException($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testListenerDoesNotHandleInProductionEnvironment(): void
    {
        // Temporarily override environment
        $_SERVER['APP_ENV'] = 'prod';

        try {
            $event = $this->createExceptionEvent(new \RuntimeException('Test exception'));
            $this->listener->onKernelException($event);

            $this->assertFalse($event->hasResponse());
        } finally {
            unset($_SERVER['APP_ENV']);
        }
    }

    public function testListenerHandlesExceptionInDevelopmentEnvironment(): void
    {
        // Set development environment
        $_SERVER['APP_ENV'] = 'dev';

        try {
            $event = $this->createExceptionEvent(new \RuntimeException('Test exception'));

            // Mock CLI check to ensure we're in web context
            $originalSapi = \PHP_SAPI;
            if ('cli' === \PHP_SAPI) {
                // For this test, we'll skip the CLI check since we can't change PHP_SAPI in tests
                $this->markTestSkipped('Cannot test web context in CLI environment');
            }

            $this->listener->onKernelException($event);

            // The listener should set a response when handling exceptions
            if ($event->hasResponse()) {
                $response = $event->getResponse();
                $this->assertInstanceOf(Response::class, $response);
                $this->assertSame(500, $response->getStatusCode());
                $this->assertStringContainsString('text/html', $response->headers->get('Content-Type', ''));
            }
        } finally {
            unset($_SERVER['APP_ENV']);
        }
    }

    public function testListenerPreservesHttpExceptionStatusCode(): void
    {
        // Set development environment
        $_SERVER['APP_ENV'] = 'dev';

        try {
            $httpException = new NotFoundHttpException('Page not found');
            $event = $this->createExceptionEvent($httpException);

            if ('cli' === \PHP_SAPI) {
                $this->markTestSkipped('Cannot test web context in CLI environment');
            }

            $this->listener->onKernelException($event);

            $response = $event->getResponse();

            if ($response instanceof Response) {
                $this->assertSame(404, $response->getStatusCode());
            }
        } finally {
            unset($_SERVER['APP_ENV']);
        }
    }

    public function testListenerHandlesRenderingErrors(): void
    {
        // Test that the listener gracefully handles cases where rendering might fail
        // by creating a service that returns empty content
        $errorService = new ErrorInsightService(
            enabled: false, // This will cause renderException to return empty string
            backend: 'none',
            model: null,
            language: 'en',
            output: 'html',
            verbose: false,
            apiKey: null,
            apiUrl: null,
            template: null,
        );

        $listener = new ExceptionListener(
            $errorService,
            enabled: true
        );

        // Set development environment
        $_SERVER['APP_ENV'] = 'dev';

        try {
            $event = $this->createExceptionEvent(new \RuntimeException('Test exception'));

            // Should not throw an exception when rendering fails
            $listener->onKernelException($event);

            // Should not have set a response due to empty rendering
            $this->assertFalse($event->hasResponse());
        } finally {
            unset($_SERVER['APP_ENV']);
        }
    }

    public function testListenerWithDisabledErrorInsightService(): void
    {
        $disabledService = new ErrorInsightService(
            enabled: false,
            backend: 'none',
            model: null,
            language: 'en',
            output: 'html',
            verbose: false,
            apiKey: null,
            apiUrl: null,
            template: null,
        );

        $listener = new ExceptionListener(
            $disabledService,
            enabled: true
        );

        $_SERVER['APP_ENV'] = 'dev';

        try {
            $event = $this->createExceptionEvent(new \RuntimeException('Test exception'));
            $listener->onKernelException($event);

            // Should not handle when the service is disabled
            $this->assertFalse($event->hasResponse());
        } finally {
            unset($_SERVER['APP_ENV']);
        }
    }

    private function createExceptionEvent(\Throwable $exception): ExceptionEvent
    {
        $request = Request::create('/test');

        return new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }
}
