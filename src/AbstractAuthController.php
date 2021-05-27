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

use FloatPHP\Interfaces\Kernel\AuthenticationInterface;
Use FloatPHP\Classes\Security\Password;
use FloatPHP\Classes\Http\Session;
use FloatPHP\Helpers\Transient;

abstract class AbstractAuthController extends BaseController
{
	/**
	 * @access public
	 * @param void
	 * @return void
	 */
	abstract public function login();

	/**
	 * @access public
	 * @param void
	 * @return bool
	 */
	public function isAuthenticated() : bool
	{
		if ( Session::isSetted($this->getSessionId()) ) {
			return $this->isLoggedIn();
		}
		return false;
	}

	/**
	 * @access protected
	 * @param AuthenticationInterface $auth
	 * @param string $username
	 * @param string $password
	 * @return void
	 */
	protected function authenticate(AuthenticationInterface $auth, $username, $password)
	{
		// Security
		$this->verifyRequest();

		// Authenticate override
		$this->doAction('authenticate',$username);

		// Authenticate
		new Session();
		if ( ($user = $auth->getUser($username)) ) {

			// Check password
			if ( Password::isValid($password,$user['password']) ) {

				// Check password format
				if ( $this->applyFilter('authenticate-strong-password',false) ) {
					if ( !Password::isStrong($password) ) {
						// Authenticate failed response
						$msg = $this->applyFilter('authenticate-password-message','Strong password required');
						$msg = $this->translate($msg);
						$this->setResponse($msg,[],'warning');
					}
				}

				// Register session
				Session::register($this->getAccessExpire());

				// Check session registred
				if ( $this->isLoggedIn() ) {

					Session::set($auth->getKey(),$user[$auth->getKey()]);
					// Authenticate success response
					$msg = $this->applyFilter('authenticate-success-message','Connected');
					$msg = $this->translate($msg);
					$this->setResponse($msg);

				} else {
					Session::end();
				}
			}
		}

		// Authenticate failed override
		$this->doAction('authenticate-failed',$username);

		// Authenticate failed response
		$msg = $this->applyFilter('authenticate-error-message','Authentication failed');
		$msg = $this->translate($msg);
		$this->setResponse($msg,[],'error',401);
	}

	/**
	 * @access protected
	 * @param void
	 * @return void
	 */
	protected function useStrongPassword()
	{
		$this->addFilter('authenticate-strong-password',function(){
			return true;
		});
	}

	/**
	 * @access protected
	 * @param int $max
	 * @return void
	 */
	protected function limitAttempts($max = 3)
	{
		// Log failed authentication
		$this->addAction('authenticate-failed',function($username){
			if ( !empty($username) ) {
				$transient = new Transient();
				$key = "authenticate-{$username}";
				if ( !($attempt = $transient->getTemp($key)) ) {
					$transient->setTemp($key,1,0);
				} else {
					$transient->setTemp($key,$attempt + 1,0);
				}
			}
		});

		// Apply attempts limit
		$this->addAction('authenticate',function($username) use ($max) {
			if ( !empty($username) ) {
				$key = "authenticate-{$username}";
				$transient = new Transient();
				$attempt = $transient->getTemp($key);
				if ( $attempt >= (int)$max ) {
					$msg = $this->applyFilter('authenticate-attempt-message','Access forbidden');
					$msg = $this->translate($msg);
					$this->setResponse($msg,[],'error',401);
				}
			}
		});
	}
}
