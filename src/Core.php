<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.5.x
 * @copyright  : (c) 2018 - 2025 Jihad Sinnaour <me@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file is a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Classes\Http\Router;
use FloatPHP\Helpers\Framework\Installer;

final class Core
{
	use TraitException,
		\FloatPHP\Helpers\Framework\tr\TraitDatable,
		\FloatPHP\Helpers\Framework\tr\TraitSessionable;

	/**
	 * Init Core.
	 *
	 * @access public
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		$config = (new Installer())->default($config);

		// Setup
		if ( $config['--enable-setup'] === true ) {
			if ( !Installer::isInstalled() ) {
				$db = ($config['--enable-database'] === true) ?: false;
				(new Installer())->setup($db);
			}
		}

		// X-Powered-By header
		if ( $config['--disable-powered-by'] !== true ) {
			$generator = $config['--powered-by'] ?? 'FloatPHP';
			header("X-Powered-By: {$generator}");
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

		// Timezone
		$this->setDefaultTimezone($config['--default-timezone']);

		// Start routing
		(new Middleware(router: new Router()))->dispatch();
	}
}
