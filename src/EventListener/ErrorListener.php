<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\EventListener;

use PhpErrorInsightBundle\Service\ErrorInsightService;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ErrorListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly ErrorInsightService $errorInsightService,
        private readonly bool $enabled,
    ) {
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $error = $event->getError();

        try {
            $content = $this->errorInsightService->renderException($error);

            if ('' === $content || '0' === $content) {
                return;
            }

            $output = $event->getOutput();

            $output->writeln($content);
        } catch (\Throwable) {
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => 'onConsoleError',
        ];
    }
}
