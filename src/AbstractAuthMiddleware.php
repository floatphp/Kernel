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
use FloatPHP\Classes\Auth\Session;
use FloatPHP\Classes\Http\Response;
Use FloatPHP\Classes\Security\Tokenizer;

abstract class AbstractAuthMiddleware extends BackendController
{
	/**
	 * @access public
	 * @param void
	 * @return void
	 */
	abstract public function login();

	/**
	 * @access protected
	 * @param AuthenticationInterface $auth
	 * @param string $username
	 * @param string $password
	 * @return void
	 */
	protected function authenticate(AuthenticationInterface $auth, $username, $password)
	{
		new Session();
		if ( ($user = $auth->getUser($username)) ) {
			if ( Tokenizer::isValidPassword($password, $user['password']) ) {
				Session::register($this->getAccessExpire());
				if ( $this->isLoggedIn() ) {
					Session::set($auth->getKey(), $user[$auth->getKey()]);
					Response::set('Connected');
				} else {
					Session::end();
				}
			}
		}
		Response::set('Invalid authentication data', [], 'error');
	}
}
