<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Tests\Unit\Service;

use PhpErrorInsight\Config;
use PhpErrorInsight\ErrorExplainer;
use PhpErrorInsightBundle\Service\ErrorInsightService;
use PHPUnit\Framework\TestCase;

final class ErrorInsightServiceTest extends TestCase
{
    private ErrorInsightService $service;

    protected function setUp(): void
    {
        $this->service = new ErrorInsightService(
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

    protected function tearDown(): void
    {
        // Clean up any registered ErrorExplainer instances
        ErrorExplainer::unregister();
    }

    public function testIsEnabledReturnsTrueWhenEnabled(): void
    {
        $this->assertTrue($this->service->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenDisabled(): void
    {
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

        $this->assertFalse($service->isEnabled());
    }

    public function testGetConfigReturnsConfigWhenEnabled(): void
    {
        $config = $this->service->getConfig();
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testGetConfigReturnsNullWhenDisabled(): void
    {
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

        $this->assertNull($service->getConfig());
    }

    public function testRenderExceptionReturnsEmptyStringWhenDisabled(): void
    {
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

        $exception = new \RuntimeException('Test exception');
        $result = $service->renderException($exception);

        $this->assertSame('', $result);
    }

    public function testRenderExceptionReturnsStringWhenEnabled(): void
    {
        $exception = new \RuntimeException('Test exception');
        $result = $this->service->renderException($exception);

        // Should return some content (either from ErrorExplainer or our fallback)
        $this->assertIsString($result);
    }

    public function testServiceWorksWithDifferentOutputFormats(): void
    {
        $formats = [Config::OUTPUT_AUTO, Config::OUTPUT_HTML, Config::OUTPUT_TEXT, Config::OUTPUT_JSON];

        foreach ($formats as $format) {
            $service = new ErrorInsightService(
                enabled: true,
                backend: 'none',
                model: null,
                language: 'en',
                output: $format,
                verbose: false,
                apiKey: null,
                apiUrl: null,
                template: null,
            );

            $config = $service->getConfig();
            $this->assertNotNull($config);
            $this->assertSame($format, $config->output);

            // Clean up for next iteration
            ErrorExplainer::unregister();
        }
    }
}
