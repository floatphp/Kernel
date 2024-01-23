<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.1.1
 * @copyright  : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Classes\Http\Router;
use FloatPHP\Helpers\Framework\Installer;

final class Core
{
	use TraitException,
		\FloatPHP\Helpers\Framework\inc\TraitDatable,
		\FloatPHP\Helpers\Framework\inc\TraitSessionable;

	/**
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		$config = (new Installer())->parse($config);

		// FloatPHP setup
		if ( $config['--disable-setup'] !== true ) {
			if ( !Installer::isInstalled() ) {
				(new Installer())->setup();
			}
		}

		// FloatPHP X-Powered-By header
		if ( $config['--disable-powered-by'] !== true ) {
			header('X-Powered-By:FloatPHP');
		}
		
		// Start session
		if ( $config['--disable-session'] !== true ) {
			$this->startSession();
			$this->setSession('--default-lang', $config['--default-lang']);
		}

		// Maintenance
		if ( $config['--enable-maintenance'] == true ) {
			$this->throwError(503);
		}

		// FloatPHP timezone
		$this->setDefaultTimezone($config['--default-timezone']);

		// Start routing
		(new Middleware(new Router()))->dispatch();
	}
}
