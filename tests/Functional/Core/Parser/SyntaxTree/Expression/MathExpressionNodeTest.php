<?php

declare(strict_types=1);

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace TYPO3Fluid\Fluid\Tests\Functional\Core\Parser\SyntaxTree\Expression;

use TYPO3Fluid\Fluid\Tests\Functional\AbstractFunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class MathExpressionNodeTest extends AbstractFunctionalTestCase
{
    public static function variableConditionDataProvider(): array
    {
        return [
            'add number and integer variable' => [
                '{12 - num}',
                ['num' => 2],
                10,
            ],
            'add number and string variable' => [
                '{12 - num}',
                ['num' => '2'],
                10,
            ],
            'add number and string variable set by viewhelper' => [
                '<f:variable name="num" value="2"/>{12 - num}',
                [],
                '10',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider variableConditionDataProvider
     */
    public function variableCondition(string $source, array $variables, $expected): void
    {
        $view = new TemplateView();
        $view->assignMultiple($variables);
        $view->getRenderingContext()->setCache(self::$cache);
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($source);
        self::assertSame($expected, $view->render());

        $view = new TemplateView();
        $view->assignMultiple($variables);
        $view->getRenderingContext()->setCache(self::$cache);
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($source);
        self::assertSame($expected, $view->render());
    }
}
