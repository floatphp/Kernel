<?php
/**
 * @author     : JIHAD SINNAOUR
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.0.0
 * @category   : PHP framework
 * @copyright  : (c) 2017 - 2022 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://www.floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Helpers\Framework\Validator;
use FloatPHP\Classes\{
    Filesystem\TypeCheck, Filesystem\Stringify, Filesystem\File, Filesystem\Json, 
    Http\Server
};

trait TraitConfiguration
{
	/**
	 * @access private
	 * @var object $global
	 * @var array $routes
	 */
	private $global = false;
	private $routes = [];

	/**
	 * Set Config Json File,
	 * Allow Parent Config Access.
	 *
	 * @access protected
	 * @param void
	 * @return void
	 */
	protected function initConfig()
	{
		// Parse Config file
		if ( File::exists(($path = $this->getConfigFile())) ) {
			Validator::checkConfig(($config = Json::parse($path)));
			$this->global = $config;

		} else {
			// Parse Default Config
			$this->global = Json::parse(dirname(__FILE__).'/bin/config.default.json');
		}

		// Set routes config
		if ( File::exists(($path = $this->getRoutesFile())) ) {
			Validator::checkRouteConfig(($routes = Json::parse($path,true)));
			$this->routes = $routes;
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
		$config = Json::parse($this->getConfigFile(),true);
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
	 * @param bool $trailingSlash
	 * @return string
	 */
	protected function getBaseRoute($trailingSlash = true) : string
	{
		$path = "{$this->global->path->base}";
		if ( !empty($path) ) {
			$path = ltrim($path,'/');
			$path = rtrim($path,'/');
			if ( $trailingSlash ) {
				$path = Stringify::trailingSlash($path);
			}
			$path = Stringify::formatPath($path);
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
		$access = File::parseIni($this->getDatabaseFile(),true);
		Validator::checkDatabaseAccess($access);
		return [
			'db'      => isset($access['default']['db']) ? $access['default']['db'] : '',
			'host'    => isset($access['default']['host']) ? $access['default']['host'] : 'localhost',
			'port'    => isset($access['default']['port']) ? $access['default']['port'] : 3306,
			'user'    => isset($access['default']['user']) ? $access['default']['user'] : '',
			'pswd'    => isset($access['default']['pswd']) ? $access['default']['pswd'] : '',
			'charset' => isset($access['default']['charset']) ? $access['default']['charset'] : ''
		];
	}

	/**
	 * Get static database root access
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getDatabaseRootAccess() : array
	{
		$access = File::parseIni($this->getDatabaseFile(),true);
		Validator::checkDatabaseAccess($access);
		return [
			'user' => isset($access['root']['user']) ? $access['root']['user'] : 'root',
			'pswd' => isset($access['root']['pswd']) ? $access['root']['pswd'] : ''
		];
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
	 * Get static view path
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getViewPath() : array
	{
		$path = $this->global->path->view;
		if ( TypeCheck::isArray($path) ) {
			foreach ($path as $key => $view) {
				$path[$key] = Stringify::formatPath("{$this->getRoot()}/{$view}",1);
			}
		} else {
			$path = "{$this->getRoot()}/{$this->global->path->view}";
			$path = Stringify::formatPath($path,1);
		}
		return (array)$path;
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
		return glob("{$this->getModulesPath()}/*");
	}

	/**
	 * Get static modules config.
	 *
	 * @access protected
	 * @param void
	 * @return array
	 */
	protected function getModulesConfig() : array
	{
		$list = [];
		foreach ( $this->getModules() as $name ) {
			$config = Json::parse("{$name}/module.json");
			$list[] = [
				'name'        => $config->name,
				'description' => $config->description,
				'system'      => $config->system,
				'migrate'     => $config->migrate
			];
		}
		return $list;
	}

	/**
	 * Get static expire.
	 *
	 * @access protected
	 * @param void
	 * @return int
	 */
	protected function getCacheTTL() : int
	{
		return $this->global->options->ttl;
	}

	/**
	 * Get static view extension.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getViewExtension() : string
	{
		return $this->global->options->view->extension;
	}

	/**
	 * Get static base url.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getBaseUrl() : string
	{
		$url = Server::getBaseUrl();
		$route = $this->getBaseRoute();
		return Stringify::untrailingSlash("{$url}/{$route}");
	}

	/**
	 * Get static assets url.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getAssetUrl() : string
	{
		$url = "{$this->getBaseUrl()}{$this->global->path->assets}";
		return Stringify::untrailingSlash($url);
	}

	/**
	 * Get static assets path.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getAssetPath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->assets}";
		return Stringify::formatPath($path,1);
	}

	/**
	 * Get static front upload url.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getFrontUploadUrl() : string
	{
		$url = "{$this->getBaseUrl()}{$this->global->path->upload->front}";
		return Stringify::untrailingSlash($url);
	}

	/**
	 * Get static front upload path.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getFrontUploadPath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->upload->front}";
		return Stringify::formatPath($path,1);
	}

	/**
	 * Get static upload url.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getAdminUploadUrl() : string
	{
		$url = "{$this->getBaseUrl()}{$this->global->path->upload->admin}";
		return Stringify::untrailingSlash($url);
	}

	/**
	 * Get static admin upload path.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getAdminUploadPath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->upload->admin}";
		return Stringify::formatPath($path,1);
	}

	/**
	 * Get static admin storage path.
	 *
	 * @access protected
	 * @param string $path
	 * @return string
	 */
	protected function getStoragePath($path = 'Storage') : string
	{
		return "{$this->getAppDir()}/{$path}";
	}

	/**
	 * Get static public path.
	 *
	 * @access protected
	 * @param string $path
	 * @return string
	 */
	protected function getPublicPath($path = 'public') : string
	{
		return "{$this->getRoot()}/{$path}";
	}

	/**
	 * Get static admin url.
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
	 * Get static verify url.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getVerifyUrl() : string
	{
		return "{$this->getBaseUrl()}{$this->global->url->verify}";
	}

	/**
	 * Get static login url.
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
	 * Get static API username.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getApiUsername() : string
	{
		return $this->global->api->username;
	}

	/**
	 * Get static API password.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getApiPassword() : string
	{
		return $this->global->api->password;
	}

	/**
	 * Get static allowed Access.
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
	 * Get static denied Access.
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
	 * Get static session ID.
	 *
	 * @access protected
	 * @param void
	 * @return string
	 */
	protected function getSessionId() : string
	{
		return $this->global->access->sessionId;
	}

	/**
	 * Get static access expire.
	 *
	 * @access protected
	 * @param void
	 * @return int
	 */
	protected function getAccessExpire() : int
	{
		return (int)$this->global->access->expire;
	}

	/**
	 * Get static secret.
	 *
	 * @access protected
	 * @param bool $api
	 * @return string
	 */
	protected function getSecret($api = false) : string
	{
		if ( $api ) {
			if ( !empty($this->global->api->secret) ) {
				return $this->global->api->secret;
			}
		}
		return $this->global->access->secret;
	}

	/**
	 * Get static permissions status.
	 *
	 * @access protected
	 * @param void
	 * @return bool
	 */
	protected function isPermissions() : bool
	{
		return $this->global->access->permissions;
	}

	/**
	 * Get static debug status.
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
	 * Get static config file path.
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
	 * Get static routes file path.
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
	 * Get static database file path.
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
