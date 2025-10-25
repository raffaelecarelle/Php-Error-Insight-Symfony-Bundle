<?php

declare(strict_types=1);

use PhpErrorInsight\Internal\Renderer;
use PhpErrorInsightBundle\EventListener\ExceptionListener;
use PhpErrorInsightBundle\Service\ErrorInsightService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(ErrorInsightService::class)
        ->args([
            param('php_error_insight.enabled'),
            param('php_error_insight.backend'),
            param('php_error_insight.model'),
            param('php_error_insight.language'),
            param('php_error_insight.output'),
            param('php_error_insight.verbose'),
            param('php_error_insight.api_key'),
            param('php_error_insight.api_url'),
            param('php_error_insight.template'),
            param('php_error_insight.editor_url'),
            param('php_error_insight.project_root'),
            param('php_error_insight.host_root'),
        ])
        ->public();

    $services->set(ExceptionListener::class)
        ->args([
            service(ErrorInsightService::class),
            param('php_error_insight.enabled'),
            param('kernel.error_controller'),
            service('logger')->nullOnInvalid(),
            param('kernel.debug'),
        ])
        ->tag('kernel.event_listener', [
            'event' => 'kernel.exception',
            'priority' => 255, // High priority to intercept before other listeners
        ]);

    $services->set(Renderer::class);

    $services->alias('php_error_insight.service', ErrorInsightService::class);
    $services->alias('php_error_insight.exception_listener', ExceptionListener::class);

    $services->set(PhpErrorInsightBundle\EventListener\ErrorListener::class)
        ->args([
            service(ErrorInsightService::class),
            param('php_error_insight.enabled'),
        ])
        ->tag('kernel.event_subscriber');
};
