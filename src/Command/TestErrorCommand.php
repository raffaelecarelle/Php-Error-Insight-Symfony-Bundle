<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Command;

use PhpErrorInsightBundle\Service\ErrorInsightService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'php-error-insight:test',
    description: 'Test PHP Error Insight integration by triggering various types of errors'
)]
final class TestErrorCommand extends Command
{
    public function __construct(
        private readonly ErrorInsightService $errorInsightService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'Type of error to trigger: exception, error, warning, notice',
                'exception'
            )
            ->addOption(
                'message',
                'm',
                InputOption::VALUE_REQUIRED,
                'Custom error message',
                'This is a test error from PHP Error Insight Bundle'
            )
            ->setHelp(
                <<<'HELP'
This command demonstrates PHP Error Insight integration with Symfony Console.
It allows you to trigger different types of errors to see how they are handled:

Examples:
  php bin/console php-error-insight:test --type=exception
  php bin/console php-error-insight:test --type=error --message="Custom error message"
  php bin/console php-error-insight:test --type=warning
  php bin/console php-error-insight:test --type=notice
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = $input->getOption('type');
        $message = $input->getOption('message');

        if (!$this->errorInsightService->isEnabled()) {
            $io->warning('PHP Error Insight is disabled. Enable it in your configuration to see AI-powered error explanations.');
        } else {
            $io->info('PHP Error Insight is enabled. The following error will be processed by AI if configured.');
        }

        $io->section(\sprintf('Triggering %s error', $type));

        try {
            match ($type) {
                'exception' => $this->triggerException($message),
                'error' => $this->triggerError($message),
                'warning' => $this->triggerWarning($message),
                'notice' => $this->triggerNotice($message),
                default => throw new \InvalidArgumentException(\sprintf('Unknown error type: %s', $type)),
            };
        } catch (\Throwable $throwable) {
            // This is expected for the 'exception' type
            if ('exception' === $type) {
                $io->error(\sprintf('Exception caught: %s', $throwable->getMessage()));
                $io->writeln(\sprintf('File: %s', $throwable->getFile()));
                $io->writeln(\sprintf('Line: %d', $throwable->getLine()));

                if ($this->errorInsightService->isEnabled()) {
                    $explanation = $this->errorInsightService->handleException($throwable);
                    if (null !== $explanation) {
                        $io->section('Error Analysis');
                        $io->writeln('PHP Error Insight would provide AI-powered analysis here.');
                    }
                }

                return Command::SUCCESS;
            }

            // Re-throw unexpected exceptions
            throw $throwable;
        }

        $io->success('Command completed successfully.');

        return Command::SUCCESS;
    }

    private function triggerException(string $message): never
    {
        throw new \RuntimeException($message);
    }

    private function triggerError(string $message): void
    {
        trigger_error($message, \E_USER_ERROR);
    }

    private function triggerWarning(string $message): void
    {
        trigger_error($message, \E_USER_WARNING);
    }

    private function triggerNotice(string $message): void
    {
        trigger_error($message, \E_USER_NOTICE);
    }
}
