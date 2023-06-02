<?php declare(strict_types=1);
/*
 * This file is part of ksaveras/circuit-breaker-bundle.
 *
 * (c) Ksaveras Sakys <xawiers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodingStyle\Rector\ClassConst\VarConstantCommentRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitLevelSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\FunctionLike\AddReturnTypeDeclarationFromYieldsRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/DependencyInjection',
        __DIR__.'/Resources',
        __DIR__.'/Tests',
    ]);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::PRIVATIZATION,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::TYPE_DECLARATION,
        PHPUnitLevelSetList::UP_TO_PHPUNIT_100,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ]);

    $rectorConfig->skip([
        AddReturnTypeDeclarationFromYieldsRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        NewlineAfterStatementRector::class,
        NewlineBeforeNewAssignSetRector::class,
        VarConstantCommentRector::class,
    ]);
};
