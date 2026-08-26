<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use RectorLaravel\Rector\MethodCall\WhereToWhereLikeRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true, )
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/config',
        __DIR__ . '/routes',
    ])
    ->withSets([
        Rector\Set\ValueObject\SetList::DEAD_CODE,
        Rector\Set\ValueObject\SetList::CODE_QUALITY,
        Rector\Set\ValueObject\SetList::CODING_STYLE,
        Rector\Set\ValueObject\SetList::TYPE_DECLARATION,
        Rector\Set\ValueObject\SetList::PRIVATIZATION,
        Rector\Set\ValueObject\SetList::EARLY_RETURN,
        Rector\Set\ValueObject\SetList::INSTANCEOF,
        Rector\Set\ValueObject\LevelSetList::UP_TO_PHP_84,

        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_IF_HELPERS,
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,

        PestSetList::CODING_STYLE,
    ])
    ->withConfiguredRule(WhereToWhereLikeRector::class, [])
    ->withSkip([
        Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector::class,
        Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector::class,
        Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector::class,
        Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,
        Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector::class,
        Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector::class,
        Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class,
        Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector::class,
    ])
    ->withImportNames(importDocBlockNames: false, importShortClasses: false);
