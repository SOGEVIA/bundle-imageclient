<?php

namespace Ugosansh\Bundle\Image\ClientBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class UgosanshImageClientExtension extends Extension
{
    /**
     * @var string
     */
    const CLIENT_API_SERVICE_NAME = 'ugosansh_image_client.client_api';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);
        $loader        = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.yml');

        if (array_key_exists('entity_class', $config)) {
            $container->setParameter('ugosansh_image_client.entity.class', $config['entity_class']);
        }

        if (array_key_exists('default_image', $config)) {
            $container->setParameter('ugosansh_image_client.image_default', $config['default_image']);
        }

        $this->loadClient($container, $config['api']);
    }

    /**
     * Load a client configuration as a service in the container
     *
     * @see https://github.com/M6Web/WsClientBundle
     *
     * @param ContainerInterface $container The container
     * @param array              $config    Base config of the client
     *
     * @return void
     */
    protected function loadClient(ContainerInterface $container, array $config)
    {
        $definition = new Definition('M6Web\Bundle\WSClientBundle\Adapter\WSClientAdapterInterface');

        $definition->setFactoryService('m6_ws_client.factory');
        $definition->setFactoryMethod('getClient');
        $definition->setScope(ContainerInterface::SCOPE_CONTAINER);

        $definition->addArgument(new Reference('service_container'));
        $definition->addArgument(array_key_exists('base_url', $config) ? $config['base_url'] : '');
        $definition->addArgument(array_key_exists('config', $config) ? $config['config'] : array());
        $definition->addArgument(array_key_exists('adapter_class', $config) ? $config['adapter_class'] : '');

        $definition->addMethodCall('setEventDispatcher', array(new Reference('event_dispatcher')));
        $definition->addMethodCall('setStopWatch', array(new Reference('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE)));

        if (array_key_exists('cache', $config)) {
            // Service, Adapter & storage
            $cacheService = array_key_exists('service', $config['cache']) ? new Reference($config['cache']['service']) : null;
            $adapterClass = array_key_exists('adapter', $config['cache']) ? $config['cache']['adapter'] : '';
            $storageClass = array_key_exists('storage', $config['cache']) ? $config['cache']['storage'] : '';

            // Subscriber
            $subscriberClass = array_key_exists('subscriber', $config['cache']) ? $config['cache']['subscriber'] : '';

            // Force ttl : ForcedCacheRequest can cache class
            if ($config['cache']['force_request_ttl']) {
                $definition->addMethodCall('setRequestTtl', array($config['cache']['ttl']));
                $canCacheClass = ['\M6Web\Bundle\WSClientBundle\Cache\Guzzle\ForcedCacheRequest', 'canCacheRequest'];
            } else {
                // Config or default value
                $canCacheClass = array_key_exists('can_cache', $config['cache']) ? $config['cache']['can_cache'] : null;
            }

            // Add call to the client setCache
            $definition->addMethodCall('setCache', array(
                $config['cache']['ttl'],
                $config['cache']['force_request_ttl'],
                [
                    'cache_service' => $cacheService,
                    'adapter_class' => $adapterClass,
                    'storage_class' => $storageClass,
                    'subscriber_class' => $subscriberClass,
                    'can_cache_callable' => $canCacheClass
                ],
                array_key_exists('options', $config['cache']) ? $config['cache']['options'] : []
            ));
        }

        $container->setDefinition(self::CLIENT_API_SERVICE_NAME, $definition);
    }

}
