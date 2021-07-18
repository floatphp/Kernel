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

use FloatPHP\Helpers\Filesystem\Cache;
use FloatPHP\Helpers\Filesystem\Transient;
use FloatPHP\Classes\Http\Server;
use FloatPHP\Classes\Http\Request;
use FloatPHP\Classes\Http\Response;
use FloatPHP\Classes\Http\Session;
use FloatPHP\Classes\Html\Shortcode;
use FloatPHP\Classes\Security\Tokenizer;
use FloatPHP\Classes\Filesystem\Translation;
use FloatPHP\Classes\Filesystem\Arrayify;
use FloatPHP\Classes\Filesystem\Stringify;

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

		// Translation cache id
		$length = strlen($string);
		$lang = $this->getLanguage();
		$uppercases = Stringify::matchAll('/([A-Z])/',$string);
		$translateId = '';
		foreach ($uppercases as $uppercase) {
			$translateId = Stringify::replace($uppercase,"{$uppercase}-1",$string);
		}
		if ( empty($translateId) ) {
			$translateId = $string;
		}
		$translateId = Stringify::slugify("translation-{$lang}-{$length}-{$translateId}");
		$translation = $cache->get($translateId);
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
}
