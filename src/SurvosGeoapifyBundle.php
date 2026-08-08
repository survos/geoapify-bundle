<?php

namespace Survos\GeoapifyBundle;

use Survos\GeoapifyBundle\Service\GeoapifyService;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SurvosGeoapifyBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {

// Lookup domain information
        $serviceId = 'survos_geoapify_service';
        $container->services()->alias(GeoapifyService::class, $serviceId);
        $builder->autowire($serviceId, GeoapifyService::class)
            ->setAutoconfigured(true)
            ->setArgument('$apiKey', $config['api_key'])
            ->setPublic(true);

    }



    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('api_key')->defaultValue('%env(GEOAPIFY_API_KEY)%')->end()
            ->end();
    }
}
