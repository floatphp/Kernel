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

use FloatPHP\Classes\Http\Session;
use FloatPHP\Classes\Http\Request;
use FloatPHP\Classes\Http\Server;
use FloatPHP\Classes\Http\Response;
use FloatPHP\Classes\Http\Post;
use FloatPHP\Classes\Html\Hook;
use FloatPHP\Classes\Html\Shortcode;
use FloatPHP\Classes\Filesystem\Translation;
use FloatPHP\Classes\Filesystem\Stringify;
use FloatPHP\Classes\Security\Tokenizer;
use FloatPHP\Helpers\Transient;

class BaseOptions
{
	use Configuration;

	/**
	 * @param void
	 */
	public function __construct()
	{
		// Init configuration
		$this->initConfig();
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
     * @access protected
     * @param string $source
     * @return mixed
     */
	protected function getToken($source = '')
	{
		$token = false;
		if ( $this->isLoggedIn() ) {
			$token = Tokenizer::generate(10);
			$transient = new Transient();
			$transient->setTemp($token,Stringify::serialize([
				'user'   => Session::get($this->getSessionId()),
				'source' => $source
			]), $this->getAccessExpire());
		}
		return $token;
	}

    /**
     * @access protected
     * @param string $token
     * @param string $source
     * @param bool
     */
	protected function verifyToken($token = '', $source = '')
	{
		if ( !empty($token) ) {
			$transient = new Transient();
			$data = Stringify::unserialize($transient->getTemp($token));
			if ( $data ) {
				if ( Session::get($this->getSessionId()) !== $data['user'] ) {
					return false;
				} elseif ( $source !== $data['source'] ) {
					return false;
				} else {
					return true;
				}
			}
		}
		return false;
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
	 * Register a shortcode handler
	 *
	 * @access protected
	 * @param string $tag
	 * @param callable $callback
	 * @return void
	 */
	protected function addShortcode($tag, $callback)
	{
		return Shortcode::getInstance()->addShortcode($tag,$callback);
	}

	/**
	 * Search content for shortcodes 
	 * and filter shortcodes through their hooks
	 *
	 * @access protected
	 * @param string $content
	 * @param bool $ignoreHTML
	 * @return void
	 */
	protected function renderShortcode($content, $ignoreHTML = false)
	{
		echo $this->doShortcode($content,$ignoreHTML);
	}

	/**
	 * Search content for shortcodes 
	 * and filter shortcodes through their hooks
	 *
	 * @access protected
	 * @param string $content
	 * @param bool $ignoreHTML
	 * @return string
	 */
	protected function doShortcode($content, $ignoreHTML = false)
	{
		return Shortcode::getInstance()->doShortcode($content,$ignoreHTML);
	}

	/**
	 * Removes hook for shortcode
	 *
	 * @access protected
	 * @param string $tag
	 * @return bool
	 */
	protected function removeShortcode($tag)
	{
		return Shortcode::getInstance()->removeShortcode($tag);
	}

	/**
	 * Checks Whether a registered shortcode exists named $tag
	 *
	 * @access protected
	 * @param string $tag
	 * @return bool
	 */
	protected function shortcodeExists($tag)
	{
		return Shortcode::getInstance()->shortcodeExists($tag);
	}

	/**
	 * Checks Whether a registered shortcode exists named $tag
	 *
	 * @access protected
	 * @param string $tag
	 * @return bool
	 */
	protected function hasShortcode($tag)
	{
		return Shortcode::getInstance()->hasShortcode($tag);
	}

	/**
	 * @access protected
	 * @param int $code
	 * @param string $message
	 * @return object
	 */
	protected function exception($code = 404, $message = '')
	{
		return new ErrorController($code,$message);
	}

	/**
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getLanguage()
	{
		if ( array_key_exists('lang',Request::get()) ) {
			$lang = Request::get('lang');
		    Session::set('--lang',$lang);

		} elseif ( array_key_exists('lang',Session::get()) && $this->isLoggedIn() ) {
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
	protected function translate($string) : string
	{
		// Set cache filters
		$path = $this->applyFilter('translation-cache-path',$this->getTranslateCachePath());
		$ttl = $this->applyFilter('translation-cache-ttl',3600);

		// Cache translation
		Cache::setConfig(['path' => $path]);
		Cache::expireIn($ttl);
		$cache = new Cache();

		$length = strlen($string);
		$lang = $this->getLanguage();
		$id = "translation-{$lang}-{$length}-{$string}";
		$translation = $cache->get($id);
		if ( !$cache->isCached() ) {
			$path = $this->applyFilter('translation-path',$this->getTranslatePath());
			$translator = new Translation($lang,$path);
			$translation = $translator->translate($string);
			$cache->set($translation,'translation');
		}
		return ($translation) ? $translation : $string;
	}

	/**
	 * @access protected
	 * @param string $string
	 * @param string $vars
	 * @return string
	 */
	protected function translateVars($string,...$vars) : string
	{
		return sprintf($this->translate($string), $vars);
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
		$token  = $this->applyFilter('sanitize-request-token','--token');
		$source = $this->applyFilter('sanitize-request-source','--source');
		$ignore = $this->applyFilter('sanitize-request-ignore','--ignore');

		if ( Request::isSetted($token) ) {
			$source = Request::isSetted($source) ? Request::get($source) : '';
			if ( !$this->verifyToken(Request::get($token),$source) ) {
				$this->setResponse('Invalid request token', [], 'error', 401);
			}
		}
		if ( Request::isSetted($ignore) && !empty(Request::get($ignore)) ) {
			$this->setResponse('Invalid request data', [], 'error', 401);
		}
	}

	/**
	 * @access protected
	 * @param void
	 * @return mixed
	 */
	protected function sanitizeRequest()
	{
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
}
