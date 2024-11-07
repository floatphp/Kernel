<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.2.x
 * @copyright  : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Classes\Http\Response;
use FloatPHP\Helpers\Connection\Transient;

class BaseController extends View
{
	/**
	 * redirectIndex : [GET] /index.php
	 *
	 * @access public
	 * @return void
	 */
	public function redirectIndex()
	{
		$this->redirect();
	}

	/**
	 * Check whether user has access.
	 *
	 * @access public
	 * @return bool
	 */
	public function hasAccess() : bool
	{
		$ip = $this->getServerIp();
		$access = false;

		// Allow local access
		if ( $this->searchString(['127.0.0.1', '::1'], $ip) ) {
			$access = true;

		} else {

			// Check allowed IPs
			$allowed = $this->applyFilter('access-allowed-ip', $this->getAllowedAccess());
			if ( !empty($allowed) ) {
				if ( $this->searchString($allowed, $ip) ) {
					$access = true;

				} else {
					$access = false;
				}
				
			} else {
				// Deny access
				$denied = $this->applyFilter('access-denied-ip', $this->getDeniedAccess());
				if ( $this->searchString($denied, $ip) ) {
					$access = false;

				} else {
					$access = true;
				}
			}
		}

		$data = ['ip' => $ip, 'access' => $access];
		$this->doAction('ip-access', $data);
		return $access;
	}

	/**
	 * Add JS hook.
	 * 
	 * @access protected
	 * @param string $js
	 * @param string $hook
	 * @return void
	 */
	protected function addJS(string $js, string $hook = 'add-js')
	{
		$this->addAction($hook, function() use($js) {
			$file = $this->applyFilter('view-js', 'system/js');
			$this->render($file, ['js' => $js]);
		});
	}

	/**
	 * Add CSS hook.
	 * 
	 * @access protected
	 * @param string $css
	 * @param string $hook
	 * @return void
	 */
	protected function addCSS(string $css, string $hook = 'add-css')
	{
		$this->addAction($hook, function() use($css){
			$file = $this->applyFilter('view-css', 'system/css');
			$this->render($file, ['css' => $css]);
		});
	}

    /**
	 * Verify token against request data.
	 * 
     * @access protected
     * @param string $token
     * @param string $action
     * @param bool
     */
	protected function verifyToken(string $token = '', string $source = '')
	{
		if ( !empty($token) ) {

			$transient = new Transient();
			$data = (string)$transient->getTemp($token);
			$data = $this->unserialize($data);

			// Override
			$this->doAction('verify-token', $data);

			// Verify token data
			if ( $data ) {
				if ( $this->isValidSession() ) {
					if ( isset($data['user']) ) {
						if ( $this->getSession($this->getSessionId()) !== $data['user'] ) {
							return false;
						}
					}
				}
				if ( $source !== $data['source'] ) {
					return false;
				}
				if ( $this->getServerIp() !== $data['ip'] ) {
					return false;
				}
				if ( $this->getServer('http-referer') !== $data['url'] ) {
					return false;
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Verify current request.
	 * 
	 * @access protected
	 * @param bool $force Token validation
	 * @return void
	 */
	protected function verifyRequest(bool $force = false)
	{
		$token  = $this->applyFilter('verify-request-token', '--token');
		$source = $this->applyFilter('verify-request-source', '--source');
		$ignore = $this->applyFilter('verify-request-ignore', '--ignore');

		if ( $force ) {
			if ( !$this->hasRequest($token) ) {
				$msg = $this->applyFilter('invalid-request-signature', 'Invalid request signature');
				$msg = $this->translate($msg);
				$this->setResponse($msg, [], 'error', 401);
			}
		}

		if ( $this->hasRequest($token) ) {
			$source = $this->hasRequest($source) ? $this->getRequest($source) : '';
			if ( !$this->verifyToken($this->getRequest($token), $source) ) {
				$msg = $this->applyFilter('invalid-request-token', 'Invalid request token');
				$msg = $this->translate($msg);
				$this->setResponse($msg, [], 'error', 401);
			}
		}

		if ( $this->hasRequest($ignore) && !empty($this->getRequest($ignore)) ) {
			$msg = $this->applyFilter('invalid-request-data', 'Invalid request data');
			$msg = $this->translate($msg);
			$this->setResponse($msg, [], 'error', 401);
		}
	}

	/**
	 * Sanitize current request.
	 * 
	 * @access protected
	 * @param bool $verify Request
	 * @param bool $force Token validation
	 * @return mixed
	 */
	protected function sanitizeRequest(bool $verify = true, bool $force = false)
	{
		if ( $verify ) $this->verifyRequest($force);

		$request = $this->getRequest();
		$excepts = $this->applyFilter('sanitize-request', [
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
	 * Set HTTP response (Translated).
	 * 
	 * @access protected
	 * @param string $msg
	 * @param mixed $content
	 * @param string $status
	 * @param int $code
	 * @return void
	 */
	protected function setResponse(string $msg = '', $content = [], string $status = 'success', int $code = 200)
	{
		$msg = $this->translate($msg);
		Response::set($msg, $content, $status, $code);
	}

	/**
	 * Set HTTP response.
	 * 
	 * @access protected
	 * @param string $msg
	 * @param mixed $content
	 * @param string $status
	 * @param int $code
	 * @return void
	 */
	protected function setHttpResponse(string $msg = '', $content = [], string $status = 'success', int $code = 200)
	{
		Response::set($msg, $content, $status, $code);
	}
}
