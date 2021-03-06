<?php
/*
 * This file is part of the DebianizeBundle project.
 *
 * (c) 21net.com <info@21net.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TON\Bundle\DebianizeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class TONDebianizeExtension extends Extension
{
    /**
     * Xml config files to load
     * @var array
     */
    protected $resources = array(
        'services' => 'services.xml',
    );
    
    /**
     * Loads the services based on your application configuration.
     *
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->process($configuration->getConfigTree(), $configs);

        $this->loadDefaults($container);

        foreach ($config as $key => $value) {
            $container->setParameter($this->getAlias().'.'.$key, $value);
        }
    }

    public function getAlias()
    {
        return 'ton_debianize';
    }

    /**
     * Get File Loader
     *
     * @param ContainerBuilder $container
     */
    public function getFileLoader($container)
    {
        return new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
    }

    protected function loadDefaults($container)
    {
        $loader = $this->getFileLoader($container);

        foreach ($this->resources as $resource) {
            $loader->load($resource);
        }
    }
}

