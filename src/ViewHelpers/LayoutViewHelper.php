<?php

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace TYPO3Fluid\Fluid\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Variables\VariableProviderInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * With this tag, you can select a layout to be used for the current template.
 *
 * Examples
 * ========
 *
 * ::
 *
 *     <f:layout name="main" />
 *
 * Output::
 *
 *     (no output)
 *
 * @api
 */
class LayoutViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments
     *
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument('name', 'string', 'Name of layout to use. If none given, "Default" is used.');
    }

    public function render()
    {
        return null;
    }

    /**
     * @param string $argumentsName
     * @param string $closureName
     * @param string $initializationPhpCode
     * @return string
     */
    public function compile($argumentsName, $closureName, &$initializationPhpCode, ViewHelperNode $node, TemplateCompiler $compiler)
    {
        return '';
    }

    /**
     * On the post parse event, add the "layoutName" variable to the variable container so it can be used by the TemplateView.
     *
     * @param ViewHelperNode $node
     * @param array $arguments
     * @param VariableProviderInterface $variableContainer
     */
    public static function postParseEvent(
        ViewHelperNode $node,
        array $arguments,
        VariableProviderInterface $variableContainer
    ) {
        if (isset($arguments['name'])) {
            $layoutNameNode = $arguments['name'];
        } else {
            $layoutNameNode = 'Default';
        }

        $variableContainer->add('layoutName', $layoutNameNode);
    }
}
