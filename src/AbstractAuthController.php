<?php
/**
 * @author     : JIHAD SINNAOUR
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.0.1
 * @category   : PHP framework
 * @copyright  : (c) 2017 - 2023 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://www.floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Interfaces\Kernel\AuthenticationInterface;
use FloatPHP\Classes\{
    Http\Session, Http\Request,
    Security\Password,
    Filesystem\Arrayify
};

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
	 * @param array $args
	 * @return void
	 */
	protected function authenticate(AuthenticationInterface $auth, $args = [])
	{
		// Security
		$this->verifyRequest(true);

		// Get authentication
		$args = Arrayify::merge([
			'username' => false,
			'password' => false
		],$args);

		if ( !$args['username'] ) {
			$args['username'] = Request::get('username');
		}
		if ( !$args['password'] ) {
			$args['password'] = Request::get('password');
		}

		// Authenticate override
		$this->doAction('authenticate',$args['username']);

		// Verify authentication
		if ( ($user = $auth->getUser($args['username'])) ) {

			// Check password
			if ( Password::isValid($args['password'],$user['password']) ) {

				// Check password format
				if ( $this->applyFilter('authenticate-strong-password',false) ) {
					if ( !Password::isStrong($args['password']) ) {
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

					if ( $auth->hasSecret($args['username']) ) {
						Session::set('--verify',$args['username']);
						// Authenticate accepted response
						$msg = $this->applyFilter('authenticate-accepted-message','Accepted');
						$msg = $this->translate($msg);
						$this->setResponse($msg,[],'accepted',202);

					} else {
						Session::set($auth->getKey(),$user[$auth->getKey()]);
						// Authenticate success response
						$msg = $this->applyFilter('authenticate-success-message','Connected');
						$msg = $this->translate($msg);
						$this->setResponse($msg);
					}

				} else {
					Session::end();
				}
			}
		}

		// Authenticate failed override
		$this->doAction('authenticate-failed',$args['username']);

		// Authenticate failed response
		$msg = $this->applyFilter('authenticate-error-message','Authentication failed');
		$msg = $this->translate($msg);
		$this->setResponse($msg,[],'error',401);
	}
}
