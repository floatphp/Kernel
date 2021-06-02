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

use FloatPHP\Classes\Http\Server;
use FloatPHP\Classes\Html\Shortcode;
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
}
