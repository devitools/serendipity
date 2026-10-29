<?php

/* @noinspection DevelopmentDependenciesUsageInspection */

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $config): void {
    $config->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // register a single rule
    $config->rule(InlineConstructorDefaultToPropertyRector::class);

    $config->skip([AddOverrideAttributeToOverriddenMethodsRector::class]);
    $config->skip([
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/tests/Infrastructure/Adapter/Serialize/BuilderTestStubEdgeCase.php',
            __DIR__ . '/tests/Testing/Stub/Variety.php',
        ],
    ]);

    // define sets of rules
    $config->sets([
        LevelSetList::UP_TO_PHP_83,
    ]);

    $config->cacheDirectory('/tmp/rector');
};
