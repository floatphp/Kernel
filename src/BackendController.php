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

use FloatPHP\Classes\Http\Server;
use FloatPHP\Classes\Filesystem\Stringify;

class BackendController extends BaseController
{
	/**
	 * @access protected
	 * @param void
	 * @return boolean
	 */
	protected function isAdmin()
	{
		$remote = Server::getRemote();

		// Allow local access
		if ( $remote == '127.0.0.1' || $remote == '::1' ) {
			return true;
		}

		// Check allowed IPs
		$allowed = $this->getAllowedAccess();
		if ( !empty($allowed) ) {
			if ( Stringify::contains($remote, $allowed) ) {
				return false;
			}

		} else {
			// Deny access
			$denied = $this->getDeniedAccess();
			if ( Stringify::contains($remote, $denied) ) {
				return false;
			}
		}

		return true;
	}
}
