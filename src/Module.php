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

use FloatPHP\Classes\Storage\Json;

class Module extends BaseController
{
	/**
	 * @access protected
	 * string $dir modules directory
	 */
	protected static $dir;	

	/**
	 * @access private
	 * string $url Asset url
	 */
	private $url;

	/**
	 * Load modules
	 * @param void
	 * @param void
	 */
	public function __construct()
	{
		self::$dir = $this->getModuleDir();
		$this->autoload();
	}

	/**
	 * Autoload modules
	 * @param void
	 * @param array
	 */
	protected function getModuleDir()
	{
		return glob('App/Modules/*', 1073741824);
	}

	/**
	 * Autoload modules
	 * @param void
	 * @param void
	 */
	private function autoload()
	{
		// load module list
		foreach ( self::$dir as $name )
		{
			$json = new Json("{$name}/module.json"); // module.json
			$config = $json->parse();
			var_dump($config);die();
			$module = "\App\Modules\\{$config->namespace}\\{$config->namespace}Module";
			new $module;
		}
	}

	/**
	 * Set router
	 * @param void
	 * @param void
	 */
	public static function setRouter()
	{
		// load module router list
		$wrapper = [];
		if (self::$dir)
		{
			foreach ( self::$dir as $key => $name )
			{
				$json = new Json("{$name}/module"); // module.json
				$config = $json->parse();
				foreach ($config['router'] as $route) {
					$wrapper[] = $route;
				}
			}
		}
		return $wrapper;
	}

	protected function addJS($url)
	{
		self::hook('action', 'add-js', function() use ($url) {
			parent::render(['src' => $url],'system/script');
		});
	}

	protected function addCSS($url)
	{
		self::hook('action', 'add-css', function() use ($url) {
			parent::render(['href' => $url],'system/style');
		});
	}

    /**
     * Get view path
     *
     * @access protected
     * @param string $template
     * @return string
     */
    protected function getPath($template)
    {
        // Set overriding path
        $override = "{$this->getThemeDir()}/{$this->getNameSpace()}/";
        if ( file_exists("{$override}{$template}{$this->getViewExtension()}") ) {
            return $override;
        }
        return $this->getViewPath();
    }
}
