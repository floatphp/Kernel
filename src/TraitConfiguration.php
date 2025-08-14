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

use FloatPHP\Classes\Http\Server;
use FloatPHP\Helpers\Framework\Validator;
use FloatPHP\Exceptions\Kernel\ConfigException;

trait TraitConfiguration
{
	use \FloatPHP\Helpers\Framework\tr\TraitIO,
		\FloatPHP\Helpers\Framework\tr\TraitFormattable;

	/**
	 * @access private
	 * @var object $global
	 * @var bool $cacheable
	 */
	private $global;
	private $cacheable = false;

	/**
	 * @access protected
	 */
	protected const STORAGE = 'Storage';
	protected const PATH    = 'config';

	/**
	 * Init config.
	 *
	 * @access public
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
	 * Get static instance.
	 *
	 * @access protected
	 * @return object
	 */
	protected static function getStatic() : object
	{
		return new static;
	}

	/**
	 * Init global config.
	 *
	 * @access protected
	 * @return void
	 */
	protected function initConfig() : void
	{
		if ( $this->global ) {
			return;
		}

		if ( defined('FLOATCACHE') ) {
			$this->cacheable = (bool)constant('FLOATCACHE');
		}

		if ( $this->cacheable ) {

			$key = $this->slugify(self::class);
			if ( !($this->global = $this->getTransient($key)) ) {
				$this->global = $this->parseConfig('global');
				$this->setTransient($key, $this->global, 0);
			}

		} else {
			$this->global = $this->parseConfig('global');
		}
	}

	/**
	 * Parse app config file.
	 *
	 * @access protected
	 * @param string $config
	 * @param bool $validate
	 * @return mixed
	 * @throws ConfigException
	 */
	protected function parseConfig(string $config, bool $validate = true) : mixed
	{
		$file = $this->getConfigFile($config);
		if ( !$this->isFile($file) ) {
			throw new ConfigException(
				ConfigException::invalidConfigFile($file)
			);
		}

		$data = $this->parseJson($file);
		if ( $validate ) {
			// Validator::validate($data, $config);
		}

		return $data;
	}

	/**
	 * Reset config object.
	 *
	 * @access protected
	 * @return void
	 */
	protected function resetConfig() : void
	{
		$this->global = null;
	}

	/**
	 * Get global config option,
	 * Initialized.
	 *
	 * @access protected
	 * @param ?string $key
	 * @return mixed
	 */
	protected function getConfig(?string $key = null) : mixed
	{
		$this->initConfig();
		$data = $this->global;
		if ( $key ) {
			$data = $data->{$key} ?? null;
		}
		$this->resetConfig();
		return $data;
	}

	/**
	 * Update global config options.
	 *
	 * @access protected
	 * @param array $options
	 * @param int $args
	 * @return bool
	 */
	protected function updateConfig(array $options = [], int $args = 64 | 256) : bool
	{
		if ( $this->getEnv() == 'dev' ) {
			return false;
		}

		if ( $this->hasDebug() ) {
			$args = 64 | 128 | 256;
		}

		$file = $this->getConfigFile();
		$data = $this->parseJson($file);

		foreach ($options as $option => $value) {
			if ( isset($data['options'][$option]) ) {
				$data['options'][$option] = $value;
			}
		}

		// Validator::validate($data, 'global');

		$data = $this->formatJson($data, $args);
		return $this->writeFile($file, $data);
	}

	/**
	 * Load partial config file,
	 * Require initialization.
	 *
	 * @access protected
	 * @param string $config
	 * @param bool $validate
	 * @return array
	 */
	protected function loadConfig(string $config, bool $validate = true) : array
	{
		$data = [];

		if ( $this->cacheable ) {

			// $key = $this->applyPrefix($config);
			// if ( !($data = $this->getTransient($key)) ) {
			// 	$data = $this->parseConfig($config, $validate);
			// 	$this->setTransient($key, $data, 0);
			// }

		} else {
			// $data = $this->parseConfig($config, $validate);
		}

		$data = $this->parseConfig($config, $validate);
		return $this->toArray($data);
	}

	/**
	 * Get app directory.
	 *
	 * @access protected
	 * @return string
	 * @throws ConfigException
	 */
	protected function getAppDir() : string
	{
		global $__APP__;
		if ( !$__APP__ ) {
			throw new ConfigException(
				ConfigException::undefinedAppDir()
			);
		}
		return $this->formatPath($__APP__, true);
	}

	/**
	 * Get root path.
	 *
	 * @access public
	 * @param string $sub
	 * @return string
	 */
	public function getRoot(?string $sub = null) : string
	{
		$path = dirname($this->getAppDir());
		if ( $sub ) {
			$path = "{$path}/{$sub}";
		}
		return $this->formatPath($path, true);
	}

	/**
	 * Get storage path.
	 *
	 * @access protected
	 * @param ?string $sub
	 * @return string
	 */
	protected function getStoragePath(?string $sub = null) : string
	{
		$path = static::STORAGE;
		$path = "{$this->getAppDir()}/{$path}/{$sub}";
		return $this->formatPath($path, true);
	}

	/**
	 * Get storage URL.
	 *
	 * @access protected
	 * @param ?string $sub
	 * @return string
	 */
	protected function getStorageUrl(?string $sub = null) : string
	{
		$base = $this->basename($this->getAppDir());
		$path = static::STORAGE;
		$path = "{$base}/{$path}/{$sub}";
		return $this->getBaseUrl($path);
	}

	/**
	 * Get config directory path.
	 *
	 * @access protected
	 * @param ?string $sub
	 * @return string
	 */
	protected function getConfigPath(?string $sub = null) : string
	{
		$path = static::PATH;
		$path = "{$path}/{$sub}";
		return $this->getStoragePath($path);
	}

	/**
	 * Get config file path.
	 *
	 * @access protected
	 * @param string $config
	 * @return string
	 */
	protected function getConfigFile(string $config = 'global') : string
	{
		return $this->getConfigPath("{$config}.json");
	}

	/**
	 * Get database config file.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getDbFile() : string
	{
		$config = $this->getConfig('path');
		$file = $config->db ?? '';
		return $this->getConfigPath($file);
	}

	/**
	 * Get routes base path.
	 *
	 * @access protected
	 * @param bool $slash
	 * @return string
	 */
	protected function getBaseRoute(bool $slash = true) : string
	{
		$config = $this->getConfig('path');
		$data = $config->base ?? '';
		if ( $data ) {
			$data = ltrim($data, '/');
			$data = rtrim($data, '/');
			if ( $slash ) {
				$data = $this->trailingSlash($data);
			}
			$data = $this->formatPath($data);
		}
		return $data;
	}

	/**
	 * Get routes.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getRoutes() : array
	{
		$this->initConfig();
		$data = $this->loadConfig('routes');
		$this->resetConfig();
		return $data['routes'] ?? [];
	}

	/**
	 * Get namespace (Controller, Module).
	 *
	 * @access protected
	 * @param string $name
	 * @return string
	 * @internal
	 */
	protected function getNamespace(string $name) : string
	{
		$config = $this->getConfig('namespace');
		$name = $config->{$name} ?? '';
		$base = $this->basename($this->getAppDir());
		$name = $this->formatPath("{$base}/{$name}");
		return $this->replaceString('/', '\\', $name);
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

		// Validator::checkDatabaseConfig($data, $type);
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
	 * Get view path.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getViewPath() : array
	{
		$config = $this->getConfig('path');
		$path = $config->view;

		if ( $this->isType('string', $path) ) {
			$path = [$path];
		}

		foreach ($path as $key => $dir) {
			$dir = "{$this->getAppDir()}/{$dir}";
			$path[$key] = $this->formatPath($dir, true);
		}

		return $path;
	}

	/**
	 * Get cache path.
	 *
	 * @access protected
	 * @param ?string $sub
	 * @return string
	 */
	protected function getCachePath(?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->cache ?? '';
		return $this->getStoragePath("{$path}/{$sub}");
	}

	/**
	 * Get translation path.
	 *
	 * @access protected
	 * @param ?string $sub
	 * @return string
	 */
	protected function getTranslatePath(?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->translation ?? '';
		return $this->getStoragePath("{$path}/{$sub}");
	}

	/**
	 * Get logs path.
	 *
	 * @access protected
	 * @param ?string $sub
	 * @return string
	 */
	protected function getLoggerPath(?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->logs ?? '';
		return $this->getStoragePath("{$path}/{$sub}");
	}

	/**
	 * Get migrate path.
	 *
	 * @access protected
	 * @param ?string $sub
	 * @return string
	 */
	protected function getMigratePath(?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->migrate ?? '';
		return $this->getStoragePath("{$path}/{$sub}");
	}

	/**
	 * Get module URL.
	 *
	 * @access protected
	 * @param ?string $sub
	 * @return string
	 */
	protected function getModuleUrl(?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->modules ?? '';
		return $this->getBaseUrl("{$path}/{$sub}");
	}

	/**
	 * Get module path.
	 *
	 * @access protected
	 * @param ?string $sub
	 * @return string
	 */
	protected function getModulePath(?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->modules ?? '';
		$path = "{$this->getAppDir()}/{$path}/{$sub}";
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
		return glob("{$this->getModulePath()}/*");
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
		$config = $this->getConfig('options');
		return $config->timeout;
	}

	/**
	 * Get cache TTL.
	 *
	 * @access protected
	 * @return int
	 */
	protected function getCacheTTL() : int
	{
		$config = $this->getConfig('options');
		return $config->ttl;
	}

	/**
	 * Get view extension.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getViewExtension() : string
	{
		$config = $this->getConfig('options');
		return $config->view->extension;
	}

	/**
	 * Get base url.
	 *
	 * @access protected
	 * @param ?string $sub
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
	 * @param ?string $sub
	 * @return string
	 */
	protected function getAssetUrl(?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->assets ?? '';
		return $this->getPublicUrl("{$path}/{$sub}");
	}

	/**
	 * Get assets path.
	 *
	 * @access protected
	 * @param ?string $sub
	 * @return string
	 */
	protected function getAssetPath(?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->assets ?? '';
		return $this->getPublicPath("{$path}/{$sub}");
	}

	/**
	 * Get upload URL.
	 *
	 * @access protected
	 * @param string $type
	 * @param ?string $sub
	 * @return string
	 */
	protected function getUploadUrl(string $type = 'admin', ?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->upload ?? '';
		$path = "{$path}/{$sub}";

		return match ($type) {
			'admin' => $this->getStorageUrl($path),
			'front' => $this->getPublicUrl($path),
			default => $this->getBaseUrl($path)
		};
	}

	/**
	 * Get upload path.
	 *
	 * @access protected
	 * @param string $type
	 * @param ?string $sub
	 * @return string
	 */
	protected function getUploadPath(string $type = 'admin', ?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->upload ?? '';
		$path = "{$path}/{$sub}";

		return match ($type) {
			'admin' => $this->getStoragePath($path),
			'front' => $this->getPublicPath($path),
			default => $this->formatPath($path)
		};
	}

	/**
	 * Get public URL.
	 *
	 * @access protected
	 * @param ?string $sub
	 * @return string
	 */
	protected function getPublicUrl(?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->public ?? '';
		return $this->getBaseUrl("{$path}/{$sub}");
	}

	/**
	 * Get public path.
	 *
	 * @access protected
	 * @param string $sub
	 * @return string
	 */
	protected function getPublicPath(?string $sub = null) : string
	{
		$config = $this->getConfig('path');
		$path = $config->public ?? '';
		return $this->getRoot("{$path}/{$sub}");
	}

	/**
	 * Get admin URL.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getAdminUrl() : string
	{
		$config = $this->getConfig('url');
		return $config->admin;
	}

	/**
	 * Get verify URL.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getVerifyUrl() : string
	{
		$config = $this->getConfig('url');
		return $config->verify;
	}

	/**
	 * Get login URL.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getLoginUrl() : string
	{
		$config = $this->getConfig('url');
		return $config->login;
	}

	/**
	 * Get API base URL.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getApiUrl() : string
	{
		$config = $this->getConfig('url');
		return $config->api;
	}

	/**
	 * Get API basic username.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getApiUsername() : string
	{
		$config = $this->getConfig('api');
		return $config->username;
	}

	/**
	 * Get API basic password.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getApiPassword() : string
	{
		$config = $this->getConfig('api');
		return $config->password;
	}

	/**
	 * Get API version.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getApiVersion() : string
	{
		$config = $this->getConfig('api');
		return $config->version;
	}

	/**
	 * Get allowed Access.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getAllowedAccess() : array
	{
		$config = $this->getConfig('access');
		return $config->allowed->ip;
	}

	/**
	 * Get denied Access.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getDeniedAccess() : array
	{
		$config = $this->getConfig('access');
		return $config->denied->ip;
	}

	/**
	 * Get session ID.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getSessionId() : string
	{
		$config = $this->getConfig('access');
		return $config->sessionId;
	}

	/**
	 * Get access expire.
	 *
	 * @access protected
	 * @return int
	 */
	protected function getAccessExpire() : int
	{
		$config = $this->getConfig('access');
		return $config->expire;
	}

	/**
	 * Get secret.
	 *
	 * @access protected
	 * @param bool $api
	 * @return string
	 */
	protected function getSecret(bool $api = false) : string
	{
		if ( $api ) {
			$config = $this->getConfig('api');
			if ( $config->secret ) {
				return $config->secret;
			}
		}
		$config = $this->getConfig('access');
		return $config->secret;
	}

	/**
	 * Get permissions status.
	 *
	 * @access protected
	 * @return bool
	 */
	protected function isPermissions() : bool
	{
		$config = $this->getConfig('access');
		return $config->permissions;
	}

	/**
	 * Get debug status.
	 *
	 * @access protected
	 * @return bool
	 */
	protected function isDebug() : bool
	{
		$config = $this->getConfig('options');
		return $config->debug;
	}

	/**
	 * Get static environment.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getEnv() : string
	{
		$config = $this->getConfig('options');
		return $config->environment;
	}

	/**
	 * Check static environment.
	 *
	 * @access protected
	 * @return bool
	 */
	protected function isEnv(string $env = 'prod') : bool
	{
		return $this->getEnv() == $env;
	}

	/**
	 * Check admin area.
	 *
	 * @access protected
	 * @return bool
	 */
	protected function isAdmin() : bool
	{
		$url = Server::getCurrentUrl();
		return $this->hasString($url, '/admin/');
	}

	/**
	 * Get strings.
	 *
	 * @access protected
	 * @param string $type
	 * @return array
	 */
	protected function getStrings(?string $type = null) : array
	{
		$this->initConfig();
		$data = $this->loadConfig('strings');
		$this->resetConfig();
		return $type ? $data[$type] ?? [] : $data;
	}

	/**
	 * Get menu.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getMenu() : array
	{
		$this->initConfig();
		$data = $this->loadConfig('menu', true);
		$this->resetConfig();
		return $data['menu'] ?? [];
	}

	/**
	 * Get vars.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getVars() : array
	{
		$this->initConfig();
		$data = $this->loadConfig('vars');
		$this->resetConfig();
		return $data['vars'] ?? [];
	}
}
