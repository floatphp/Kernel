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

use FloatPHP\Classes\Filesystem\File;
use FloatPHP\Classes\Filesystem\Json;
use FloatPHP\Classes\Filesystem\Stringify;

trait Configuration
{
	/**
	 * @access private
	 * @var object $global
	 * @var object $global
	 * @var array $routes
	 */
	private $path = '/Storage/config/global.json';
	private $global = false;
	private $routes = [];

	/**
	 * Set Config Json File
	 * Allow Parent Config Access
	 *
	 * @access protected
	 * @param void
	 * @return void
	 */
	protected function initConfig()
	{
		// Parse Config file
		$json = new Json("{$this->getAppDir()}{$this->path}");
		$this->global = $json->parse();

		// Set routes config
		$routes = new Json("{$this->global->path->routes}");
		$this->routes = $routes->parse(1);
	}

	/**
	 * Get global
	 *
	 * @access protected
	 * @param string $var null
	 * @return mixed
	 */
	protected function getConfig($var = null)
	{
		if ( $var ) {
			return isset($this->global->$var)
			? $this->global->$var : false;
		}
		return $this->global;
	}

	/**
	 * Update Custom Options
	 *
	 * @access protected
	 * @param array $options
	 * @param int $args
	 * @return void
	 */
	protected function updateConfig($options = [], $args = 64|128|256)
	{
		$json = new Json("{$this->getRoot()}{$this->path}");
		$config = $json->parse(true);
		foreach ($options as $option => $value) {
			if ( isset($config['options'][$option]) ) {
				$config['options'][$option] = $value;
			}
		}
		$config = Json::format($config, $args);
		File::w("{$this->getRoot()}{$this->path}",$config);
	}

	/**
	 * Get static app dir (root)
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getAppDir() : string
	{
		global $appDir;
		return Stringify::formatPath($appDir);
	}

	/**
	 * Get static base routes path
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getBaseRoute() : string
	{
		return "{$this->global->path->base}";
	}

	/**
	 * Get static routes
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getRoutes() : array
	{
		return $this->routes;
	}

	/**
	 * Get static cache path
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getCachePath() : string
	{
		return "{$this->getAppDir()}{$this->global->path->cache}";
	}

	/**
	 * Get static temp path
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getTempPath() : string
	{
		return "{$this->getAppDir()}{$this->global->path->temp}";
	}

	/**
	 * Get static view path
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getViewPath() : string
	{
		return "{$this->getAppDir()}{$this->global->path->view}";
	}

	/**
	 * Get static logs path
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getLoggerPath() : string
	{
		return "{$this->getAppDir()}{$this->global->path->logs}";
	}

	/**
	 * Get static expire
	 *
	 * @access protected
	 * @param void
	 * @return int
	 */
	protected function getExpireIn() : int
	{
		return intval($this->global->options->ttl);
	}

	/**
	 * Get static view extension
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getViewExtension() : string
	{
		return "{$this->global->options->view->extension}";
	}

	/**
	 * Get static base url
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getBaseUrl() : string
	{
		return "{$this->global->url->base}";
	}

	/**
	 * Get static assets url
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getAssetUrl() : string
	{
		return "{$this->getBaseUrl()}{$this->global->url->assets}";
	}

	/**
	 * Get static admin url
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getAdminUrl() : string
	{
		return "{$this->getBaseUrl()}{$this->global->url->admin}";
	}

	/**
	 * Get static login url
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getLoginUrl() : string
	{
		return "{$this->getBaseUrl()}{$this->global->url->login}";
	}

	/**
	 * Get static API username
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getApiUsername() : string
	{
		return "{$this->global->api->username}";
	}

	/**
	 * Get static API password
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getApiPassword() : string
	{
		return "{$this->global->api->password}";
	}

	/**
	 * Get static allowed Access
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getAllowedAccess() : array
	{
		return $this->global->access->allowed->ip;
	}

	/**
	 * Get static denied Access
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getDeniedAccess() : array
	{
		return $this->global->access->denied->ip;
	}

	/**
	 * Get static session ID
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getSessionID() : string
	{
		return "{$this->global->access->session}";
	}

	/**
	 * Get static debug status
	 *
	 * @access protected
	 * @param void
	 * @return bool
	 */
	protected function isDebug() : bool
	{
		return $this->global->options->debug;
	}
}
