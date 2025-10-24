<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Tests\Unit\DependencyInjection;

use PhpErrorInsightBundle\DependencyInjection\PhpErrorInsightExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PhpErrorInsightExtensionTest extends TestCase
{
    public function testGetAlias(): void
    {
        $extension = new PhpErrorInsightExtension();
        $this->assertSame('php_error_insight', $extension->getAlias());
    }

    public function testLoadSetsParametersFromConfig(): void
    {
        $container = new ContainerBuilder();
        $extension = new PhpErrorInsightExtension();

        $configs = [[
            'enabled' => true,
            'backend' => 'local',
            'model' => 'llama3:instruct',
            'language' => 'it',
            'output' => 'html',
            'verbose' => true,
            'api_key' => 'abc123',
            'api_url' => 'http://localhost:11434',
            'template' => __FILE__,
            'editor_url' => 'phpstorm://open?file=%file%&line=%line',
            'project_root' => '/app',
            'host_root' => '/host/app',
        ]];

        $extension->load($configs, $container);

        $this->assertTrue($container->hasParameter('php_error_insight.enabled'));
        $this->assertTrue($container->getParameter('php_error_insight.enabled'));
        $this->assertSame('local', $container->getParameter('php_error_insight.backend'));
        $this->assertSame('llama3:instruct', $container->getParameter('php_error_insight.model'));
        $this->assertSame('it', $container->getParameter('php_error_insight.language'));
        $this->assertSame('html', $container->getParameter('php_error_insight.output'));
        $this->assertTrue($container->getParameter('php_error_insight.verbose'));
        $this->assertSame('abc123', $container->getParameter('php_error_insight.api_key'));
        $this->assertSame('http://localhost:11434', $container->getParameter('php_error_insight.api_url'));
        $this->assertSame(__FILE__, $container->getParameter('php_error_insight.template'));
        $this->assertSame('phpstorm://open?file=%file%&line=%line', $container->getParameter('php_error_insight.editor_url'));
        $this->assertSame('/app', $container->getParameter('php_error_insight.project_root'));
        $this->assertSame('/host/app', $container->getParameter('php_error_insight.host_root'));

        // services.php should have been loaded; basic alias should exist
        $this->assertTrue($container->has('php_error_insight.service'));
    }
}
