<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\SecurityFirewallCompilerPass;
use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener;
use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallExceptionListener;
use Oro\Bundle\SecurityBundle\Http\Firewall\ExceptionListener;
use Symfony\Bundle\SecurityBundle\Security\FirewallContext;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFirewallCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var SecurityFirewallCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new SecurityFirewallCompilerPass();
    }

    public function testProcessOnEmptySecurityConfig()
    {
        $this->container->prependExtensionConfig('security', []);

        $this->compiler->process($this->container);
    }

    public function testProcessOnEmptySecurityFirewallsConfig()
    {
        $this->container->prependExtensionConfig('security', ['firewalls' => []]);

        $this->compiler->process($this->container);
    }

    public function testProcessOnNonStatelessFirewall()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => false, 'context' => 'main']]]
        );

        $this->compiler->process($this->container);
    }

    public function testProcessOnStatelessButWithoutContextFirewall()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => true]]]
        );

        $this->compiler->process($this->container);
    }

    public function testProcessOnStatelessButWithoutMapContext()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => true, 'context' => 'main']]]
        );

        $this->compiler->process($this->container);
    }

    public function testProcess()
    {
        $this->container->prependExtensionConfig(
            'security',
            ['firewalls' => ['testFirewall' => ['stateless' => true, 'context' => 'main']]]
        );
        $exceptionListener = new Reference('exceptionListener');
        $exceptionListenerDefinition = new Definition(ExceptionListener::class, []);
        $this->container->setDefinition(
            'exceptionListener',
            $exceptionListenerDefinition
        );
        $contextFirewallContext = new Definition(
            FirewallContext::class,
            [
                new IteratorArgument([new Reference('security.access_listener')]),
                $exceptionListener
            ]
        );

        $this->container->setDefinition(
            'security.firewall.map.context.testFirewall',
            $contextFirewallContext
        );

        $this->compiler->process($this->container);

        $contextListener = $this->container->getDefinition('oro_security.context_listener.main');
        self::assertEquals('security.context_listener', $contextListener->getParent());
        self::assertEquals('main', $contextListener->getArgument(2));
        $contextFirewallListener = $this->container->getDefinition('oro_security.context_listener.main.testFirewall');
        self::assertEquals(SecurityFirewallContextListener::class, $contextFirewallListener->getClass());
        self::assertEquals(
            [
                new Reference('oro_security.context_listener.main'),
                new Reference('security.token_storage'),
                new Reference('session', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
            ],
            $contextFirewallListener->getArguments()
        );
        self::assertTrue($contextFirewallListener->hasMethodCall('setCsrfRequestManager'));
        self::assertEquals(SecurityFirewallExceptionListener::class, $exceptionListenerDefinition->getClass());

        $listeners = $contextFirewallContext->getArgument(0);
        self::assertCount(2, $listeners);
        // Context serializer listener should does before the access listener
        self::assertEquals('oro_security.context_listener.main.testFirewall', (string)$listeners[0]);
        self::assertEquals('security.access_listener', (string)$listeners[1]);
    }
}
