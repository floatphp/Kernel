<?php
/**
 * @author     : JIHAD SINNAOUR
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.0.0
 * @category   : PHP framework
 * @copyright  : (c) 2017 - 2021 JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link       : https://www.floatphp.com
 * @license    : MIT License
 *
 * This file if a part of FloatPHP Framework
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Helpers\Filesystem\Cache;
use FloatPHP\Helpers\Filesystem\Transient;
use FloatPHP\Classes\Filesystem\Arrayify;
use FloatPHP\Classes\Filesystem\Stringify;
use FloatPHP\Classes\Filesystem\Translation;
use FloatPHP\Classes\Html\Hook;
use FloatPHP\Classes\Html\Shortcode;
use FloatPHP\Classes\Http\Session;
use FloatPHP\Classes\Http\Server;
use FloatPHP\Classes\Http\Request;
use FloatPHP\Classes\Security\Tokenizer;

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
	 * @param void
	 * @return string
	 */
	protected function getLanguage()
	{
		if ( Arrayify::hasKey('lang',(array)Request::get()) ) {
			$lang = Request::get('lang');
		    Session::set('--lang',$lang);

		} elseif ( Arrayify::hasKey('lang',(array)Session::get()) && $this->isLoggedIn() ) {
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
     * @param string $source
     * @return mixed
     */
	protected function getToken($source = '')
	{
		// Init token data
		$data = $this->applyFilter('token-data',[]);

		// Set default token data
		$data['source'] = $source;
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
}
