<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.1.0
 * @category  : PHP framework
 * @copyright : (c) JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 *
 * This file if a part of FloatPHP Framework
 */

namespace floatPHP\Kernel;

use floatPHP\Classes\Html\Hooks;

class BaseController extends View
{
	/**
	 * @param void
	 * @return object
	 */
	protected $hook;

	/**
	 * @param void
	 * @return object
	 */
	public function __construct()
	{
		$this->initConfig();
	}
	
	/**
	 * @param void
	 * @return object
	 */
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

	/**
	 * @param void
	 * @return object
	 */
	protected function applyFilter($filter, $callbakck = [])
	{
		$this->hook = Hooks::getInstance();
		$this->applyFilter($filter, $callbakck);
	}

	/**
	 * @param void
	 * @return object
	 */
	protected function exception($code = null,$message = null)
	{
		return new ErrorController($code,$message);
	}
}
