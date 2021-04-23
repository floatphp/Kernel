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

class ApiController extends BaseOptions
{
	/**
	 * Is HTTP Authenticated
	 *
	 * @param void
	 * @return bool
	 */
	protected function isHttpAuthenticated()
	{
		if ( Server::isBasicAuth() ) {
			$username = Server::getBasicAuthUser();
			$password = Server::getBasicAuthPwd();
		    if ( $username == $this->getApiUsername() && $password == $this->getApiPassword() ){
			    return true;
		    }
		}
		return false;
	}
}
