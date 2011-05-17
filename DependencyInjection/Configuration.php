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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Jonas Wouters <jonas@21net.com>
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
            ->arrayNode('commands')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('tar')->defaultValue('tar')->end()
                    ->scalarNode('ar')->defaultValue('ar')->end()
                ->end()
            ->end()
            ->scalarNode('install_location')->defaultValue('/var/www/symfony2')->end()
            ->arrayNode('additional_resources')
                ->prototype('array')->children()
                    ->scalarNode('source')->end()
                    ->scalarNode('destination')->end()
                ->end()
            ->end()->end()
            ->arrayNode('additional_control_files')
                ->prototype('array')->children()
                    ->scalarNode('source')->end()
                    ->scalarNode('destination')->end()
                ->end()
            ->end()->end()
            ->arrayNode('excludes')->addDefaultsIfNotSet()->ignoreExtraKeys()->defaultValue(array('app/cache/*'))
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('package')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('name')->defaultValue('symfony2')->cannotBeEmpty()->end()
                    ->scalarNode('description')->defaultValue('symfony2 application')->cannotBeEmpty()->end()
                    ->scalarNode('maintainer')->cannotBeEmpty()->end()
                    ->arrayNode('dependencies')->cannotBeEmpty()->addDefaultsIfNotSet()->ignoreExtraKeys()->defaultValue(array('php5 (>= 5.3)'))
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('deploy')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('username')->defaultValue('root')->cannotBeEmpty()->end()
                    ->scalarNode('password')->cannotBeEmpty()->end()
                    ->scalarNode('host')->cannotBeEmpty()->end()
                    ->arrayNode('commands')->cannotBeEmpty()->ignoreExtraKeys()
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder->buildTree();
    }
}

