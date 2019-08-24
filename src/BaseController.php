<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.0.0
 * @category  : PHP framework
 * @copyright : (c) JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 */

namespace floatphp\Kernel;

use floatPHP\Kernel\View;
use floatPHP\Classes\Html\Hooks;

class BaseController
{
	protected $hook;
	protected $content;
	
	protected function assign($data = [] , $template = null)
	{
		View::assign($data,$template);
	}

	protected function render($data = [] , $template = null)
	{
		echo View::render($data,$template);
	}

	protected function hook($type = 'action', $name, $callbakck = [])
	{
		$hook = Hooks::getInstance();
		switch ($type)
		{
			case 'action':
				$hook->addAction($name,$callbakck);
				break;
			case 'filter':
				$hook->addFilter($name,$callbakck);
				break;
		}
	}

	protected function applyFilterr($filter, $callbakck = [])
	{
		$this->hook = Hooks::getInstance();
		$this->applyFilterr($filter, $callbakck);
	}

	protected function exception($code = null,$message = null)
	{
		return new ErrorController($code,$message);
	}
}
