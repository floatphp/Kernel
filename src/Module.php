<?php
/**
 * @author     : JIHAD SINNAOUR
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.0.1
 * @category   : PHP framework
 * @copyright  : (c) 2017 - 2023 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://www.floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Classes\Filesystem\{
    File, Json
};
use FloatPHP\Helpers\Framework\{
    Configurator, Validator, Permission
};

class Module extends BaseController
{
	/**
	 * @param void
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
	 * @param mixed $roles
	 * @return bool
	 */
	public function hasPermissions($roles = false) : bool
	{
		if ( $this->isPermissions() ) {
			if ( $roles ) {
				return Permission::hasRole($roles);
			}
		}
		return true;
	}

	/**
	 * Get modules routes.
	 *
	 * @access public
	 * @param void
	 * @param array
	 */
	public function getModulesRoutes()
	{
		$wrapper = [];
		if ( $this->getModules() ) {
			foreach ( $this->getModules() as $key => $name ) {
				$config = Json::parse("{$name}/module.json", true);
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
			$this->render(['js' => "{$this->getModulesUrl()}/{$js}"],$tpl);
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
			$this->render(['css' => "{$this->getModulesUrl()}/{$css}"],$tpl);
		});
	}

	/**
	 * Get modules routes.
	 *
	 * @access private
	 * @param void
	 * @param array
	 */
	private function loadModules()
	{
		foreach ( $this->getModules() as $name ) {
			$config = Json::parse("{$name}/module.json");
			Validator::checkModuleConfig($config);
			if ( $config->migrate ) {
				$configurator = new Configurator();
				$configurator->migrate("{$name}/migrate");
			}
			if ( $config->enable ) {
				$basename = basename($name);
				$namespace = "{$this->getModuleNamespace()}{$basename}\\{$basename}";
				if ( File::exists("{$name}/{$basename}Module.php") ) {
					$module = "{$namespace}Module";
					new $module;
				}
			}
		}
	}
}
