<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('neox_crud');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('uploads_dir')
                    ->defaultValue('public/uploads') // Default value
                    ->info('The directory where files are uploaded')
                ->end()
            ->end()
            ->children()
                ->arrayNode('mercure')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('topic_prefix')
                            ->defaultValue('/crud')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('makers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('templates_namespace')
                            ->defaultNull()
                            ->info('Default Twig namespace used by the Maker when generating templates (can be overridden by CLI option).')
                        ->end()
                        ->scalarNode('base_layout')
                            ->defaultNull()
                            ->info('Default Twig base layout path used by the Maker when generating templates (can be overridden by CLI option). When null, falls back to /admin/_layout.html.twig or namespace-derived value.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('translations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('field_keys')
                            ->prototype('scalar')->end()
                            ->defaultValue(['label', 'placeholder'])
                        ->end()
                        ->arrayNode('patterns')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('live_table')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->enumNode('pagination_position')
                            ->values(['top', 'bottom', 'all'])
                            ->defaultValue('bottom')
                        ->end()
                        ->integerNode('default_per_page')
                            ->min(1)
                            ->defaultValue(25)
                        ->end()
                        ->integerNode('max_per_page')
                            ->min(1)
                            ->defaultValue(100)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
