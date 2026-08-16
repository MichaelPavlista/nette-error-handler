<?php declare(strict_types=1);

use Rector\CodeQuality\Rector\FuncCall\SortCallLikeNamedArgsRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php70\Rector\FuncCall\RandomFunctionRector;
use Rector\Php70\Rector\MethodCall\ThisCallOnStaticMethodToStaticCallRector;
use Rector\Php74\Rector\Assign\NullCoalescingOperatorRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\FuncCall\ClassOnObjectRector;
use Rector\Php80\Rector\Identical\StrStartsWithRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__ . '/src']);

    $rectorConfig->rules([
        RemoveUnusedPromotedPropertyRector::class,
        StrStartsWithRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        RemoveUselessVarTagRector::class,
        NullCoalescingOperatorRector::class,
        LongArrayToShortArrayRector::class,
        ChangeSwitchToMatchRector::class,
        StrContainsRector::class,
        RemoveUnusedVariableInCatchRector::class,
        ClassOnObjectRector::class,
        RandomFunctionRector::class,
        ThisCallOnStaticMethodToStaticCallRector::class,
        StringClassNameToClassConstantRector::class,
        PrivatizeFinalClassMethodRector::class,
        PrivatizeFinalClassPropertyRector::class,
        ArrayToFirstClassCallableRector::class,
        ReadOnlyPropertyRector::class,
        SimplifyBoolIdenticalTrueRector::class,
        SortCallLikeNamedArgsRector::class,
    ]);

    // Definice pravidel a setů pravidel.
    $sets = [
        SetList::PHP_52,
        SetList::PHP_53,
        SetList::PHP_54,
        SetList::PHP_55,
        SetList::PHP_56,
        SetList::PHP_70,
        SetList::PHP_71,
        SetList::PHP_72,
        SetList::PHP_73,
        SetList::PHP_74,
        SetList::PHP_80,
        SetList::PHP_81,
        SetList::PHP_82,
    ];

    // Přidání pravidel pro novější verze PHP podle aktuální verze PHP.
    if (PHP_VERSION_ID >= 80300) {
        $sets[] = SetList::PHP_83;
    }

    if (PHP_VERSION_ID >= 80400) {
        $sets[] = SetList::PHP_84;
    }

    if (PHP_VERSION_ID >= 80500) {
        $sets[] = SetList::PHP_85;
    }

    $rectorConfig->sets($sets);

    $rectorConfig->skip([RestoreDefaultNullToNullableTypePropertyRector::class]);

    $rectorConfig->cacheDirectory(__DIR__ . '/temp/.tools-cache/rector');
};
