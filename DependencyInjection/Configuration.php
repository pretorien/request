<?php

namespace WTeam\RequestBundle\DependencyInjection;

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
            ->arrayNode('myip')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('uri')
                        // ->defaultValue("https://api6.ipify.org?format=json")
                        ->defaultValue("https://api.myip.com")
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
