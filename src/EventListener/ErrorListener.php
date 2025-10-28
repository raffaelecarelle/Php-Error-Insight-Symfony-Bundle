<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\EventListener;

use PhpErrorInsightBundle\Service\ErrorInsightService;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Output\OutputInterface;
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
            $output = $event->getOutput();
            $verbosity = $output->getVerbosity();
            $isVerbose = $verbosity >= OutputInterface::VERBOSITY_VERBOSE; // -v or more
            $isFatal = $error instanceof \Error; // treat PHP Errors as fatal/critical

            // Drive core library verbosity from Symfony Console flags (HTTP uses kernel.debug elsewhere)
            $content = $this->errorInsightService->renderException($error, $isVerbose);

            if ('' === $content || '0' === $content) {
                return;
            }

            // In CLI, when verbose=0 and not a fatal error, hide the stack from the text output
            if (!$isVerbose && !$isFatal) {
                $content = $this->stripStackFromText($content);
            }

            $output->writeln($content);
        } catch (\Throwable) {
            // Never break CLI flow due to renderer issues
        }
    }

    /**
     * Remove the "Stack trace" section from text output, keeping other sections.
     * Works with or without ANSI colors and supports EN/IT labels for subsequent sections.
     */
    private function stripStackFromText(string $content): string
    {
        // If looks like HTML/JSON, leave untouched
        $trim = ltrim($content);
        if (str_starts_with($trim, '<') || str_starts_with($trim, '{') || str_starts_with($trim, '[')) {
            return $content;
        }

        $pos = stripos($content, 'Stack trace');
        if (false === $pos) {
            return $content; // nothing to strip
        }

        // Find the next section header approximate boundaries
        $nextMarkers = [
            'Details',        // EN
            'Dettagli',       // IT
            'Suggestions',    // EN
            'Suggerimenti',   // IT
        ];

        $nextPos = null;
        foreach ($nextMarkers as $marker) {
            $p = stripos($content, $marker, $pos + 1);
            if (false !== $p && (null === $nextPos || $p < $nextPos)) {
                $nextPos = $p;
            }
        }

        if (null === $nextPos) {
            // Cut everything from stack header to the end
            return rtrim(substr($content, 0, $pos)) . "\n";
        }

        return rtrim(substr($content, 0, $pos)) . "\n" . ltrim(substr($content, $nextPos));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => 'onConsoleError',
        ];
    }
}
