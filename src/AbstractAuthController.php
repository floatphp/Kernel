<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.1.1
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
	protected function authenticate(AuthenticationInterface $auth, $args = [])
	{
		// Security
		$this->verifyRequest(true);

		// Get authentication
		$args = $this->mergeArray([
			'username' => $this->getRequest('username'),
			'password' => $this->getRequest('password')
		], $args);

		// Authenticate override
		$this->doAction('authenticate', $args['username']);

		// Verify authentication
		if ( ($user = $auth->getUser($args['username'])) ) {

			// Check password
			if ( $this->isPassword($args['password'], $user['password']) ) {

				// Check password format
				if ( $this->applyFilter('authenticate-strong-password', false) ) {
					if ( !$this->isStrongPassword($args['password']) ) {
						// Authenticate failed
						$msg = $this->applyFilter('authenticate-password-message', 'Strong password required');
						$msg = $this->translate($msg);
						$this->setResponse($msg, [], 'warning');
					}
				}

				// Register session
				$this->registerSession($this->getAccessExpire());

				// Check valid session
				if ( $this->isValidSession() ) {

					if ( $auth->hasSecret($args['username']) ) {
						$this->setSession('--verify', $args['username']);
						// Authenticate accepted
						$msg = $this->applyFilter('authenticate-accepted-message', 'Accepted');
						$msg = $this->translate($msg);
						$this->setResponse($msg, [], 'accepted', 202);

					} else {
						$this->setSession($auth->getKey(),$user[$auth->getKey()]);
						// Authenticate success
						$msg = $this->applyFilter('authenticate-success-message', 'Connected');
						$msg = $this->translate($msg);
						$this->setResponse($msg);
					}

				} else {
					$this->endSession();
				}
			}
		}

		// Authenticate failed override
		$this->doAction('authenticate-failed', $args['username']);

		// Authenticate failed
		$msg = $this->applyFilter('authenticate-error-message', 'Authentication failed');
		$msg = $this->translate($msg);
		$this->setResponse($msg, [], 'error', 401);
	}
}
