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

use FloatPHP\Helpers\Framework\{Installer, Validator};

class Module extends BaseController
{
	/**
	 * @access public
	 */
	public function __construct()
	{
		$this->loadModules();
	}

	/**
	 * Check permission.
	 *
	 * @access public
	 * @param string $role
	 * @return bool
	 */
	public function hasPermissions(?string $role = null) : bool
	{
		if ( $this->isPermissions() && $role ) {
			return $this->hasRole($role);
		}
		return true;
	}

	/**
	 * Get modules routes.
	 *
	 * @access public
	 * @param array
	 */
	public function getModulesRoutes() : mixed
	{
		$wrapper = [];
		if ( $this->getModules() ) {
			foreach ($this->getModules() as $key => $name) {
				$config = $this->parseJson("{$name}/module.json", isArray: true);
				if ( $config['enable'] == true ) {
					foreach ($config['router'] as $route) {
						$wrapper[] = $route;
					}
				}
			}
		}
		return $this->applyFilter('module-routes', $wrapper);
	}

	/**
	 * Add module js.
	 *
	 * @access protected
	 * @param string $path
	 * @param string $hook
	 * @return void
	 */
	protected function addJS(string $path, string $hook = 'add-js') : void
	{
		$this->addAction($hook, function () use ($path) : void {
			$file = $this->applyFilter('module-view-js', 'system/js');
			$this->render($file, ['js' => "{$this->getModuleUrl()}/{$path}"]);
		});
	}

	/**
	 * Add module css.
	 *
	 * @access protected
	 * @param string $path
	 * @param string $hook
	 * @return void
	 */
	protected function addCSS(string $path, string $hook = 'add-css') : void
	{
		$this->addAction($hook, function () use ($path) : void {
			$file = $this->applyFilter('module-view-css', 'system/css');
			$this->render($file, ['css' => "{$this->getModuleUrl()}/{$path}"]);
		});
	}

	/**
	 * Get modules routes.
	 *
	 * @access private
	 * @param array
	 */
	private function loadModules() : void
	{
		foreach ($this->getModules() as $name) {
			$config = $this->parseJson("{$name}/module.json");
			Validator::checkModuleConfig($config);
			if ( $config->migrate ) {
				(new Installer())->migrate("{$name}/migrate");
			}
			if ( $config->enable ) {
				$basename = $this->basename($name);
				$namespace = "{$this->getNamespace('module')}{$basename}\\{$basename}";
				if ( $this->isFile("{$name}/{$basename}Module.php") ) {
					$module = "{$namespace}Module";
					new $module;
				}
			}
		}
	}
}
