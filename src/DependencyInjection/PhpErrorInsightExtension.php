<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class PhpErrorInsightExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Set configuration parameters
        $container->setParameter('php_error_insight.enabled', $config['enabled']);
        $container->setParameter('php_error_insight.backend', $config['backend']);
        $container->setParameter('php_error_insight.model', $config['model']);
        $container->setParameter('php_error_insight.language', $config['language']);
        $container->setParameter('php_error_insight.output', $config['output']);
        $container->setParameter('php_error_insight.verbose', $config['verbose']);
        $container->setParameter('php_error_insight.api_key', $config['api_key']);
        $container->setParameter('php_error_insight.api_url', $config['api_url']);
        $container->setParameter('php_error_insight.template', $config['template']);
        $container->setParameter('php_error_insight.editor_url', str_replace('%', '%%', $config['editor_url']));
        $container->setParameter('php_error_insight.project_root', $config['project_root']);
        $container->setParameter('php_error_insight.host_root', $config['host_root']);

        // Load service definitions
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }

    public function getAlias(): string
    {
        return 'php_error_insight';
    }
}
