<?php

namespace TON\Bundle\DebianizeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Jonas Wouters <hello@jonaswouters.be>
 */
class Configuration
{
    /**
     * Generates the configuration tree.
     *
     * @return \Symfony\Component\DependencyInjection\Configuration\NodeInterface
     */
    public function getConfigTree()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ton_debianize');

        $rootNode
            ->children()
                ->arrayNode('commands')->children()
                    ->scalarNode('tar')->defaultValue('tar')->end()
                    ->scalarNode('ar')->defaultValue('ar')->end()
                ->end()->end()
                ->scalarNode('install_location')->defaultValue('/var/www/symfony2')->end()
                ->arrayNode('additional_resources')->addDefaultsIfNotSet()->defaultValue(array('app/config/vhost' => '/etc/apache2/sites-available/mysite.com'))->end()
                ->arrayNode('excludes')->defaultValue(array('data.tar.gz','control.tar.gz'))->end()
                ->scalarNode('root')->defaultValue('%kernel.root_dir%/..')->cannotBeEmpty()->end()
                ->scalarNode('kablah')->defaultValue('hoera')->cannotBeEmpty()->end()
                ->arrayNode('package')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')->defaultValue('symfony2')->cannotBeEmpty()->end()
                        ->scalarNode('description')->defaultValue('symfony2 application')->cannotBeEmpty()->end()
                        ->scalarNode('maintainer')->cannotBeEmpty()->end()
                        ->arrayNode('dependencies')->ignoreExtraKeys()->cannotBeEmpty()->end()
                    ->end()
                ->end();

        return $treeBuilder->buildTree();
    }
}

