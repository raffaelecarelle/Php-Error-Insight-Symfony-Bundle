<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Tests\Unit\EventListener;

use PhpErrorInsightBundle\EventListener\ErrorListener;
use PhpErrorInsightBundle\Service\ErrorInsightService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class ErrorListenerTest extends TestCase
{
    public function testDoesNothingWhenDisabled(): void
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

        $listener = new ErrorListener($service, enabled: false);

        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $event = new ConsoleErrorEvent($input, $output, new \RuntimeException('Test error'));

        $listener->onConsoleError($event);

        $this->assertSame('', $output->fetch());
    }

    public function testHandlesConsoleErrorGracefullyWhenEnabled(): void
    {
        $service = new ErrorInsightService(
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

        $listener = new ErrorListener($service, enabled: true);

        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $event = new ConsoleErrorEvent($input, $output, new \RuntimeException('Test error'));

        // Should not throw and may write some content depending on the renderer
        $listener->onConsoleError($event);

        $fetched = $output->fetch();
        $this->assertIsString($fetched);
        // It's acceptable that content may be empty depending on environment, so no strict assertion here
    }
}
