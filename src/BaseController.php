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
	 * @param string $src
	 * @return void
	 */
	protected function addJS($src)
	{
		$this->addAction('add-script', function() use($src) {
			$this->assign(['src' => $src],'inc/script');
		});
	}

	/**
	 * @access protected
	 * @param string $href
	 * @return void
	 */
	protected function addCSS($href)
	{
		$this->addAction('add-style', function() use($href){
			$this->assign(['href' => $href],'inc/style');
		});
	}
}
