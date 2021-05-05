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
	 * @var array $routes
	 */
	private $global = false;
	private $routes = [];

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
		if ( File::exists(($path = $this->getConfigFile())) ) {
			$json = new Json($path);
			Validator::checkConfig($json);
			$this->global = $json->parse();
		} else {
			// Parse Default Config
			$json = new Json(dirname(__FILE__).'/bin/config.default.json');
			$this->global = $json->parse();
		}

		// Set routes config
		if ( File::exists(($path = $this->getRoutesFile())) ) {
			$routes = new Json($path);
			$this->routes = $routes->parse(true);
		}
	}

	/**
	 * Get config
	 *
	 * @access protected
	 * @param string $var
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
	 * Update config
	 *
	 * @access protected
	 * @param array $options
	 * @param int $args
	 * @return void
	 */
	protected function updateConfig($options = [], $section = 'options', $args = 64|128|256)
	{
		$json = new Json($this->getConfigFile());
		$config = $json->parse(true);
		foreach ($options as $option => $value) {
			if ( isset($config[$section][$option]) ) {
				$config[$section][$option] = $value;
			}
		}
		$config = Json::format($config,$args);
		File::w($this->getConfigFile(),$config);
	}

	/**
	 * Get static app dir
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getAppDir() : string
	{
		global $appDir;
		return Stringify::formatPath($appDir,1);
	}

	/**
	 * Get static dir root
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getRoot() : string
	{
		return dirname($this->getAppDir());
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
		$path = "{$this->global->path->base}";
		if ( !empty($path) ) {
			$path = ltrim($path,'/');
			$path = Stringify::formatPath(Stringify::trailingSlash($path));
		}
		return $path;
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
	 * Get static controller namespace
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getControllerNamespace() : string
	{
		$namespace = "{$this->global->namespace->controller}";
		return Stringify::replace('/','\\',$namespace);
	}

	/**
	 * Get static module namespace
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getModuleNamespace() : string
	{
		$namespace = "{$this->global->namespace->module}";
		return Stringify::replace('/','\\',$namespace);
	}

	/**
	 * Get static database access
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getDatabaseAccess() : array
	{
		return (array) File::parseIni($this->getDatabaseFile());
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
		$path = "{$this->getRoot()}/{$this->global->path->cache}";
		return Stringify::formatPath($path,1);
	}

	/**
	 * Get static translation cache path
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getTranslateCachePath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->cache}/translation";
		return Stringify::formatPath($path,1);
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
		$path = "{$this->getRoot()}/{$this->global->path->temp}";
		return Stringify::formatPath($path,1);
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
		$path = "{$this->getRoot()}/{$this->global->path->view}";
		return Stringify::formatPath($path,1);
	}

	/**
	 * Get static translate path
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getTranslatePath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->translate}";
		return Stringify::formatPath($path,1);
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
		$path = "{$this->getRoot()}/{$this->global->path->logs}";
		return Stringify::formatPath($path,1);
	}

	/**
	 * Get static migrate path
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getMigratePath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->migrate}";
		return Stringify::formatPath($path,1);
	}

	/**
	 * Get static modules path
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getModulesPath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->modules}";
		return Stringify::formatPath($path,1);
	}

	/**
	 * Get static modules url
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getModulesUrl() : string
	{
		$path = "{$this->getBaseUrl()}/{$this->global->path->modules}";
		return Stringify::formatPath($path,1);
	}

	/**
	 * Get static modules
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getModules() : array
	{
		return glob("{$this->getModulesPath()}/*",1073741824);
	}

	/**
	 * Get static modules config
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getModulesConfig() : array
	{
		$list = [];
		foreach ( $this->getModules() as $name ) {
			$json = new Json("{$name}/module.json");
			$config = $json->parse();
			$list[] = [
				'name'        => $config->name,
				'description' => $config->description,
				'system'      => $config->system
			];
		}
		return $list;
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
		$url = "{$this->global->url->base}";
		return Stringify::untrailingSlash($url);
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
		$url = "{$this->getBaseUrl()}{$this->global->url->assets}";
		return Stringify::untrailingSlash($url);
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
	protected function getSessionId() : string
	{
		return "{$this->global->access->sessionId}";
	}

	/**
	 * Get static access expire
	 *
	 * @access protected
	 * @param void
	 * @return int
	 */
	protected function getAccessExpire() : int
	{
		return "{$this->global->access->expire}";
	}

	/**
	 * Get static secret
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getSecret() : string
	{
		return "{$this->global->access->secret}";
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

	/**
	 * Get static config file path
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getConfigFile() : string
	{
		return "{$this->getAppDir()}/Storage/config/global.json";
	}

	/**
	 * Get static routes file path
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getRoutesFile() : string
	{
		return "{$this->getRoot()}/{$this->global->path->routes}";
	}

	/**
	 * Get static database file path
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getDatabaseFile() : string
	{
		return "{$this->getRoot()}/{$this->global->path->db}";
	}
}
