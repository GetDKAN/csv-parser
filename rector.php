<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\ArrayShapeFromConstantArrayReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
    __DIR__ . '/src',
    __DIR__ . '/test',
    ]);

    $rectorConfig->sets([
        SetList::PHP_74,
        // Please no dead code or unneeded variables.
        SetList::DEAD_CODE,
        // Try to figure out type hints.
        SetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};
