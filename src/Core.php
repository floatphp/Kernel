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

use FloatPHP\Classes\Http\Session;
use FloatPHP\Classes\Http\Router;
use FloatPHP\Classes\Server\Date;
use FloatPHP\Helpers\Framework\Configurator;

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

		// Maintenance
		if ( $config['--enable-maintenance'] == true ) {
			new ErrorController(503);
		}

		// FloatPHP timezone
		Date::setDefaultTimezone($config['--default-timezone']);

		// Start routing
		$middleware = new Middleware(new Router());
		$middleware->dispatch();
	}
}
