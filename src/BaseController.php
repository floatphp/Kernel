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
 * This file is a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Classes\Http\Response;

class BaseController extends View
{
	/**
	 * redirectIndex : [GET] /index.php
	 *
	 * @access public
	 * @return void
	 */
	public function redirectIndex() : void
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

		// Allow local access
		if ( $this->isDebug() && $this->hasString(['127.0.0.1', '::1'], $ip) ) {
			return true;
		}

		// Check allowed IPs
		$access = false;
		$allowed = $this->getAllowedAccess();
		$allowed = $this->applyFilter('access-allowed-ip', $allowed);

		if ( !empty($allowed) ) {
			$access = $this->hasString($allowed, $ip);

		} else {

			// Deny access
			$denied = $this->getDeniedAccess();
			$denied = $this->applyFilter('access-denied-ip', $denied);
			$access = !$this->hasString($denied, $ip);
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
	protected function addJS(string $js, string $hook = 'add-js') : void
	{
		$this->addAction($hook, function () use ($js) {
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
	protected function addCSS(string $css, string $hook = 'add-css') : void
	{
		$this->addAction($hook, function () use ($css) {
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
	protected function verifyToken(?string $token = null, ?string $action = null) : bool
	{
		// Get token session
		$session = $this->getSession('--token') ?: [];

		// Get token data
		$data = $session[$token] ?? [];

		// Apply default data
		$data = $this->mergeArray([
			'action' => '',
			'url'    => false,
			'ip'     => false,
			'user'   => false
		], $data);

		// Override verification
		$this->doAction('verify-token', $data);

		// Verify authenticated user
		if ( $this->isAuthenticated() ) {
			$user = $this->getSession($this->getSessionId());
			if ( $user !== $data['user'] ) {
				return false;
			}
		}

		// Verify action
		if ( $action !== $data['action'] ) {
			return false;
		}

		// Verify IP
		if ( $this->getServerIp() !== $data['ip'] ) {
			return false;
		}

		// Verify URL
		if ( $this->getServer('http-referer') !== $data['url'] ) {
			return false;
		}

		return $this->verifyHash($token, $data);
	}

	/**
	 * Verify current request.
	 *
	 * @access protected
	 * @param bool $force, Token validation
	 * @return void
	 */
	protected function verifyRequest(bool $force = false) : void
	{
		$token = (string)$this->applyFilter('request-token', '--token');
		$action = (string)$this->applyFilter('request-action', '--action');
		$ignore = (string)$this->applyFilter('request-ignore', '--ignore');

		if ( $force ) {
			if ( !$this->hasRequest($token) ) {
				$msg = $this->applyFilter('invalid-signature', 'Invalid request signature');
				$msg = $this->translate($msg);
				$this->setResponse($msg, [], 'error', 401);
			}
		}

		if ( $this->hasRequest($token) ) {
			$action = $this->hasRequest($action) ? $this->getRequest($action) : '';
			if ( !$this->verifyToken($this->getRequest($token), $action) ) {
				$msg = $this->applyFilter('invalid-token', 'Invalid request token');
				$msg = $this->translate($msg);
				$this->setResponse($msg, [], 'error', 401);
			}
		}

		if ( $this->hasRequest($ignore) && !empty($this->getRequest($ignore)) ) {
			$msg = $this->applyFilter('invalid-data', 'Invalid request data');
			$msg = $this->translate($msg);
			$this->setResponse($msg, [], 'error', 401);
		}
	}

	/**
	 * Sanitize current request.
	 *
	 * @access protected
	 * @param bool $verify, Request
	 * @param bool $force, Token validation
	 * @return array
	 */
	protected function sanitizeRequest(bool $verify = true, bool $force = false) : array
	{
		$request = $this->getRequest();
		$excepts = ['PHPSESSID', 'COOKIES'];

		if ( !$force ) {
			$excepts = $this->mergeArray(['submit', '--token', '--action', '--ignore'], $excepts);
		}

		if ( $verify ) {
			$this->verifyRequest($force);
		}

		$excepts = $this->applyFilter('sanitize-request', $excepts);

		foreach ($excepts as $except) {
			if ( isset($request[$except]) ) {
				unset($request[$except]);
			}
		}

		return $request ?: [];
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
	protected function setResponse(string $msg = '', $content = [], string $status = 'success', int $code = 200) : void
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
	protected function setHttpResponse(string $msg = '', $content = [], string $status = 'success', int $code = 200) : void
	{
		Response::set($msg, $content, $status, $code);
	}
}
