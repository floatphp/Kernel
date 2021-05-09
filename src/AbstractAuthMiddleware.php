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
use FloatPHP\Classes\Http\Session;
use FloatPHP\Classes\Http\Response;
Use FloatPHP\Classes\Security\Password;

abstract class AbstractAuthMiddleware extends BackendController
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
		$this->doAction('authenticate',$username);
		new Session();
		if ( ($user = $auth->getUser($username)) ) {
			if ( Password::isValid($password,$user['password']) ) {
				Session::register($this->getAccessExpire());
				if ( $this->isLoggedIn() ) {
					Session::set($auth->getKey(),$user[$auth->getKey()]);
					Response::set($this->applyFilter('authenticate-success-message','Connected'));
				} else {
					Session::end();
				}
			}
		}
		$this->doAction('authenticate-failed',$username);
		$error = $this->applyFilter('authenticate-error-message','Invalid authentication');
		Response::set($error,[],'error');
	}
}
