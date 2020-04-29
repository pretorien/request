<?php

namespace Pretorien\RequestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('request');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
        ->children()
            ->arrayNode('proxy')
            ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('nordvpn')
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('username')->end()
                            ->scalarNode('password')->end()
                            ->scalarNode('api')
                                ->defaultValue("https://api.nordvpn.com/v1/servers/recommendations?filters\[servers_groups\]=5&filters\[servers_technologies\]=9&filters\[country_id\]=74")
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('class')->isRequired()
            ->children()
                ->arrayNode('model')->isRequired()
                    ->children()
                        ->scalarNode('proxy')->isRequired()->end()
                    ->end()
                ->end()
            ->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
