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
use FloatPHP\Helpers\Configurator;

final class Core
{
	/**
	 * @param void
	 */
	public function __construct($config = [])
	{
		$config = Configurator::parse($config);

		// FloatPHP setup
		if ( $config['--disable-setup'] !== true ) {
			$configurator = new Configurator();
			$configurator->setup();
		}

		// FloatPHP X-Powered-By header
		if ( $config['--disable-powered-by'] !== true ) {
			header('X-Powered-By:FloatPHP');
		}
		
		// Start session
		if ( $config['--disable-session'] !== true ) {
			new Session();
			Session::set('--default-lang',$config['--default-lang']);
		}
		
		// Start routing
		new Middleware(new Router());
	}
}
