<?php
/**
 * @author     : JIHAD SINNAOUR
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.0.0
 * @category   : PHP framework
 * @copyright  : (c) 2017 - 2022 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://www.floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Classes\{
	Filesystem\Stringify, 
    Http\Session, Http\Server, Http\Request, Http\Response
};
use FloatPHP\Helpers\Filesystem\Transient;

class BaseController extends View
{
	use TraitException;
	
	/**
	 * @access public
	 * @param void
	 * @return bool
	 */
	public function isAuthenticated() : bool
	{
		return $this->isLoggedIn();
	}

	/**
	 * Check current ip access
	 *
	 * @access public
	 * @param void
	 * @return bool
	 */
	public function hasAccess() : bool
	{
		$ip = Server::getIP();
		$access = false;

		// Allow local access
		if ( Stringify::contains(['127.0.0.1','::1'],$ip) ) {
			$access = true;

		} else {

			// Check allowed IPs
			$allowed = $this->applyFilter('access-allowed-ip',$this->getAllowedAccess());
			if ( !empty($allowed) ) {
				if ( Stringify::contains($allowed,$ip) ) {
					$access = true;
				} else {
					$access = false;
				}
				
			} else {
				// Deny access
				$denied = $this->applyFilter('access-denied-ip',$this->getDeniedAccess());
				if ( Stringify::contains($denied,$ip) ) {
					$access = false;
				} else {
					$access = true;
				}
			}
		}

		$data = ['ip' => $ip,'access' => $access];
		$this->doAction('ip-access',$data);
		return $access;
	}

	/**
	 * @access protected
	 * @param string $js
	 * @param string $hook
	 * @return void
	 */
	protected function addJS($js, $hook = 'add-js')
	{
		$this->addAction($hook, function() use($js) {
			$tpl = $this->applyFilter('view-js','system/js');
			$this->render(['js' => $js],$tpl);
		});
	}

	/**
	 * @access protected
	 * @param string $css
	 * @param string $hook
	 * @return void
	 */
	protected function addCSS($css, $hook = 'add-css')
	{
		$this->addAction($hook, function() use($css){
			$tpl = $this->applyFilter('view-css','system/css');
			$this->render(['css' => $css],$tpl);
		});
	}

	/**
	 * @access public
	 * @param string $url
	 * @param int $code
	 * @param string $message
	 * @return void
	 */
	public function redirect($url = '/', $code = 301, $message = 'Moved Permanently')
	{
		Server::redirect($url,$code,$message);
	}

    /**
     * @access protected
     * @param string $token
     * @param string $action
     * @param bool
     */
	protected function verifyToken($token = '', $source = '')
	{
		if ( !empty($token) ) {

			$transient = new Transient();
			$data = Stringify::unserialize($transient->getTemp($token));

			// Override
			$this->doAction('verify-token',$data);

			// Verify token data
			if ( $data ) {

				if ( $this->isLoggedIn() ) {
					if ( isset($data['user']) ) {
						if ( Session::get($this->getSessionId()) !== $data['user'] ) {
							return false;
						}
					}
				}
				if ( $source !== $data['source'] ) {
					return false;
				}
				if ( Server::getIp() !== $data['ip'] ) {
					return false;
				}
				if ( Server::get('http-referer') !== $data['url'] ) {
					return false;
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * @access protected
	 * @param bool $force
	 * @return void
	 */
	protected function verifyRequest($force = false)
	{
		$token  = $this->applyFilter('verify-request-token','--token');
		$source = $this->applyFilter('verify-request-source','--source');
		$ignore = $this->applyFilter('verify-request-ignore','--ignore');

		if ( $force ) {
			if ( !Request::isSetted($token) ) {
				$msg = $this->applyFilter('invalid-request-signature','Invalid request signature');
				$msg = $this->translate($msg);
				$this->setResponse($msg,[],'error',401);
			}
		}

		if ( Request::isSetted($token) ) {
			$source = Request::isSetted($source) ? Request::get($source) : '';
			if ( !$this->verifyToken(Request::get($token),$source) ) {
				$msg = $this->applyFilter('invalid-request-token','Invalid request token');
				$msg = $this->translate($msg);
				$this->setResponse($msg,[],'error',401);
			}
		}
		if ( Request::isSetted($ignore) && !empty(Request::get($ignore)) ) {
			$msg = $this->applyFilter('invalid-request-data','Invalid request data');
			$msg = $this->translate($msg);
			$this->setResponse($msg,[],'error',401);
		}
	}

	/**
	 * @access protected
	 * @param bool $verify
	 * @return mixed
	 */
	protected function sanitizeRequest($verify = true, $force = false)
	{
		if ( $verify ) {
			$this->verifyRequest($force);
		}
		$request = Request::get();
		$excepts = $this->applyFilter('sanitize-request',[
			'submit',
			'--token',
			'--source',
			'--ignore'
		]);
		foreach ($excepts as $except) {
			if ( isset($request[$except]) ) {
				unset($request[$except]);
			}
		}
		return $request;
	}

	/**
	 * @access protected
	 * @param string $message
	 * @param array $content
	 * @param string $status
	 * @param int $code
	 * @return void
	 */
	protected function setResponse($message = '', $content = [], $status = 'success', $code = 200)
	{
		Response::set($this->translate($message),$content,$status,$code);
	}

	/**
	 * @access protected
	 * @param string $message
	 * @param array $content
	 * @param string $status
	 * @param int $code
	 * @return void
	 */
	protected function setHttpResponse($message = '', $content = [], $status = 'success', $code = 200)
	{
		Response::set($message,$content,$status,$code);
	}
}
