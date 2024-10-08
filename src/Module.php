<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.1.0
 * @copyright  : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Helpers\Framework\{
    Installer, Validator
};

class Module extends BaseController
{
	/**
	 * @uses initConfig()
	 */
	public function __construct()
	{
		// Init configuration
		$this->initConfig();

		// Load modules
		$this->loadModules();
	}

	/**
	 * @access public
	 * @param mixed $role
	 * @return bool
	 */
	public function hasPermissions($role = false) : bool
	{
		if ( $this->isPermissions() ) {
			if ( $role ) {
				return $this->hasRole($role);
			}
		}
		return true;
	}

	/**
	 * Get modules routes.
	 *
	 * @access public
	 * @param array
	 */
	public function getModulesRoutes()
	{
		$wrapper = [];
		if ( $this->getModules() ) {
			foreach ( $this->getModules() as $key => $name ) {
				$config = $this->parseJson("{$name}/module.json", true);
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
	 * @param string $js
	 * @param string $hook
	 * @return void
	 */
	protected function addJS($js, $hook = 'add-js')
	{
		$this->addAction($hook, function() use($js) {
			$tpl = $this->applyFilter('module-view-js', 'system/js');
			$this->render(['js' => "{$this->getModulesUrl()}/{$js}"], $tpl);
		});
	}

	/**
	 * Add module css.
	 *
	 * @access protected
	 * @param string $css
	 * @param string $hook
	 * @return void
	 */
	protected function addCSS($css, $hook = 'add-css')
	{
		$this->addAction($hook, function() use($css){
			$tpl = $this->applyFilter('module-view-css', 'system/css');
			$this->render(['css' => "{$this->getModulesUrl()}/{$css}"], $tpl);
		});
	}

	/**
	 * Get modules routes.
	 *
	 * @access private
	 * @param array
	 */
	private function loadModules()
	{
		foreach ( $this->getModules() as $name ) {
			$config = $this->parseJson("{$name}/module.json");
			Validator::checkModuleConfig($config);
			if ( $config->migrate ) {
				(new Installer())->migrate("{$name}/migrate");
			}
			if ( $config->enable ) {
				$basename = $this->basename($name);
				$namespace = "{$this->getModuleNamespace()}{$basename}\\{$basename}";
				if ( $this->hasFile("{$name}/{$basename}Module.php") ) {
					$module = "{$namespace}Module";
					new $module;
				}
			}
		}
	}
}
