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

use FloatPHP\Classes\Auth\Session;
use FloatPHP\Classes\Http\Router;

class Core
{
	/**
	 * @param void
	 */
	public function __construct($config = [])
	{
		// FloatPHP header
		header('X-Powered-By:FloatPHP');

		// Set override config
		if ( isset($config['default-lang']) ) {
			Session::set('default-lang',$config['default-lang']);
		} else {
			Session::set('default-lang','en');
		}
		
		// Start session
		new Session();
		
		// Start routing
		new Middleware(new Router());
	}
}
