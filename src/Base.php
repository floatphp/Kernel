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

use FloatPHP\Classes\Html\Hook;
use FloatPHP\Classes\Http\Server;
use FloatPHP\Classes\Http\Session;
use FloatPHP\Classes\Http\Request;
use FloatPHP\Classes\Http\Response;
use FloatPHP\Classes\Filesystem\Arrayify;
use FloatPHP\Classes\Filesystem\Stringify;
use FloatPHP\Classes\Filesystem\Translation;
use FloatPHP\Classes\Security\Tokenizer;
use FloatPHP\Helpers\Logger;
use FloatPHP\Helpers\Cache;
use FloatPHP\Helpers\Transient;

class Base
{
	use TraitConfiguration;

	/**
	 * @param void
	 */
	public function __construct()
	{
		// Init configuration
		$this->initConfig();
	}
	
	/**
	 * Prevent object clone
	 *
	 * @param void
	 */
    public function __clone()
    {
        die(__METHOD__.': Clone denied');
    }

	/**
	 * Prevent object serialization
	 *
	 * @param void
	 */
    public function __wakeup()
    {
        die(__METHOD__.': Unserialize denied');
    }

	/**
	 * Get static instance
	 *
	 * @access protected
	 * @param void
	 * @return object
	 */
	protected static function getStatic()
	{
		return new static;
	}

	/**
	 * Hook a method on a specific action
	 *
	 * @access protected
	 * @param string $hook
	 * @param callable $method
	 * @param int $priority
	 * @param int $args
	 * @return true
	 */
	protected function addAction($hook, $method, $priority = 10, $args = 1)
	{
		return Hook::getInstance()->addAction($hook,$method,$priority,$args);
	}

	/**
	 * Remove a method from a specified action hook
	 *
	 * @access protected
	 * @param string $hook
	 * @param callable $method
	 * @param int $priority
	 * @return bool
	 */
	protected function removeAction($hook, $method, $priority = 10)
	{
		return Hook::getInstance()->removeAction($hook,$method,$priority);
	}

	/**
	 * Add a method from a specified action hook
	 *
	 * @access protected
	 * @param string $tag
	 * @param mixed $args
	 * @return true
	 */
	protected function doAction($tag, $args = null)
	{
		return Hook::getInstance()->doAction($tag,$args);
	}

	/**
	 * Check if any filter has been registered for action
	 *
	 * @access protected
	 * @param string $tag
	 * @param mixed $args
	 * @return bool
	 */
	protected function hasAction($tag, $args = null)
	{
		return Hook::getInstance()->hasAction($tag,$args);
	}

	/**
	 * Hook a function or method to a specific filter action
	 *
	 * @access protected
	 * @param string $hook
	 * @param callable $method
	 * @param int $priority
	 * @param int $args
	 * @return true
	 */
	protected function addFilter($hook, $method, $priority = 10, $args = 1)
	{
		return Hook::getInstance()->addFilter($hook,$method,$priority,$args);
	}

	/**
	 * Remove a function from a specified filter hook
	 *
	 * @access protected
	 * @param string $hook
	 * @param callable $method
	 * @param int $priority
	 * @return bool
	 */
	protected function removeFilter($hook, $method, $priority = 10) : bool
	{
		return Hook::getInstance()->removeFilter($hook,$method,$priority);
	}

	/**
	 * Calls the callback functions 
	 * that have been added to a filter hook
	 *
	 * @access protected
	 * @param string $hook
	 * @param mixed $value
	 * @param mixed $args
	 * @return mixed
	 */
	protected function applyFilter($hook, $value, $args = null)
	{
		return Hook::getInstance()->applyFilter($hook,$value,$args);
	}

	/**
	 * Check if any filter has been registered for filter
	 *
	 * @access protected
	 * @param string $hook
	 * @param callable $method
	 * @return bool
	 */
	protected function hasFilter($hook, $method = false) : bool
	{
		return Hook::getInstance()->hasFilter($hook,$method);
	}

    /**
     * @access protected
     * @param string $action
     * @return mixed
     */
	protected function getToken($action = '')
	{
		// Init token data
		$data = $this->applyFilter('token-data',[]);

		// Set default token data
		$data['action'] = $action;
		$data['url'] = Server::getCurrentUrl();
		$data['ip'] = Server::getIp();

		// Set user token data
		if ( $this->isLoggedIn() ) {
			$data['user'] = Session::get($this->getSessionId());
		}

		// Save token
		$data = Stringify::serialize($data);
		$token = Tokenizer::generate(10);
		$transient = new Transient();
		$transient->setTemp($token,$data,$this->getAccessExpire());
		return $token;
	}

    /**
     * @access protected
     * @param string $token
     * @param string $action
     * @param bool
     */
	protected function verifyToken($token = '', $action = '')
	{
		if ( !empty($token) ) {

			$transient = new Transient();
			$data = Stringify::unserialize($transient->getTemp($token));

			// Override
			$this->doAction('verify-token',$data);

			// Verify token data
			if ( $data ) {

				if ( $this->isLoggedIn() ) {
					if ( Session::get($this->getSessionId()) !== $data['user'] ) {
						return false;
					}
				} elseif ( $action !== $data['action'] ) {
					return false;

				} elseif ( Server::getIp() !== $data['ip'] ) {
					return false;

				} elseif ( Server::get('http-referer') !== $data['url'] ) {
					return false;

				} else {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @access protected
	 * @param void
	 * @return bool
	 */
	protected function isLoggedIn() : bool
	{
		if ( Session::isRegistered() && !Session::isExpired() ) {
			return true;
		}
		return false;
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
	 * @param void
	 * @return string
	 */
	protected function getLanguage()
	{
		if ( Arrayify::hasKey('lang',Request::get()) ) {
			$lang = Request::get('lang');
		    Session::set('--lang',$lang);

		} elseif ( Arrayify::hasKey('lang',Session::get()) && $this->isLoggedIn() ) {
		    $lang = Session::get('--lang');

		} else {
		    $lang = Session::get('--default-lang');
		}
		return $this->applyFilter('--default-lang',$lang);
	}

	/**
	 * @access protected
	 * @param string $string
	 * @return string
	 */
	protected function translate($string = '') : string
	{
		// Set cache filters
		$path = $this->applyFilter('translation-cache-path','translate');
		$ttl = $this->applyFilter('translation-cache-ttl',3600);

		// Cache translation
		$cache = new Cache($path,$ttl);

		$length = strlen($string);
		$lang = $this->getLanguage();
		$id = Stringify::slugify("translation-{$lang}-{$length}-{$string}");
		$translation = $cache->get($id);
		if ( !$cache->isCached() ) {
			$path = $this->applyFilter('translation-path',$this->getTranslatePath());
			$translator = new Translation($lang,$path);
			$translation = $translator->translate($string);
			$cache->set($translation,'translation');
		}
		return ($translation) ? $translation : (string)$string;
	}

	/**
	 * @access protected
	 * @param string $string
	 * @param string $vars
	 * @return string
	 */
	protected function translateVars($string,...$vars) : string
	{
		return sprintf($this->translate($string),$vars);
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
		Response::set($message,$content,$status,$code);
	}

	/**
	 * @access protected
	 * @param void
	 * @return void
	 */
	protected function verifyRequest()
	{
		$token  = $this->applyFilter('verify-request-token','--token');
		$action = $this->applyFilter('verify-request-action','--action');
		$ignore = $this->applyFilter('verify-request-ignore','--ignore');

		if ( Request::isSetted($token) ) {
			$action = Request::isSetted($action) ? Request::get($action) : '';
			if ( !$this->verifyToken(Request::get($token),$action) ) {
				$msg = $this->applyFilter('invalid-request-token','Invalid request token');
				$msg = $this->translate($msg);
				$this->setResponse($msg,[],'error',401);
			}

		} elseif ( Request::isSetted($ignore) && !empty(Request::get($ignore)) ) {
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
	protected function sanitizeRequest($verify = true)
	{
		if ( $verify ) {
			$this->verifyRequest();
		}
		$request = Request::get();
		$excepts = $this->applyFilter('sanitize-request',[
			'submit',
			'--token',
			'--action',
			'--ignore'
		]);
		foreach ($excepts as $except) {
			if ( isset($request[$except]) ) {
				unset($request[$except]);
			}
		}
		return $request;
	}
}
