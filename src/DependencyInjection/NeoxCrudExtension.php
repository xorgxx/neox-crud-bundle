<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\DependencyInjection;

use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Mercure\HubInterface;

class NeoxCrudExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        // Support both new root-level `config/` and legacy `Resources/config` for BC
        $configDirs = [
            __DIR__ . '/../../config',            // new location (preferred)
//            __DIR__ . '/../../Resources/config',  // legacy location (BC)
        ];

        $loader = new YamlFileLoader($container, new FileLocator($configDirs));

        $loader->load('services.yaml');

        // Load maker services only if enabled AND MakerBundle is present (dev-only in many apps)
        if (!empty($config['makers']['enabled']) && \class_exists(AbstractMaker::class)) {
            $loader->load('maker.yaml');
        }

        if (!empty($config['mercure']['enabled']) && interface_exists(HubInterface::class)) {
            $loader->load('mercure.yaml');
        }

        $container->setParameter('neox_crud.mercure.topic_prefix', $config['mercure']['topic_prefix']);
        $container->setParameter('neox_crud.translations.field_keys', $config['translations']['field_keys']);
        $container->setParameter('neox_crud.translations.patterns', $config['translations']['patterns']);
        $container->setParameter('neox_crud.uploads_dir', $config['uploads_dir']);
        $container->setParameter('neox_crud.makers.templates_namespace', $config['makers']['templates_namespace'] ?? 'NeoxCrud');
        $container->setParameter('neox_crud.makers.base_layout', $config['makers']['base_layout'] ?? null);
        $container->setParameter('neox_crud.makers', $config['makers'] ?? null);

    }

    /**
     * Configure Twig automatically to register the "NeoxCrud" namespace.
     */
    public function prepend(ContainerBuilder $container): void
    {
        // Check if TwigBundle is registered in the application
        if (!$container->hasExtension('twig')) {
            return;
        }

        // Path to the templates folder at the root of the bundle
        // __DIR__ is in src/DependencyInjection, so go up two levels to reach the root
        $templatesPath = realpath(__DIR__ . '/../../templates');

        if ($templatesPath) {
            $container->prependExtensionConfig('twig', [
                'paths' => [
                    // Register the path to your templates under the "NeoxCrud" namespace
                    $templatesPath => 'NeoxCrud'
                ]
            ]);
        }
    }

    public function getAlias(): string
    {
        return 'neox_crud';
    }
}
