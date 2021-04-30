<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.0.0
 * @category  : PHP framework
 * @copyright : (c) 2017 - 2021 JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 *
 * This file if a part of FloatPHP Framework
 */

namespace FloatPHP\Kernel;

class BaseController extends View
{
	/**
	 * @access protected
	 * @param string $js
	 * @return void
	 */
	protected function addJS($js)
	{
		$this->addAction('add-js', function() use($js) {
			$this->assign(['js' => $js],'system/js');
		});
	}

	/**
	 * @access protected
	 * @param string $css
	 * @return void
	 */
	protected function addCSS($css)
	{
		$this->addAction('add-css', function() use($css){
			$this->assign(['css' => $css],'system/css');
		});
	}
}
