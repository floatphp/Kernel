<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.3.x
 * @copyright  : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Helpers\Framework\Validator;
use FloatPHP\Classes\Http\Server;

trait TraitConfiguration
{
	use \FloatPHP\Helpers\Framework\tr\TraitIO,
		\FloatPHP\Helpers\Framework\tr\TraitFormattable;

	/**
	 * @access private
	 * @var object $global
	 * @var array $routes
	 */
	private $global = false;
	private $routes = [];

	/**
	 * Init configuration.
	 */
	public function __construct()
	{
		$this->initConfig();
	}

	/**
	 * Prevent object clone.
	 */
	public function __clone()
	{
		exit(__METHOD__ . ': Clone denied');
	}

	/**
	 * Prevent object serialization.
	 */
	public function __wakeup()
	{
		exit(__METHOD__ . ': Unserialize denied');
	}

	/**
	 * Set config Json file,
	 * Allow parent config access.
	 *
	 * @access protected
	 * @return void
	 */
	protected function initConfig()
	{
		// Parse config file
		if ( $this->isFile(($path = $this->getConfigFile())) ) {
			Validator::checkConfig(($config = $this->parseJson($path)));
			$this->global = $config;

		} else {
			// Parse default config
			$this->global = $this->parseJson(
				dirname(__FILE__) . '/bin/config.default.json'
			);
		}

		// Set routes config
		if ( $this->isFile(($path = $this->getRoutesFile())) ) {
			Validator::checkRouteConfig(($routes = $this->parseJson($path, true)));
			$this->routes = $routes;
		}
	}

	/**
	 * Reset config.
	 *
	 * @access protected
	 * @return void
	 */
	protected function resetConfig()
	{
		unset($this->global);
		unset($this->routes);
	}

	/**
	 * Get config.
	 *
	 * @access protected
	 * @param string $var
	 * @return mixed
	 */
	protected function getConfig($var = null)
	{
		if ( $var ) {
			return $this->global->$var ?? false;
		}
		return $this->global;
	}

	/**
	 * Update config.
	 *
	 * @access protected
	 * @param array $options
	 * @param string $section
	 * @param int $args
	 * @return bool
	 */
	protected function updateConfig(array $options = [], string $section = 'options', int $args = 64 | 128 | 256) : bool
	{
		$config = $this->parseJson($this->getConfigFile(), true);
		foreach ($options as $option => $value) {
			if ( isset($config[$section][$option]) ) {
				$config[$section][$option] = $value;
			}
		}
		$config = $this->formatJson($config, $args);
		return $this->writeFile($this->getConfigFile(), $config);
	}

	/**
	 * Get config file path.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getConfigFile() : string
	{
		return "{$this->getAppDir()}/Storage/config/global.json";
	}

	/**
	 * Load configuration file.
	 *
	 * @access protected
	 * @param string $config
	 * @param bool $isArray
	 * @return mixed
	 */
	protected function loadConfig(string $config, bool $isArray = false)
	{
		$dir = "{$this->getAppDir()}/Storage/config";
		if ( $this->isFile(($json = "{$dir}/{$config}.json")) ) {
			return $this->decodeJson($this->readfile($json), $isArray);
		}
		return false;
	}

	/**
	 * Get app dir.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getAppDir() : string
	{
		global $appDir;
		return $this->formatPath($appDir, true);
	}

	/**
	 * Get dynamic root path.
	 *
	 * @access protected
	 * @param string $sub
	 * @return string
	 */
	protected function getRoot(?string $sub = null) : string
	{
		$path = dirname($this->getAppDir());
		if ( $sub ) {
			$path .= "/{$sub}";
		}
		return $this->formatPath($path);
	}

	/**
	 * Get base routes path.
	 *
	 * @access protected
	 * @param bool $trailingSlash
	 * @return string
	 */
	protected function getBaseRoute($trailingSlash = true) : string
	{
		$path = "{$this->global->path->base}";
		if ( !empty($path) ) {
			$path = ltrim($path, '/');
			$path = rtrim($path, '/');
			if ( $trailingSlash ) {
				$path = $this->trailingSlash($path);
			}
			$path = $this->formatPath($path);
		}
		return $path;
	}

	/**
	 * Get routes.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getRoutes() : array
	{
		return $this->routes;
	}

	/**
	 * Get controller namespace.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getControllerNamespace() : string
	{
		$namespace = "{$this->global->namespace->controller}";
		return $this->replaceString('/', '\\', $namespace);
	}

	/**
	 * Get module namespace.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getModuleNamespace() : string
	{
		$namespace = "{$this->global->namespace->module}";
		return $this->replaceString('/', '\\', $namespace);
	}

	/**
	 * Get database access.
	 *
	 * @access protected
	 * @param string $type
	 * @return array
	 */
	protected function getDbAccess(string $type = 'default') : array
	{
		$file = $this->getDbFile();
		$extension = $this->getFileExtension($file);
		$data = [];

		if ( $extension == 'yaml' ) {
			$data = $this->parseYaml($file, $type) ?: [];

		} elseif ( $extension == 'ini' ) {
			$data = $this->parseIni($file, true);
			$data = $data[$type] ?? [];
		}

		Validator::checkDatabaseConfig($data, $type);
		return $data;
	}

	/**
	 * Get database root access.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getDbRootAccess() : array
	{
		return $this->getDbAccess('root');
	}

	/**
	 * Get cache path.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getCachePath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->cache}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get view path.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getViewPath() : array
	{
		$path = $this->global->path->view;
		if ( $this->isType('array', $path) ) {
			foreach ($path as $key => $view) {
				$path[$key] = $this->formatPath("{$this->getRoot()}/{$view}", true);
			}
		} else {
			$path = "{$this->getRoot()}/{$this->global->path->view}";
			$path = $this->formatPath($path, true);
		}
		return (array)$path;
	}

	/**
	 * Get translation path.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getTranslatePath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->translation}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get logs path.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getLoggerPath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->logs}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get migrate path.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getMigratePath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->migrate}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get modules path.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getModulesPath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->modules}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get modules url.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getModulesUrl() : string
	{
		$path = "{$this->getBaseUrl()}/{$this->global->path->modules}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get modules.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getModules() : array
	{
		return glob("{$this->getModulesPath()}/*");
	}

	/**
	 * Get modules config.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getModulesConfig() : array
	{
		$list = [];
		foreach ($this->getModules() as $name) {
			$config = $this->parseJson("{$name}/module.json");
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
	 * Get timeout.
	 *
	 * @access protected
	 * @return int
	 */
	protected function getTimeout() : int
	{
		return $this->global->options->timeout;
	}

	/**
	 * Get expire.
	 *
	 * @access protected
	 * @return int
	 */
	protected function getCacheTTL() : int
	{
		return $this->global->options->ttl;
	}

	/**
	 * Get view extension.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getViewExtension() : string
	{
		return $this->global->options->view->extension;
	}

	/**
	 * Get base url.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getBaseUrl(?string $sub = null) : string
	{
		$url = Server::getBaseUrl();
		$url = "{$url}/{$this->getBaseRoute()}";
		if ( $sub ) {
			$url .= "/{$sub}";
		}
		return $this->formatPath($url, true);
	}

	/**
	 * Get assets url.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getAssetUrl() : string
	{
		$url = "{$this->getBaseUrl()}{$this->global->path->assets}";
		return $this->untrailingSlash($url);
	}

	/**
	 * Get assets path.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getAssetPath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->assets}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get public url.
	 *
	 * @access protected
	 * @param string $path
	 * @return string
	 */
	protected function getPublicUrl(?string $path = null) : string
	{
		return $this->formatPath(
			"{$this->getBaseUrl()}/public/{$path}"
		);
	}

	/**
	 * Get front upload url.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getFrontUploadUrl() : string
	{
		$url = "{$this->getBaseUrl()}{$this->global->path->upload->front}";
		return $this->untrailingSlash($url);
	}

	/**
	 * Get front upload path.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getFrontUploadPath() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->upload->front}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get upload url.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getAdminUploadUrl() : string
	{
		$url = "{$this->getBaseUrl()}{$this->global->path->upload->admin}";
		return $this->untrailingSlash($url);
	}

	/**
	 * Get admin upload path.
	 *
	 * @access protected
	 * @param string $path
	 * @return string
	 */
	protected function getAdminUploadPath(?string $path = null) : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->upload->admin}/{$path}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get admin path.
	 *
	 * @access protected
	 * @param string $path
	 * @return string
	 */
	protected function getAdminPath(?string $path = null) : string
	{
		$path = "{$this->getAppDir()}/{$path}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get public path.
	 *
	 * @access protected
	 * @param string $path
	 * @return string
	 */
	protected function getPublicPath(?string $path = null) : string
	{
		return $this->formatPath(
			"{$this->getRoot()}/public/{$path}"
		);
	}

	/**
	 * Get routes file path.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getRoutesFile() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->routes}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get database config file.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getDbFile() : string
	{
		$path = "{$this->getRoot()}/{$this->global->path->db}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get admin url.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getAdminUrl() : string
	{
		return "{$this->getBaseUrl()}{$this->global->url->admin}";
	}

	/**
	 * Get verify url.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getVerifyUrl() : string
	{
		return "{$this->getBaseUrl()}{$this->global->url->verify}";
	}

	/**
	 * Get login url.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getLoginUrl() : string
	{
		return "{$this->getBaseUrl()}{$this->global->url->login}";
	}

	/**
	 * Get API base url.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getApiBaseUrl() : string
	{
		return "{$this->global->url->api}";
	}

	/**
	 * Get API username.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getApiUsername() : string
	{
		return $this->global->api->username;
	}

	/**
	 * Get API password.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getApiPassword() : string
	{
		return $this->global->api->password;
	}

	/**
	 * Get allowed Access.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getAllowedAccess() : array
	{
		return $this->global->access->allowed->ip;
	}

	/**
	 * Get denied Access.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getDeniedAccess() : array
	{
		return $this->global->access->denied->ip;
	}

	/**
	 * Get session ID.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getSessionId() : string
	{
		return $this->global->access->sessionId;
	}

	/**
	 * Get access expire.
	 *
	 * @access protected
	 * @return int
	 */
	protected function getAccessExpire() : int
	{
		return (int)$this->global->access->expire;
	}

	/**
	 * Get secret.
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
	 * Get permissions status.
	 *
	 * @access protected
	 * @return bool
	 */
	protected function isPermissions() : bool
	{
		return $this->global->access->permissions;
	}

	/**
	 * Get debug status.
	 *
	 * @access protected
	 * @return bool
	 */
	protected function isDebug() : bool
	{
		return $this->global->options->debug;
	}

	/**
	 * Get admin status.
	 *
	 * @access protected
	 * @return bool
	 */
	protected function isAdmin() : bool
	{
		$url = Server::getBaseUrl();
		return $this->hasString($url, '/admin/');
	}

	/**
	 * Get strings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getStrings() : array
	{
		$strings = $this->loadConfig('strings', true);
		return ($strings) ? (array)$strings : (array)$this->global->strings;
	}

	/**
	 * Get vars.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getVars() : array
	{
		$vars = $this->loadConfig('vars', true);
		return $vars ?: (array)$this->global->vars;
	}

	/**
	 * Get menu.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getMenu() : mixed
	{
		$menu = $this->loadConfig('menu', true);
		return $menu ?: (array)$this->global->menu;
	}
}
