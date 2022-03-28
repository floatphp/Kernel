<?php
/**
 * @author     : JIHAD SINNAOUR
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.0.0
 * @category   : PHP framework
 * @copyright  : (c) 2017 - 2021 JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link       : https://www.floatphp.com
 * @license    : MIT License
 *
 * This file if a part of FloatPHP Framework
 */

namespace FloatPHP\Kernel;

use FloatPHP\Classes\Http\Server;
use FloatPHP\Classes\Filesystem\Stringify;
use FloatPHP\Classes\Security\Encryption;

class ApiController extends BaseController
{
	/**
	 * Is HTTP authenticated.
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
	        	// API authenticate override
				$this->doAction('api-authenticate',[
					'username' => $username,
					'address'  => Server::getIP(),
					'method'   => 'basic'
				]);
			    if ( $username == $this->getApiUsername() 
			    	&& $password == $this->getApiPassword() ) {
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
	 * Is HTTP granted (Token).
	 *
	 * @access protected
	 * @param string $token
	 * @return bool
	 */
	protected function isGranted($token) : bool
	{
        $encryption = new Encryption($token,$this->getSecret(true));
        $access = $encryption->decrypt();
        $pattern = '/\{(.*?)\}:\{(.*?)\}/';
        $username = Stringify::match($pattern,$encryption->decrypt(),1);
        $password = Stringify::match($pattern,$encryption->decrypt(),2);
        if ( $username && $password ) {
        	// API authenticate override
			$this->doAction('api-authenticate',[
				'username' => $username,
				'address'  => Server::getIP(),
				'method'   => 'token'
			]);
			if ( $username == $this->getApiUsername() 
				&& $password == $this->getApiPassword() ) {
			    return true;
			}
        }
		return false;
	}
}
