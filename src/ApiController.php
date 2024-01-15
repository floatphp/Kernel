<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.1.0
 * @copyright  : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

class ApiController extends BaseController
{
	/**
	 * Is HTTP authenticated (Basic).
	 *
	 * @access public
	 * @return bool
	 * @uses initConfig()
	 */
	public function isHttpAuthenticated() : bool
	{
		// Init configuration
		$this->initConfig();

		// Basic authentication
		if ( $this->applyFilter('basic-authentication', true) ) {
			if ( $this->isBasicAuth() ) {

				$username = $this->getBasicAuthUser();
				$password = $this->getBasicAuthPwd();

	        	// API authenticate override
				$this->doAction('api-authenticate', [
					'username' => $username,
					'address'  => $this->getServerIp(),
					'method'   => 'basic'
				]);

			    if ( $username == $this->getApiUsername() 
			      && $password == $this->getApiPassword() ) {
				    return true;
			    }
			}
		}

		// Bearer token authentication
		if ( $this->applyFilter('bearer-authentication', true) ) {
			if ( ($token = $this->getBearerToken()) ) {
	 			return $this->isGranted($token);
			}
		}

		// Extra authentication
		if ( $this->applyFilter('extra-authentication', false) ) {
			return $this->applyFilter('extra-authenticated', false);
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
	protected function isGranted(string $token) : bool
	{
        $access   = $this->getTokenAccess($token, $this->getSecret(true));
        $username = $this->matchString($this->getTokenPattern(), $access, 1);
        $password = $this->matchString($this->getTokenPattern(), $access, 2);

        if ( $username && $password ) {

        	// API authenticate override
			$this->doAction('api-authenticate', [
				'username' => $username,
				'address'  => $this->getServerIp(),
				'method'   => 'token'
			]);

			// Match authentication
			if ( $username == $this->getApiUsername() 
			  && $password == $this->getApiPassword() ) {
			    return true;
			}
        }

		return false;
	}
}
