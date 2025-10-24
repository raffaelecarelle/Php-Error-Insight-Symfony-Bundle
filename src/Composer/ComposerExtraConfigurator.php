<?php

namespace PhpErrorInsightBundle\Composer;

use Composer\Json\JsonFile;
use Symfony\Flex\Configurator\AbstractConfigurator;
use Symfony\Flex\Update\RecipeUpdate;

class ComposerExtraConfigurator extends AbstractConfigurator
{
    public function configure($recipe, $config, $lock, $options = []): void
    {
        $this->addErrorHandlerToComposer($config);
    }

    public function update(RecipeUpdate $recipeUpdate, array $originalConfig, array $newConfig): void
    {
        // Aggiorna la configurazione se l'handler è cambiato
        $this->addErrorHandlerToComposer($originalConfig);
    }

    public function unconfigure($recipe, $config, $lock)
    {
        $composerFile = new JsonFile($this->options->get('composer-file'));
        $composerData = $composerFile->read();

        if (isset($composerData['extra']['runtime']['error_handler'])) {
            // Verifica che sia il nostro handler prima di rimuoverlo
            if ($composerData['extra']['runtime']['error_handler'] === $config['error_handler']) {
                unset($composerData['extra']['runtime']['error_handler']);

                if (empty($composerData['extra']['runtime'])) {
                    unset($composerData['extra']['runtime']);
                }

                if (empty($composerData['extra'])) {
                    unset($composerData['extra']);
                }

                $composerFile->write($composerData);

                $this->write('Removed error handler from composer.json');
            }
        }
    }

    private function addErrorHandlerToComposer(array $config): void
    {
        $composerFile = new JsonFile($this->options->get('composer-file'));
        $composerData = $composerFile->read();

        if (!isset($composerData['extra'])) {
            $composerData['extra'] = [];
        }

        if (!isset($composerData['extra']['runtime'])) {
            $composerData['extra']['runtime'] = [];
        }

        $currentHandler = $composerData['extra']['runtime']['error_handler'] ?? null;
        $newHandler = $config['error_handler'];

        // Aggiorna solo se l'handler è diverso o non esiste
        if ($currentHandler !== $newHandler) {
            $composerData['extra']['runtime']['error_handler'] = $newHandler;
            $composerFile->write($composerData);

            $message = $currentHandler
                ? sprintf('Updated error handler in composer.json (from %s to %s)', $currentHandler, $newHandler)
                : 'Added error handler to composer.json extra section';

            $this->write($message);
        }
    }
}