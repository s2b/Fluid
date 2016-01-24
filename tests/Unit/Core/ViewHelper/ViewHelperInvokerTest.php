<?php
namespace TYPO3Fluid\Fluid\Tests\Unit\Core\ViewHelper;

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInvoker;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3Fluid\Fluid\Tests\UnitTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;
use TYPO3Fluid\Fluid\ViewHelpers\CountViewHelper;

/**
 * Class ViewHelperInvokerTest
 */
class ViewHelperInvokerTest extends UnitTestCase {

	public function testInvokeViewHelper() {
		$view = new TemplateView();
		$resolver = new ViewHelperResolver();
		$invoker = new ViewHelperInvoker($resolver);
		$renderingContext = new RenderingContext($view);
		$result = $invoker->invoke(CountViewHelper::class, array('subject' => array('foo')), $renderingContext);
		$this->assertEquals(1, $result);
	}

}
