<?php

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace TYPO3Fluid\Fluid\Core\ViewHelper;

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class ViewHelperInvoker
 *
 * Class which is responsible for calling the render methods
 * on ViewHelpers, and this alone.
 *
 * Can be replaced via the ViewHelperResolver if the system
 * that implements Fluid requires special handling of classes.
 * This includes for example when you want to validate arguments
 * differently, wish to use another ViewHelper initialization
 * process, or wish to store instances of ViewHelpers to reuse
 * as if they were Singletons.
 *
 * To override the instantiation process and class name resolving,
 * see ViewHelperResolver. This particular class should only be
 * responsible for invoking the render method of a ViewHelper
 * using the properties available in the node.
 */
class ViewHelperInvoker
{
    /**
     * Invoke the ViewHelper described by the ViewHelperNode, the properties
     * of which will already have been filled by the ViewHelperResolver.
     *
     * @param string|ViewHelperInterface $viewHelperClassNameOrInstance
     * @param array<string, mixed> $arguments
     * @param RenderingContextInterface $renderingContext
     * @param \Closure|null $renderChildrenClosure
     * @param bool $trustArguments  If set to true, merging with default values
     *                              and checking for undeclared arguments won't
     *                              be performed. This increases performance when
     *                              the ViewHelper is invoked from a cached template
     *                              where these checks have already been performed
     * @return string
     */
    public function invoke($viewHelperClassNameOrInstance, array $arguments, RenderingContextInterface $renderingContext, \Closure $renderChildrenClosure = null, bool $trustArguments = false)
    {
        $viewHelperResolver = $renderingContext->getViewHelperResolver();
        if ($viewHelperClassNameOrInstance instanceof ViewHelperInterface) {
            $viewHelper = $viewHelperClassNameOrInstance;
        } else {
            $viewHelper = $viewHelperResolver->createViewHelperInstanceFromClassName($viewHelperClassNameOrInstance);
        }

        // Rendering process
        try {
            if ($renderChildrenClosure) {
                $viewHelper->setRenderChildrenClosure($renderChildrenClosure);
            }
            $viewHelper->setRenderingContext($renderingContext);

            if ($trustArguments) {
                $viewHelper->setArguments($arguments);
            } else {
                $expectedViewHelperArguments = $viewHelperResolver->getArgumentDefinitionsForViewHelper($viewHelper);

                $evaluatedArguments = [];
                foreach ($expectedViewHelperArguments as $argumentName => $argumentDefinition) {
                    if (isset($arguments[$argumentName])) {
                        /** @var NodeInterface|mixed $argumentValue */
                        $argumentValue = $arguments[$argumentName];
                        $evaluatedArguments[$argumentName] = $argumentValue instanceof NodeInterface ? $argumentValue->evaluate($renderingContext) : $argumentValue;
                    } else {
                        $evaluatedArguments[$argumentName] = $argumentDefinition->getDefaultValue();
                    }
                }

                $undeclaredArguments = [];
                foreach ($arguments as $argumentName => $argumentValue) {
                    if (!array_key_exists($argumentName, $evaluatedArguments)) {
                        $undeclaredArguments[$argumentName] = $argumentValue instanceof NodeInterface ? $argumentValue->evaluate($renderingContext) : $argumentValue;
                    }
                }

                $viewHelper->setArguments($evaluatedArguments);
                $viewHelper->handleAdditionalArguments($undeclaredArguments);
            }

            return $viewHelper->initializeArgumentsAndRender();
        } catch (Exception $error) {
            return $renderingContext->getErrorHandler()->handleViewHelperError($error);
        }
    }
}
