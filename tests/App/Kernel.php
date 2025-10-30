<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Tests\App;

use PhpErrorInsightBundle\PhpErrorInsightBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use PhpErrorInsightBundle\Tests\App\Controller\SiteController;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        $bundles = [
            new FrameworkBundle(),
            new PhpErrorInsightBundle(),
        ];

        if (class_exists(SecurityBundle::class)) {
            $bundles[] = new SecurityBundle();
        }

        return $bundles;
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // Minimal Framework configuration
        $container->extension('framework', [
            'secret' => 'S0ME_SECRET',
            'test' => true,
            'router' => ['utf8' => true],
            'http_method_override' => false,
        ]);

        // Enable our bundle with minimal config
        $container->extension('php_error_insight', [
            'enabled' => true,
            'backend' => 'none',
            'language' => 'en',
            'output' => 'html',
        ]);

        // Configure Security if available
        if (class_exists(SecurityBundle::class)) {
            $container->extension('security', [
                'password_hashers' => [
                    'Symfony\\Component\\Security\\Core\\User\\PasswordAuthenticatedUserInterface' => 'plaintext',
                ],
                'providers' => [
                    'in_memory' => [
                        'memory' => [
                            'users' => [
                                'user' => ['password' => 'password', 'roles' => ['ROLE_USER']],
                            ],
                        ],
                    ],
                ],
                'firewalls' => [
                    'main' => [
                        'provider' => 'in_memory',
                        'lazy' => true,
                        'form_login' => [
                            'login_path' => '/login',
                            'check_path' => '/login_check',
                            'enable_csrf' => false,
                        ],
                        'logout' => ['path' => '/logout'],
                    ],
                ],
                'access_control' => [
                    ['path' => '^/admin', 'roles' => 'ROLE_USER'],
                ],
            ]);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('login', '/login')
            ->controller([SiteController::class, 'login']);

        $routes->add('admin', '/admin')
            ->controller([SiteController::class, 'admin']);

        $routes->add('boom', '/boom')
            ->controller([SiteController::class, 'boom']);
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__, 2);
    }
}
