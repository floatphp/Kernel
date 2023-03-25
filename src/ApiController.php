<?php
/**
 * @author     : JIHAD SINNAOUR
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.0.2
 * @category   : PHP framework
 * @copyright  : (c) 2017 - 2023 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://www.floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Classes\{
    Http\Server,
    Security\Encryption,
    Filesystem\Stringify
};

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

		// Bearer token authentication
		if ( $this->applyFilter('bearer-authentication',true) ) {
			if ( ($token = Server::getBearerToken()) ) {
	 			return $this->isGranted($token);
			}
		}

		// Extra authentication
		if ( $this->applyFilter('extra-authentication',false) ) {
			return $this->applyFilter('extra-authenticated',false);
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
        $encryption = new Encryption($token, $this->getSecret(true));
        $access = $encryption->decrypt();
        $pattern = '/{user:(.*?)}{pswd:(.*?)}/';
        $username = Stringify::match($pattern,$access,1);
        $password = Stringify::match($pattern,$access,2);

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
