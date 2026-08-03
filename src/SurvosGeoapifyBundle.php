<?php

namespace Survos\GeoapifyBundle;

use Survos\GeoapifyBundle\Service\GeoapifyService;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SurvosGeoapifyBundle extends AbstractBundle
{
    private const CLIENT_ID = 'survos_geoapify.client';

    /**
     * Scoped client defined in the FRAMEWORK (framework.yaml's http_client.scoped_clients),
     * with retry_failed on -- same pattern as SurvosLocBundle/SurvosTranslatorBundle -- rather
     * than GeoapifyService autowiring the app's default, unscoped http_client with no retry.
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::prependExtension($container, $builder);

        $builder->prependExtensionConfig('framework', [
            'http_client' => [
                'scoped_clients' => [
                    self::CLIENT_ID => [
                        'base_uri' => GeoapifyService::BASE_URL,
                        'retry_failed' => [
                            'enabled' => true,
                            'http_codes' => [429, 500, 502, 503, 504],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {

// Lookup domain information
        $serviceId = 'survos_geoapify_service';
        $container->services()->alias(GeoapifyService::class, $serviceId);
        $builder->autowire($serviceId, GeoapifyService::class)
            ->setAutoconfigured(true)
            ->setArgument('$apiKey', $config['api_key'])
            ->setArgument('$httpClient', new Reference(self::CLIENT_ID))
//            ->setArgument('$cache', new Reference('cache.app', ContainerInterface::NULL_ON_INVALID_REFERENCE))
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
