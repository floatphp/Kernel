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

use FloatPHP\Helpers\Connection\Transient;
use FloatPHP\Helpers\Filesystem\{
	Cache, Translator
};

class Base
{
	use TraitConfiguration,
		TraitException,
		\FloatPHP\Helpers\Framework\inc\TraitHookable,
		\FloatPHP\Helpers\Framework\inc\TraitPermissionable,
		\FloatPHP\Helpers\Framework\inc\TraitRequestable,
		\FloatPHP\Helpers\Framework\inc\TraitThrowable,
		\FloatPHP\Helpers\Framework\inc\TraitAuthenticatable;

    /**
	 * Get token.
	 * 
     * @access protected
     * @param string $source
     * @return string
     */
	protected function getToken(?string $source = null) : string
	{
		// Init token data
		$data = $this->applyFilter('token-data', []);

		// Set default token data
		$data['source'] = (string)$source;
		$data['url'] = $this->getServerCurrentUrl();
		$data['ip'] = $this->getServerIp();

		// Set user token data
		if ( $this->isAuthenticated() ) {
			$data['user'] = $this->getSession($this->getSessionId());
		}

		// Save token
		$data = $this->serialize($data);
		$token = $this->generateToken(10);
		$transient = new Transient();
		$transient->setTemp($token, $data, $this->getAccessExpire());

		return $token;
	}

	/**
	 * Get language.
	 * 
	 * @access protected
	 * @return string
	 */
	protected function getLanguage() : string
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
	 * Translate string, May require quotes escaping.
	 *
	 * @access protected
	 * @param string $string
	 * @return string
	 */
	protected function translate(string $string) : string
	{
		if ( !($length = strlen($string)) ) {
			return $string;
		}

		$slug = $string;
		$this->matchEveryString('/([A-Z])/', $slug, $matches, -1);

		foreach ($matches as $upper) {
			$slug = $this->replaceString($upper, "{$upper}1-", $slug);
		}

		$slug = $this->slugify($slug);
		$slug = $this->limitString($slug);

		$cache = new Cache();
		$lang  = $this->getLanguage();
		$key   = "i18n-{$lang}-{$length}-{$slug}";

		$data = $cache->get($key, $status);
		if ( !$status ) {
			$translator = new Translator($lang);
			$data = $translator->translate($string);
			$cache->set($key, $data, 0);
		}

		return (string)$data;
	}

	/**
	 * Translate array of strings.
	 *
     * @access protected
     * @param array $strings
     * @return array
     */
    protected function translateArray(array $strings = []) : array
    {
        foreach ($strings as $key => $value) {
            $strings[$key] = $this->translate($value);
        }
        return $strings;
    }

	/**
	 * Translate string with variables.
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
	protected function translateDeepStrings(array $strings)
	{
		$this->recursiveArray($strings, function(&$string) {
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
