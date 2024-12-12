<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.4.x
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
	 */
	public function isHttpAuthenticated() : bool
	{
		// Basic authentication
		if ( $this->applyFilter('basic-auth', true) ) {
			if ( $this->isBasicAuth() ) {

				$user = $this->getBasicAuthUser();
				$pswd = $this->getBasicAuthPwd();

				// API authenticate override
				$this->doAction('api-authenticate', [
					'username' => $user,
					'address'  => $this->getServerIp(),
					'method'   => 'basic'
				]);

				if (
					$user == $this->getApiUsername()
					&& $pswd == $this->getApiPassword()
				) {
					return true;
				}
			}
		}

		// Bearer token authentication
		if ( $this->applyFilter('bearer-auth', true) ) {
			if ( ($token = $this->getBearerToken()) ) {
				return $this->isGranted($token);
			}
		}

		// Extra authentication
		if ( $this->applyFilter('extra-auth', false) ) {
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
		$access = $this->getAccessToken($token, $this->getSecret(true));
		$user = $access['user'] ?? false;
		$pswd = $access['pswd'] ?? false;

		if ( $user && $pswd ) {

			// API authenticate override
			$this->doAction('api-authenticate', [
				'username' => $user,
				'address'  => $this->getServerIp(),
				'method'   => 'token'
			]);

			// Match authentication
			if ( $user == $this->getApiUsername() && $pswd == $this->getApiPassword() ) {
				return true;
			}
		}

		return false;
	}
}
