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

namespace floatPHP\Kernel;

use floatPHP\Classes\Storage\Json;

final class Configuration
{
	public $global;
	public $routes;
	/**
	 * @param void
	 * @return void
	 */
	public function __construct()
	{
		$this->set();
	}
	/**
	 * @param void
	 * @return void
	 */
	private function set()
	{
		// define global configuration
		$global = new Json('App/Storage/config/global');
		$this->global = $global->parseObject();

		// set route configuration
		$routes = new Json($this->global->system->root);
		$this->routes = $routes->parse();
	}
}
