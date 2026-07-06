<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\Class_\DynamicDocBlockPropertyToNativePropertyRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodingStyle\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\CodingStyle\Rector\FuncCall\CallUserFuncArrayToVariadicRector;
use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromBooleanConstReturnsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\NumericReturnTypeFromStrictScalarReturnsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictScalarReturnsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withImportNames(removeUnusedImports: true)
    ->withTypeCoverageLevel(0)
    // PHP 8.3 features: constructor property promotion, readonly, override attribute
    ->withPhpSets(php83: true)
    // Type-safety improvements: explicit return/param types, typed properties
    ->withRules([
        AddParamTypeDeclarationRector::class,
        AddReturnTypeDeclarationRector::class,
        TypedPropertyFromAssignsRector::class,
        BoolReturnTypeFromBooleanConstReturnsRector::class,
        NumericReturnTypeFromStrictScalarReturnsRector::class,
        StringReturnTypeFromStrictScalarReturnsRector::class,
        CompleteDynamicPropertiesRector::class,
        DynamicDocBlockPropertyToNativePropertyRector::class,
        InlineConstructorDefaultToPropertyRector::class,
    ])
    // Code quality: remove dead code, simplify expressions
    ->withRules([
        RemoveEmptyClassMethodRector::class,
        RemoveUnusedPrivateMethodRector::class,
        SimplifyUselessVariableRector::class,
    ])
    // Coding style: prefer modern array/function call APIs
    ->withRules([
        ArraySpreadInsteadOfArrayMergeRector::class,
        CallUserFuncArrayToVariadicRector::class,
        FunctionFirstClassCallableRector::class,
    ]);