<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\EventListener;

use PhpErrorInsightBundle\Service\ErrorInsightService;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleErrorListener implements EventSubscriberInterface
{
    // Variable needed in terminate to determine if I'm coming from my console error
    private bool $isError = false;

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

            // Force the error code to 0 so that Symfony's error handling doesn't interfere
            // Because after the error event and terminate event are completed, if the error code is != 0
            // Symfony re-throws the exception and renders Symfony's error output. This is not desired
            /* @see Application::run() line 156 */
            $event->setExitCode(0);
            $this->isError = true;
        } catch (\Throwable) {
            // Never break CLI flow due to renderer issues
        }
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if (!$this->enabled || !$this->isError) {
            return;
        }

        // Then in the terminate event I set the error code back to 1 because Symfony has already set the exception to null here (having found the error code set to 0 above)
        // and therefore Symfony will not print anything
        $event->setExitCode(1);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => [['onConsoleError', 255]],
            ConsoleEvents::TERMINATE => [['onConsoleTerminate', 255]],
        ];
    }
}
