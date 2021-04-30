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

use FloatPHP\Classes\Filesystem\Json;

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
		foreach ( $this->getModules() as $name ) {
			$json = new Json("{$name}/module.json");
			$config = $json->parse();
			$module = $this->getModuleNamespace();
			$module .= "{$config->namespace}\\{$config->namespace}Module";
			new $module;
		}
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
				foreach ($config['router'] as $route) {
					$wrapper[] = $route;
				}
			}
		}
		return $wrapper;
	}

	/**
	 * @access protected
	 * @param string $js
	 * @return void
	 */
	protected function addJS($js)
	{
		$this->addAction('add-js', function() use($js) {
			parent::assign(['js' => "{$this->getModulesPath()}/{$js}"],'system/js');
		});
	}

	/**
	 * @access protected
	 * @param string $css
	 * @return void
	 */
	protected function addCSS($css)
	{
		$this->addAction('add-css', function() use($css){
			parent::assign(['css' => "{$this->getModulesPath()}/{$css}"],'system/css');
		});
	}

    /**
     * Get overrided view path
     *
     * @access protected
     * @param void
     * @return string
     */
    protected function getOverridedViewPath()
    {
        return $this->getModulesPath();
    }
}
