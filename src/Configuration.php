<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.1.0
 * @category  : PHP framework
 * @copyright : (c) JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 *
 * This file if a part of FloatPHP Framework
 */

namespace floatPHP\Kernel;

use floatPHP\Classes\Filesystem\Json;
use floatPHP\Classes\Filesystem\File;

trait Configuration
{
	/**
	 * @access private
	 * @var string $configPath
	 * @var string $routesPath
	 * @var object $global
	 * @var object $routes
	 */
	private $configPath = 'App/Storage/config/global.json';
	private $routesPath = 'App/Storage/config/routes.json';
	private $global = false;
	private $routes = false;

	/**
	 * Get static instance
	 *
	 * @access public
	 * @param void
	 * @return object
	 */
	public static function getStatic()
	{
		return new static;
	}
	
	/**
	 * Set Config Json File
	 * Allow Parent Config Access
	 *
	 * @param void
	 * @return void
	 */
	protected function initConfig()
	{
		// Parse Config file
		$config = new Json($this->configPath);
		$this->global = $config->parse();
		// Parse Routes file
		$routes = new Json($this->routesPath);
		$this->routes = $routes->parse(true);
	}

	/**
	 * Get global
	 *
	 * @access public
	 * @param void
	 * @return mixed
	 */
	public function getConfig($var = null)
	{
		if ($var) {
			return isset($this->global->$var)
			? $this->global->$var : false;
		}
		return $this->global;
	}

	/**
	 * Update Custom Options
	 *
	 * @access public
	 * @param array $options
	 * @return void
	 */
	public function updateConfig($options = [])
	{
		$json = new Json("{$this->getRoot()}{$this->configPath}");
		$config = $json->parse(true);
		foreach ($options as $option => $value) {
			if ( isset($config['options'][$option]) ) {
				$config['options'][$option] = $value;
			}
		}
		$config = Json::format($config);
		File::write("{$this->getRoot()}{$this->configPath}",$config);
	}

	/**
	 * Get Routes
	 *
	 * @access public
	 * @param void
	 * @return array
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Get View Extension
	 *
	 * @access public
	 * @param void
	 * @return string
	 */
	public function getViewExtension()
	{
		return $this->global->view->extension;
	}

	/**
	 * Get View Path
	 *
	 * @access public
	 * @param void
	 * @return string
	 */
	public function getViewPath()
	{
		return $this->global->path->view;
	}

	/**
	 * Get Cache Path
	 *
	 * @access public
	 * @param void
	 * @return string
	 */
	public function getCachePath()
	{
		return $this->global->path->cache;
	}

	/**
	 * Get Base Uri
	 *
	 * @access public
	 * @param void
	 * @return string
	 */
	public function getBaseUri()
	{
		return $this->global->baseUri;
	}

	/**
	 * Get root
	 *
	 * @access public
	 * @param void
	 * @return string
	 */
	public function getRoot()
	{
		return $this->global->root;
	}

	/**
	 * Get Session ID
	 *
	 * @access public
	 * @param void
	 * @return string
	 */
	public function getSessionID()
	{
		return $this->global->options->sessionID;
	}

	/**
	 * Get Login Location
	 *
	 * @access public
	 * @param void
	 * @return string
	 */
	public function getLoginLocation()
	{
		return $this->global->options->login;
	}

	/**
	 * Get Admin Location
	 *
	 * @access public
	 * @param void
	 * @return string
	 */
	public function getAdminLocation()
	{
		return $this->global->options->admin;
	}

	/**
	 * Get static root
	 *
	 * @access public
	 * @param void
	 * @return string
	 */
	public function getApiUsername()
	{
		return $this->global->api->username;
	}

	/**
	 * Get static root
	 *
	 * @access public
	 * @param void
	 * @return string
	 */
	public function getApiPassword()
	{
		return $this->global->api->password;
	}

	/**
	 * Get Debug
	 *
	 * @access public
	 * @param void
	 * @return boolean
	 */
	public function isDebug()
	{
		return $this->global->options->debug;
	}
}
