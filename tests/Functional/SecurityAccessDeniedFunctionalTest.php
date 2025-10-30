<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Tests\Functional;

use PhpErrorInsightBundle\Tests\App\Kernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityAccessDeniedFunctionalTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testAccessDeniedRedirectsToLogin(): void
    {
        $this->client->request('GET', '/admin');

        // Expect a redirect to /login when not authenticated
        $response = $this->client->getResponse();
        self::assertTrue($response->isRedirection(), 'Expected a redirect response');
        self::assertSame('http://localhost/login', $response->headers->get('Location'));
    }

    public function testGenericExceptionHandledByOurListener(): void
    {
        $this->client->request('GET', '/boom');

        $response = $this->client->getResponse();
        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
        self::assertNotSame('', $response->getContent());
    }
}
