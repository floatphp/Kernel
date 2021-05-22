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
	 * Check current ip access
	 *
	 * @access public
	 * @param void
	 * @return bool
	 */
	public function hasAccess() : bool
	{
		$ip = Server::getIP();

		// Allow local access
		if ( $ip == '127.0.0.1' || $ip == '::1' ) {
			return true;
		}

		// Check allowed IPs
		$allowed = $this->applyFilter('admin-allowed-ip',$this->getAllowedAccess());
		if ( !empty($allowed) ) {
			if ( Stringify::contains($ip,$allowed) ) {
				return false;
			}

		} else {
			// Deny access
			$denied = $this->applyFilter('admin-denied-ip',$this->getDeniedAccess());
			if ( Stringify::contains($ip,$denied) ) {
				return false;
			}
		}

		return true;
	}
}
