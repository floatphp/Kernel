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

use FloatPHP\Helpers\Filesystem\Translator;

class Base
{
	use TraitConfiguration,
		TraitException,
		\FloatPHP\Helpers\Framework\tr\TraitHookable,
		\FloatPHP\Helpers\Framework\tr\TraitPermissionable,
		\FloatPHP\Helpers\Framework\tr\TraitRequestable,
		\FloatPHP\Helpers\Framework\tr\TraitThrowable,
		\FloatPHP\Helpers\Framework\tr\TraitAuthenticatable;

	/**
	 * Get token (CSRF).
	 *
	 * @access protected
	 * @param string $action
	 * @return string
	 */
	protected function getToken(?string $action = null) : string
	{
		// Set filtered token data
		$data = $this->applyFilter('token-data', []);

		// Apply default data
		$data = $this->mergeArray([
			'action' => (string)$action,
			'url'    => $this->getServerCurrentUrl(),
			'ip'     => $this->getServerIp(),
			'user'   => false
		], $data);

		$this->startSession();

		// Set authenticated user data
		if ( $this->isAuthenticated() ) {
			$data['user'] = $this->getSession(
				$this->getSessionId()
			);
		}

		// Generate session token from data
		$token = $this->generateHash($data);

		// Get session token data
		$session = $this->getSession('--token') ?: [];

		// Set session token data
		if ( !isset($session[$token]) ) {
			$session[$token] = $data;
			$this->setSession('--token', $session);
		}

		$this->closeSession();

		return $token;
	}

	/**
	 * Get language.
	 *
	 * @access public
	 * @return string
	 */
	public function getLanguage() : string
	{
		if ( $this->hasRequest('--lang') ) {
			return $this->getRequest('--lang');
		}
		if ( !($lang = $this->getSession('--lang')) ) {
			$lang = $this->getSession('--default-lang');
		}
		return (string)$lang;
	}

	/**
	 * Translate string,
	 * May require quotes escaping.
	 *
	 * @access public
	 * @param string $string
	 * @return string
	 */
	public function translate(string $string) : string
	{
		if ( $string ) {
			$lang = $this->getLanguage();
			return (new Translator($lang))->translate($string);
		}

		return $string;
	}

	/**
	 * Translate array of strings.
	 *
	 * @access public
	 * @param array $strings
	 * @return array
	 */
	public function translateArray(array $strings = []) : array
	{
		foreach ($strings as $key => $value) {
			$strings[$key] = $this->translate($value);
		}
		return $strings;
	}

	/**
	 * Translate string with variables,
	 * May require quotes escaping.
	 *
	 * @access public
	 * @param string $string
	 * @param mixed $vars
	 * @return string
	 */
	public function translateVar(string $string, $vars = null) : string
	{
		if ( $this->isType('array', $vars) ) {
			return vsprintf($this->translate($string), $vars);
		}
		if ( $this->isType('string', $vars) ) {
			$vars = $this->replaceString('/\s+/', $this->translate('{Empty}'), $vars, true);
			$string = $this->replaceString($vars, '%s', $string);
			return sprintf($this->translate($string), $vars);
		}
		return $string;
	}

	/**
	 * Translate deep strings.
	 *
	 * @access protected
	 * @param array $strings
	 * @return array
	 */
	protected function translateDeepStrings(array $strings) : array
	{
		$this->recursiveArray($strings, function (&$string) : void {
			if ( $this->isType('string', $string) ) {
				$string = $this->translate($string);
			}
		});
		return $strings;
	}

	/**
	 * Load translated strings.
	 *
	 * @access protected
	 * @param string $type
	 * @return array
	 */
	protected function loadStrings(string $type = 'admin') : array
	{
		$strings = $this->getStrings();
		switch ($type) {
			case 'admin':
				return $this->translateDeepStrings(
					$strings['admin']
				);
				break;

			case 'front':
				return $this->translateDeepStrings(
					$strings['front']
				);
				break;
		}
		return $this->translateDeepStrings($strings);
	}
}
