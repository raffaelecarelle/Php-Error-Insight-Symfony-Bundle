<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\DependencyInjection;

use ErrorExplainer\Config;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('php_error_insight');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                    ->info('Enable or disable the PHP Error Insight bundle')
                ->end()
                ->scalarNode('backend')
                    ->defaultValue('none')
                    ->validate()
                        ->ifNotInArray(['none', 'local', 'api', 'openai', 'anthropic', 'google', 'gemini'])
                        ->thenInvalid('Invalid backend "%s"')
                    ->end()
                    ->info('AI backend to use: none, local, api, openai, anthropic, google, gemini')
                ->end()
                ->scalarNode('model')
                    ->defaultNull()
                    ->info('AI model name (e.g., llama3:instruct, gpt-4o-mini, claude-3-5-sonnet-20240620)')
                ->end()
                ->scalarNode('language')
                    ->defaultValue('en')
                    ->info('Language for AI prompts (en, it, etc.)')
                ->end()
                ->scalarNode('output')
                    ->defaultValue(Config::OUTPUT_AUTO)
                    ->validate()
                        ->ifNotInArray([Config::OUTPUT_AUTO, Config::OUTPUT_HTML, Config::OUTPUT_TEXT, Config::OUTPUT_JSON])
                        ->thenInvalid('Invalid output format "%s"')
                    ->end()
                    ->info('Output format: auto, html, text, json')
                ->end()
                ->booleanNode('verbose')
                    ->defaultFalse()
                    ->info('Enable verbose output')
                ->end()
                ->scalarNode('api_key')
                    ->defaultNull()
                    ->info('API key for external AI services')
                ->end()
                ->scalarNode('api_url')
                    ->defaultNull()
                    ->info('Custom API URL for AI services')
                ->end()
                ->scalarNode('template')
                    ->defaultNull()
                    ->info('Path to custom HTML template')
                ->end()
                ->scalarNode('editor_url')
                    ->defaultNull()
                    ->info('Editor URL template (e.g., phpstorm://open?file=%%file&line=%%line)')
                ->end()
                ->scalarNode('host_root')
                    ->defaultNull()
                    ->info('Absolute host project root for mapping container paths (e.g., when running in Docker)')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
