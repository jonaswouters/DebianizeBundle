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
                ->scalarNode('command')->defaultValue('dpkg')->end()
                ->scalarNode('options')->defaultValue('-Cva')->end()
                ->scalarNode('root')->defaultValue('%kernel.root_dir%/..')->cannotBeEmpty()->end()
            ->end();

        return $treeBuilder->buildTree();
    }
}

