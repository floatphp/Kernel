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
use FloatPHP\Classes\Security\Encryption;

class ApiController extends View
{
	/**
	 * Is HTTP authenticated
	 *
	 * @access public
	 * @param void
	 * @return bool
	 */
	public function isHttpAuthenticated() : bool
	{
		// Init configuration
		$this->initConfig();

		// Basic authentication
		if ( $this->applyFilter('basic-authentication',true) ) {
			if ( Server::isBasicAuth() ) {
				$username = Server::getBasicAuthUser();
				$password = Server::getBasicAuthPwd();
			    if ( $username == $this->getApiUsername() && $password == $this->getApiPassword() ) {
				    return true;
			    }
			}
		} 

		// Bearer token
		if ( ($token = Server::getBearerToken()) ) {
 			return $this->isGranted($token);
		}
		return false;
	}

	/**
	 * Is HTTP granted
	 *
	 * @access protected
	 * @param string $token
	 * @return bool
	 */
	protected function isGranted($token) : bool
	{
        $encryption = new Encryption($token,$this->getSecret());
        $access = explode(':',$encryption->decrypt());
        $username = isset($access[0]) ? $access[0] : false;
        $password = isset($access[1]) ? $access[1] : false;
        if ( $username && $password ) {
			if ( $username == $this->getApiUsername() && $password == $this->getApiPassword() ) {
			    return true;
			}
        }
		return false;
	}
}
