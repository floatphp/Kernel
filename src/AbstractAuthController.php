<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.3.x
 * @copyright  : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Interfaces\Kernel\AuthenticationInterface;

abstract class AbstractAuthController extends BaseController
{
	/**
	 * @access public
	 * @return void
	 */
	abstract public function login();

	/**
	 * Check whether current user is authenticated.
	 * 
	 * @access public
	 * @return bool
	 */
	public function isAuthenticated() : bool
	{
		if ( $this->getSession($this->getSessionId()) ) {
			return $this->isValidSession();
		}
		return false;
	}

	/**
	 * @access protected
	 * @param AuthenticationInterface $auth
	 * @param array $args
	 * @return void
	 */
	protected function authenticate(AuthenticationInterface $auth, array $args = []) : void
	{
		// Security
		$this->verifyRequest(force: true);

		// Get authentication
		$args = $this->mergeArray([
			'username' => $this->getRequest('username'),
			'password' => $this->getRequest('password')
		], $args);

		$username = (string)$args['username'];
		$password = (string)$args['password'];

		// Authenticate override
		$this->doAction('authenticate', $username);

		// Verify authentication
		if ( ($user = $auth->getUser($username)) ) {

			// Check password
			if ( $this->isPassword($password, $user['password']) ) {

				// Check password format
				if ( $this->applyFilter('auth-strong-pswd', false) ) {
					if ( !$this->isStrongPassword($password) ) {
						// Authenticate failed
						$msg = $this->applyFilter('auth-pswd-msg', 'Strong password required');
						$msg = $this->translate($msg);
						$this->setResponse($msg, [], 'warning');
					}
				}

				// Register session
				$this->registerSession(
					$this->getAccessExpire()
				);

				// Check valid session
				if ( $this->isValidSession() ) {

					if ( $auth->hasSecret($username) ) {
						$this->setSession('--verify', $username);
						// Authenticate accepted
						$msg = $this->applyFilter('auth-accepted-msg', 'Accepted');
						$msg = $this->translate($msg);
						$this->setResponse($msg, [], 'accepted', 202);

					} else {
						$this->setSession($auth->getKey(), $user[$auth->getKey()]);
						// Authenticate success
						$msg = $this->applyFilter('auth-success-msg', 'Connected');
						$msg = $this->translate($msg);
						$this->setResponse($msg);
					}

				} else {
					$this->endSession();
				}
			}
		}

		// Authenticate failed override
		$this->doAction('auth-failed', $username);

		// Authenticate failed
		$msg = $this->applyFilter('auth-error-msg', 'Authentication failed');
		$msg = $this->translate($msg);
		$this->setResponse($msg, [], 'error', 401);
	}
}
