<?php

declare(strict_types=1);

namespace Neox\NeoxCrudBundle\Maker;

use Neox\NeoxCrudBundle\Maker\Contract\DoctrineEntityHelperInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * make:neox:crud-handler
 *
 * Génère un handler CRUD basé sur AbstractDoctrineCrudHandler.
 */
class MakeCrudHandler extends AbstractMaker
{
    public function __construct(
        private DoctrineEntityHelperInterface $doctrineHelper,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:neox:crud-handler';
    }

    public static function getCommandDescription(): string
    {
        return 'Crée un handler CRUD (factory-based) pour une entité donnée';
    }

    /**
     * Declare optional dependencies required by this maker.
     *
     * For this maker, no additional packages are strictly required beyond the
     * MakerBundle itself, so we keep this empty to satisfy the interface.
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // No extra dependencies needed.
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setHelp('Ce maker génère une classe de handler CRUD (namespace App\\Crud\\Handler).')
            ->addArgument('resource', InputArgument::REQUIRED, 'Nom de la ressource (ex: product)')
            ->addArgument('entity-class', InputArgument::REQUIRED, 'Classe de l’entité (FQCN ou raccourci, ex: App\\Entity\\Product ou Product)')
            ->addArgument('form-type-class', InputArgument::REQUIRED, 'Classe du FormType (FQCN, ex: App\\Form\\ProductType)')
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        // Simple pour ce skeleton, tout passe par les arguments
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $resource      = (string) $input->getArgument('resource');
        $entityClass   = (string) $input->getArgument('entity-class');
        $formTypeClass = (string) $input->getArgument('form-type-class');

        $resourceSlug   = strtolower($resource);
        $resourceStudly = ucfirst($resourceSlug);

        if (!str_contains($entityClass, '\\')) {
            $entityClass = $this->doctrineHelper->getEntityNamespace() . '\\' . $entityClass;
        }

        // Gather Doctrine fields to suggest them in the generated config.yaml
        $metadata   = $this->doctrineHelper->getMetadata($entityClass);
        $fieldNames = array_filter(
            $metadata->getFieldNames(),
            static fn (string $field): bool => $field !== 'id'
        );

        // Fixed path: Crud/Handle/<ResourceStudly>/<ResourceStudly>CrudHandler.php
        $handlerNamespacePrefix  = 'Crud\\Handle\\' . $resourceStudly . '\\';
        $handlerClassNameDetails = $generator->createClassNameDetails(
            $resourceStudly . 'CrudHandler',
            $handlerNamespacePrefix
        );

        $templatePrefix = 'admin/' . $resourceSlug;

        $generator->generateClass(
            $handlerClassNameDetails->getFullName(),
            __DIR__ . '/tpl/CrudHandler.tpl.php',
            [
                'resource'        => $resourceSlug,
                'entity_class'    => $entityClass,
                'form_type'       => $formTypeClass,
                'template_prefix' => $templatePrefix,
                'class_name'      => $handlerClassNameDetails->getShortName(),
            ]
        );

        // Also emit a commented per-handler config.yaml next to the handler (idempotent)
        $handlerDir = 'src' . DIRECTORY_SEPARATOR . 'Crud' . DIRECTORY_SEPARATOR . 'Handle' . DIRECTORY_SEPARATOR . $resourceStudly;
        $configPath = $handlerDir . DIRECTORY_SEPARATOR . 'config.yaml';
        if (!file_exists($configPath)) {
            $generator->generateFile(
                $configPath,
                __DIR__ . '/tpl/HandlerConfig.yaml.tpl',
                [
                    'resource'   => $resourceSlug,
                    'class_name' => $handlerClassNameDetails->getShortName(),
                    // Suggest all detected entity fields in comments for quick start
                    'available_fields' => $fieldNames,
                ]
            );
        }

        $generator->writeChanges();

        $io->success(sprintf('Handler CRUD généré : %s', $handlerClassNameDetails->getFullName()));
        $io->text('Pense à créer les templates Twig : ' . $templatePrefix . '/index.html.twig et form.html.twig');
    }
}
