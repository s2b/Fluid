<?php

declare(strict_types=1);

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace TYPO3Fluid\Fluid\Tests\Unit\Core\Parser\SyntaxTree;

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ArrayNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\BooleanNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NumericNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Tests\UnitTestCase;

class BooleanNodeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function convertToBooleanProperlyConvertsValuesOfTypeBoolean(): void
    {
        $renderingContext = new RenderingContext();
        self::assertFalse(BooleanNode::convertToBoolean(false, $renderingContext));
        self::assertTrue(BooleanNode::convertToBoolean(true, $renderingContext));
    }

    /**
     * @test
     */
    public function convertToBooleanProperlyConvertsValuesOfTypeString(): void
    {
        $renderingContext = new RenderingContext();
        self::assertFalse(BooleanNode::convertToBoolean('', $renderingContext));
        self::assertFalse(BooleanNode::convertToBoolean('false', $renderingContext));
        self::assertFalse(BooleanNode::convertToBoolean('FALSE', $renderingContext));
        self::assertTrue(BooleanNode::convertToBoolean('true', $renderingContext));
        self::assertTrue(BooleanNode::convertToBoolean('TRUE', $renderingContext));
    }

    public static function getNumericBooleanTestValues(): array
    {
        return [
            [0, false],
            [-1, true],
            ['-1', true],
            [-.5, true],
            [1, true],
            [.5, true],
        ];
    }

    /**
     * @param mixed $number
     * @test
     * @dataProvider getNumericBooleanTestValues
     */
    public function convertToBooleanProperlyConvertsNumericValues($number, bool $expected): void
    {
        $renderingContext = new RenderingContext();
        self::assertEquals($expected, BooleanNode::convertToBoolean($number, $renderingContext));
    }

    /**
     * @test
     */
    public function convertToBooleanProperlyConvertsValuesOfTypeArray(): void
    {
        $renderingContext = new RenderingContext();
        self::assertFalse(BooleanNode::convertToBoolean([], $renderingContext));
        self::assertTrue(BooleanNode::convertToBoolean(['foo'], $renderingContext));
        self::assertTrue(BooleanNode::convertToBoolean(['foo' => 'bar'], $renderingContext));
    }

    /**
     * @test
     */
    public function convertToBooleanProperlyConvertsObjects(): void
    {
        $renderingContext = new RenderingContext();
        self::assertFalse(BooleanNode::convertToBoolean(null, $renderingContext));
        self::assertTrue(BooleanNode::convertToBoolean(new \stdClass(), $renderingContext));
    }

    public static function getCreateFromNodeAndEvaluateTestValues(): array
    {
        return [
            '1 && 1' => [new TextNode('1 && 1'), true],
            '1 && 0' => [new TextNode('1 && 0'), false],
            '(1 && 1) && 1' => [new TextNode('(1 && 1) && 1'), true],
            '(1 && 0) || 1' => [new TextNode('(1 && 0) || 1'), true],
            '(\'text\' == \'text\') || 1 >= 0' => [new TextNode('(\'text\' == \'text\') || 1 >= 0'), true],
            '1 <= 0' => [new TextNode('1 <= 0'), false],
            '1 > 4' => [new TextNode('1 > 4'), false],
            '1 < 4' => [new TextNode('1 < 4'), true],
            '4 % 4' => [new TextNode('4 % 4'), false],
            '2 % 4' => [new TextNode('2 % 4'), true],
            '0 && 1' => [new TextNode('0 && 1'), false],
        ];
    }

    /**
     * @dataProvider getCreateFromNodeAndEvaluateTestValues
     * @param bool $expected
     * @test
     */
    public function testCreateFromNodeAndEvaluate(NodeInterface $node, bool $expected): void
    {
        $renderingContext = new RenderingContext();
        $result = BooleanNode::createFromNodeAndEvaluate($node, $renderingContext);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function comparingNestedComparisonsWorks(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('('));
        $rootNode->addChildNode(new ArrayNode(['foo' => 'bar']));
        $rootNode->addChildNode(new TextNode('=='));
        $rootNode->addChildNode(new ArrayNode(['foo' => 'bar']));
        $rootNode->addChildNode(new TextNode(')'));
        $rootNode->addChildNode(new TextNode('&&'));
        $rootNode->addChildNode(new TextNode('1'));
        self::assertTrue(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function comparingEqualNumbersReturnsTrue(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('5'));
        $rootNode->addChildNode(new TextNode('=='));
        $rootNode->addChildNode(new TextNode('5'));
        $booleanNode = new BooleanNode($rootNode);
        self::assertTrue($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function comparingUnequalNumbersReturnsFalse(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('5'));
        $rootNode->addChildNode(new TextNode('=='));
        $rootNode->addChildNode(new TextNode('3'));
        $booleanNode = new BooleanNode($rootNode);
        self::assertFalse($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function comparingEqualIdentityReturnsTrue(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('5'));
        $rootNode->addChildNode(new TextNode('==='));
        $rootNode->addChildNode(new TextNode('5'));
        $booleanNode = new BooleanNode($rootNode);
        self::assertTrue($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function comparingUnequalIdentityReturnsFalse(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new NumericNode('0'));
        $rootNode->addChildNode(new TextNode('==='));
        $rootNode->addChildNode(new BooleanNode(false));
        $booleanNode = new BooleanNode($rootNode);
        self::assertFalse($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function notEqualReturnsFalseIfNumbersAreEqual(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('5'));
        $rootNode->addChildNode(new TextNode('!='));
        $rootNode->addChildNode(new TextNode('5'));
        $booleanNode = new BooleanNode($rootNode);
        self::assertFalse($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function notEqualReturnsTrueIfNumbersAreNotEqual(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('5'));
        $rootNode->addChildNode(new TextNode('!='));
        $rootNode->addChildNode(new TextNode('3'));
        $booleanNode = new BooleanNode($rootNode);
        self::assertTrue($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function oddNumberModulo2ReturnsTrue(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('43'));
        $rootNode->addChildNode(new TextNode('%'));
        $rootNode->addChildNode(new TextNode('2'));
        $booleanNode = new BooleanNode($rootNode);
        self::assertTrue($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function evenNumberModulo2ReturnsFalse(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('42'));
        $rootNode->addChildNode(new TextNode('%'));
        $rootNode->addChildNode(new TextNode('2'));
        $booleanNode = new BooleanNode($rootNode);
        self::assertFalse($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function greaterThanReturnsTrueIfNumberIsReallyGreater(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('10'));
        $rootNode->addChildNode(new TextNode('>'));
        $rootNode->addChildNode(new TextNode('9'));
        $booleanNode = new BooleanNode($rootNode);
        self::assertTrue($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function greaterThanReturnsFalseIfNumberIsEqual(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('10'));
        $rootNode->addChildNode(new TextNode('>'));
        $rootNode->addChildNode(new TextNode('10'));
        $booleanNode = new BooleanNode($rootNode);
        self::assertFalse($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function greaterOrEqualsReturnsTrueIfNumberIsReallyGreater(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('10'));
        $rootNode->addChildNode(new TextNode('>='));
        $rootNode->addChildNode(new TextNode('9'));
        $booleanNode = new BooleanNode($rootNode);
        self::assertTrue($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function greaterOrEqualsReturnsTrueIfNumberIsEqual(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('10'));
        $rootNode->addChildNode(new TextNode('>='));
        $rootNode->addChildNode(new TextNode('10'));
        self::assertTrue(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function greaterOrEqualsReturnFalseIfNumberIsSmaller(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('10'));
        $rootNode->addChildNode(new TextNode('>='));
        $rootNode->addChildNode(new TextNode('11'));
        self::assertFalse(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function lessThanReturnsTrueIfNumberIsReallyless(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('9'));
        $rootNode->addChildNode(new TextNode('<'));
        $rootNode->addChildNode(new TextNode('10'));
        self::assertTrue(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function lessThanReturnsFalseIfNumberIsEqual(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('10'));
        $rootNode->addChildNode(new TextNode('<'));
        $rootNode->addChildNode(new TextNode('10'));
        self::assertFalse(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function lessOrEqualsReturnsTrueIfNumberIsReallyLess(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('9'));
        $rootNode->addChildNode(new TextNode('<='));
        $rootNode->addChildNode(new TextNode('10'));
        self::assertTrue(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function lessOrEqualsReturnsTrueIfNumberIsEqual(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('10'));
        $rootNode->addChildNode(new TextNode('<='));
        $rootNode->addChildNode(new TextNode('10'));
        self::assertTrue(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function lessOrEqualsReturnFalseIfNumberIsBigger(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('11'));
        $rootNode->addChildNode(new TextNode('<='));
        $rootNode->addChildNode(new TextNode('10'));
        self::assertFalse(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function lessOrEqualsReturnFalseIfComparingWithANegativeNumber(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('11 <= -2.1'));
        self::assertFalse(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    private function getDummyRenderingContextWithVariables(array $variables): RenderingContextInterface
    {
        $renderingContext = new RenderingContext();
        $renderingContext->setVariableProvider(new StandardVariableProvider($variables));
        $renderingContext->getVariableProvider()->setSource($variables);
        return $renderingContext;
    }

    /**
     * @test
     */
    public function comparingVariableWithMatchedQuotedString(): void
    {
        $renderingContext = $this->getDummyRenderingContextWithVariables(['test' => 'somevalue']);
        $rootNode = new RootNode();
        $rootNode->addChildNode(new ObjectAccessorNode('test'));
        $rootNode->addChildNode(new TextNode(' == '));
        $rootNode->addChildNode(new TextNode('\'somevalue\''));
        self::assertTrue(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function comparingVariableWithUnmatchedQuotedString(): void
    {
        $renderingContext = $this->getDummyRenderingContextWithVariables(['test' => 'somevalue']);
        $rootNode = new RootNode();
        $rootNode->addChildNode(new ObjectAccessorNode('test'));
        $rootNode->addChildNode(new TextNode(' != '));
        $rootNode->addChildNode(new TextNode('\'othervalue\''));
        self::assertTrue(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function comparingNotEqualsVariableWithMatchedQuotedString(): void
    {
        $renderingContext = $this->getDummyRenderingContextWithVariables(['test' => 'somevalue']);
        $rootNode = new RootNode();
        $rootNode->addChildNode(new ObjectAccessorNode('test'));
        $rootNode->addChildNode(new TextNode(' != '));
        $rootNode->addChildNode(new TextNode('\'somevalue\''));
        self::assertFalse(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function comparingNotEqualsVariableWithUnmatchedQuotedString(): void
    {
        $renderingContext = $this->getDummyRenderingContextWithVariables(['test' => 'somevalue']);
        $rootNode = new RootNode();
        $rootNode->addChildNode(new ObjectAccessorNode('test'));
        $rootNode->addChildNode(new TextNode(' != '));
        $rootNode->addChildNode(new TextNode('\'somevalue\''));
        self::assertFalse(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function comparingEqualsVariableWithMatchedQuotedStringInSingleTextNode(): void
    {
        $renderingContext = $this->getDummyRenderingContextWithVariables(['test' => 'somevalue']);
        $rootNode = new RootNode();
        $rootNode->addChildNode(new ObjectAccessorNode('test'));
        $rootNode->addChildNode(new TextNode(' != \'somevalue\''));
        self::assertFalse(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function notEqualReturnsFalseIfComparingMatchingStrings(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('\'stringA\' != "stringA"'));
        self::assertFalse(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function notEqualReturnsTrueIfComparingNonMatchingStrings(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('\'stringA\' != \'stringB\''));
        self::assertTrue(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfComparingNonMatchingStrings(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('\'stringA\' == \'stringB\''));
        self::assertFalse(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfComparingMatchingStrings(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('\'stringA\' == "stringA"'));
        self::assertTrue(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfComparingMatchingStringsWithEscapedQuotes(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('\'\\\'stringA\\\'\' == \'\\\'stringA\\\'\''));
        self::assertTrue(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfComparingStringWithNonZero(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('\'stringA\' == 42'));
        self::assertFalse(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfComparingStringWithZero(): void
    {
        $renderingContext = new RenderingContext();
        // expected value based on php versions behaviour
        $expected = (PHP_VERSION_ID < 80000 ? true : false);
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('\'stringA\' == 0'));
        self::assertSame($expected, BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfComparingStringZeroWithZero(): void
    {
        $renderingContext = new RenderingContext();
        $rootNode = new RootNode();
        $rootNode->addChildNode(new TextNode('\'0\' == 0'));
        self::assertTrue(BooleanNode::createFromNodeAndEvaluate($rootNode, $renderingContext));
    }

    /**
     * @test
     */
    public function objectsAreComparedStrictly(): void
    {
        $renderingContext = new RenderingContext();
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $rootNode = new RootNode();

        $object1Node = $this->createMock(ObjectAccessorNode::class);
        $object1Node->expects(self::any())->method('evaluate')->willReturn($object1);

        $object2Node = $this->createMock(ObjectAccessorNode::class);
        $object2Node->expects(self::any())->method('evaluate')->willReturn($object2);

        $rootNode->addChildNode($object1Node);
        $rootNode->addChildNode(new TextNode('=='));
        $rootNode->addChildNode($object2Node);

        $booleanNode = new BooleanNode($rootNode);
        self::assertFalse($booleanNode->evaluate($renderingContext));
    }

    /**
     * @test
     */
    public function objectsAreComparedStrictlyInUnequalComparison(): void
    {
        $renderingContext = new RenderingContext();
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $rootNode = new RootNode();

        $object1Node = $this->createMock(ObjectAccessorNode::class);
        $object1Node->expects(self::any())->method('evaluate')->willReturn($object1);

        $object2Node = $this->createMock(ObjectAccessorNode::class);
        $object2Node->expects(self::any())->method('evaluate')->willReturn($object2);

        $rootNode->addChildNode($object1Node);
        $rootNode->addChildNode(new TextNode('!='));
        $rootNode->addChildNode($object2Node);

        $booleanNode = new BooleanNode($rootNode);
        self::assertTrue($booleanNode->evaluate($renderingContext));
    }

    public static function getStandardInputTypes(): array
    {
        return [
            [0, false],
            [1, true],
            [false, false],
            [true, true],
            [null, false],
            ['', false],
            ['0', false],
            ['1', true],
            [[1], true],
            [[0], false],
        ];
    }

    /**
     * @test
     * @dataProvider getStandardInputTypes
     */
    public function acceptsStandardTypesAsInput(mixed $input, bool $expected): void
    {
        $renderingContext = new RenderingContext();
        $node = new BooleanNode($input);
        self::assertEquals($expected, $node->evaluate($renderingContext));
    }
}
