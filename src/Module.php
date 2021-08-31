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
use FloatPHP\Helpers\Framework\Configurator;
use FloatPHP\Helpers\Framework\Validator;

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
	 * Get modules routes
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
				$json = new Json("{$name}/module.json");
				$config = $json->parse(true);
				if ( $config['enable'] == true ) {
					foreach ($config['router'] as $route) {
						$wrapper[] = $route;
					}
				}
			}
		}
		return $this->applyFilter('module-routes',$wrapper);
	}

	/**
	 * Add module js
	 *
	 * @access protected
	 * @param string $js
	 * @param string $hook
	 * @return void
	 */
	protected function addJS($js, $hook = 'add-js')
	{
		$this->addAction($hook, function() use($js) {
			$tpl = $this->applyFilter('module-view-js','system/js');
			$this->render(['js' => "{$this->getModulesUrl()}/{$js}"],$tpl);
		});
	}

	/**
	 * Add module css
	 *
	 * @access protected
	 * @param string $css
	 * @param string $hook
	 * @return void
	 */
	protected function addCSS($css, $hook = 'add-css')
	{
		$this->addAction($hook, function() use($css){
			$tpl = $this->applyFilter('module-view-css','system/css');
			$this->render(['css' => "{$this->getModulesUrl()}/{$css}"],$tpl);
		});
	}

	/**
	 * Get modules routes
	 *
	 * @access private
	 * @param void
	 * @param array
	 */
	private function loadModules()
	{
		foreach ( $this->getModules() as $name ) {
			$json = new Json("{$name}/module.json");
			Validator::checkModuleConfig($json);
			$config = $json->parse();
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
