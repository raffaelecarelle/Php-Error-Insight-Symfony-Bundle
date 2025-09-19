<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Tests\Unit\Service;

use ErrorExplainer\Config;
use ErrorExplainer\ErrorExplainer;
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

    public function testGetErrorExplainerReturnsNullWhenDisabled(): void
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

        $this->assertNull($service->getErrorExplainer());
    }

    public function testGetErrorExplainerReturnsInstanceWhenEnabled(): void
    {
        $explainer = $this->service->getErrorExplainer();
        $this->assertInstanceOf(ErrorExplainer::class, $explainer);
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

    public function testHandleExceptionReturnsArrayWithExceptionData(): void
    {
        $exception = new \RuntimeException('Test exception', 123);
        $result = $this->service->handleException($exception);

        $this->assertIsArray($result);
        $this->assertSame('Test exception', $result['message']);
        $this->assertSame(__FILE__, $result['file']);
        $this->assertIsInt($result['line']);
        $this->assertIsArray($result['trace']);
        $this->assertSame(\RuntimeException::class, $result['class']);
        $this->assertNull($result['severity']);
    }

    public function testHandleExceptionReturnsNullWhenDisabled(): void
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
        $result = $service->handleException($exception);

        $this->assertNull($result);
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

    public function testServiceWorksWithDifferentBackends(): void
    {
        $backends = ['none', 'local', 'api'];

        foreach ($backends as $backend) {
            $service = new ErrorInsightService(
                enabled: true,
                backend: $backend,
                model: 'test-model',
                language: 'en',
                output: 'html',
                verbose: false,
                apiKey: 'test-key',
                apiUrl: 'http://localhost:11434',
                template: null,
            );

            $this->assertTrue($service->isEnabled());
            $this->assertInstanceOf(ErrorExplainer::class, $service->getErrorExplainer());

            // Clean up for next iteration
            ErrorExplainer::unregister();
        }
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
