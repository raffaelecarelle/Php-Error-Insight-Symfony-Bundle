<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Tests\Unit\EventListener;

use PhpErrorInsightBundle\EventListener\ExceptionListener;
use PhpErrorInsightBundle\Service\ErrorInsightService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\LazyResponseException;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class ExceptionListenerTest extends TestCase
{
    private function createEnabledService(): ErrorInsightService
    {
        return new ErrorInsightService(
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
    }

    public function testIgnoresSubRequest(): void
    {
        $listener = new ExceptionListener($this->createEnabledService(), enabled: true);
        $kernel = $this->createMock(KernelInterface::class);
        $request = Request::create('/test');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, new \RuntimeException('x'));

        $listener->onKernelException($event);

        self::assertFalse($event->hasResponse());
    }

    public function testDefersWhenAuthenticationExceptionInChain(): void
    {
        $listener = new ExceptionListener($this->createEnabledService(), enabled: true);
        $kernel = $this->createMock(KernelInterface::class);
        $event = new ExceptionEvent(
            $kernel,
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('wrap', 0, new AuthenticationException('auth'))
        );

        $listener->onKernelException($event);

        self::assertFalse($event->hasResponse());
    }

    public function testDefersWhenAccessDeniedExceptionInChain(): void
    {
        $listener = new ExceptionListener($this->createEnabledService(), enabled: true);
        $kernel = $this->createMock(KernelInterface::class);
        $event = new ExceptionEvent(
            $kernel,
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('wrap', 0, new AccessDeniedException('denied'))
        );

        $listener->onKernelException($event);

        self::assertFalse($event->hasResponse());
    }

    public function testDefersWhenLogoutOrLazyResponseInChain(): void
    {
        $listener = new ExceptionListener($this->createEnabledService(), enabled: true);
        $kernel = $this->createMock(KernelInterface::class);

        $event1 = new ExceptionEvent(
            $kernel,
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('wrap', 0, new LogoutException('bye'))
        );
        $listener->onKernelException($event1);
        self::assertFalse($event1->hasResponse());

        $event2 = new ExceptionEvent(
            $kernel,
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('wrap', 0, new LazyResponseException(new Response('lazy')))
        );
        $listener->onKernelException($event2);
        self::assertFalse($event2->hasResponse());
    }

    public function testDefersWhenSecurityRequestAttributesPresent(): void
    {
        $listener = new ExceptionListener($this->createEnabledService(), enabled: true);
        $kernel = $this->createMock(KernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, 'x');
        $request->attributes->set(SecurityRequestAttributes::ACCESS_DENIED_ERROR, 'y');

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('x')
        );

        $listener->onKernelException($event);

        self::assertFalse($event->hasResponse());
    }

    public function testNonHttpExceptionSetsRenderedContentOnSubResponse(): void
    {
        $service = $this->createEnabledService();
        $listener = new ExceptionListener($service, enabled: true);

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('handle')->willReturn(new Response('original', 200));

        $event = new ExceptionEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, new \RuntimeException('x'));

        $listener->onKernelException($event);

        // Depending on renderer, content should be non-empty string
        self::assertTrue($event->hasResponse());
        $response = $event->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertIsString($response->getContent());
        self::assertNotSame('', $response->getContent());
    }

    public function testEmptyRenderContentDoesNotSetResponse(): void
    {
        // Disabled service returns empty string
        $service = new ErrorInsightService(
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

        $listener = new ExceptionListener($service, enabled: true);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new ExceptionEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, new \RuntimeException('x'));

        $listener->onKernelException($event);

        self::assertFalse($event->hasResponse());
    }
}
