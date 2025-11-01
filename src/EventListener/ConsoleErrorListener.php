<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\EventListener;

use PhpErrorInsightBundle\Service\ErrorInsightService;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleErrorListener implements EventSubscriberInterface
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
            $output = $event->getOutput();
            $verbosity = $output->getVerbosity();
            $isVerbose = $verbosity >= OutputInterface::VERBOSITY_VERBOSE; // -v or more

            $content = $this->errorInsightService->renderException($error, $isVerbose);

            if ('' === $content || '0' === $content) {
                return;
            }

            $output->writeln($content);
        } catch (\Throwable) {
            // Never break CLI flow due to renderer issues
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => 'onConsoleError',
        ];
    }
}
