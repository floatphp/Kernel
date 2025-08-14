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
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Interfaces\Kernel\AuthenticationInterface;

abstract class AbstractAuth extends BaseController
{
	/**
	 * Login.
	 *
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
	 * Authenticate user.
	 *
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
			'user' => $this->getRequest('user'),
			'pswd' => $this->getRequest('pswd')
		], $args);

		$user = (string)$args['user'];
		$pswd = (string)$args['pswd'];

		// Authenticate override
		$this->doAction('authenticate', $user);

		// Verify authentication
		if ( ($data = $auth->getUser($user)) ) {

			// Check password
			if ( $this->isPassword($pswd, $data['password']) ) {

				// Check password format
				if ( $this->applyFilter('auth-strong-pswd', false) ) {
					if ( !$this->isStrongPassword($pswd) ) {
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

					if ( $auth->hasSecret($user) ) {
						$this->setSession('--verify', $user);
						// Authenticate accepted
						$msg = $this->applyFilter('auth-accepted-msg', 'Accepted');
						$msg = $this->translate($msg);
						$this->setResponse($msg, [], 'accepted', 202);

					} else {
						$this->setSession($auth->getKey(), $data[$auth->getKey()]);
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
		$this->doAction('auth-failed', $user);

		// Authenticate failed
		$msg = $this->applyFilter('auth-error-msg', 'Authentication failed');
		$msg = $this->translate($msg);
		$this->setResponse($msg, [], 'error', 401);
	}
}
