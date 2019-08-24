<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.0.0
 * @category  : PHP framework
 * @copyright : (c) JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 */

namespace floatphp\Kernel;

use App\System\Interfaces\Kernel\ViewInterface;
use App\System\Classes\Html\Hooks;
use Twig_Loader_Filesystem as Loader;
use Twig_Environment as Environment;
use Twig_SimpleFunction as Plugin;

class View implements ViewInterface
{
	/**
	 * @access private
	 */
	private static $env;
	private static $config;

	/**
	 * @access protected
	 */
	protected static $path;
	protected static $cache;
	protected static $debug = false;
	protected static $extension;

	const EXTENSION = '.tpl';

	/**
	 * Set config
	 *
	 * @param array $data
	 * @return void
	 */
	public static function setConfig($config = []): void
	{
		extract($config);
		$setting = new Configuration();
		
		static::$extension = isset($extension)
		? $extension : self::EXTENSION;

		static::$path = isset($path)
		? $path : (string)$setting->global->dir->view;

		static::$cache = isset($cache)
		? $cache : (string)$setting->global->dir->cache;

		static::$debug = isset($debug)
		? $debug : $setting->global->system->debug;

		static::$config = [
		  	'cache' => static::$cache,
		  	'debug' => static::$debug
		];
	}

	/**
	 * Init View
	 *
	 * @access protected
	 * @param void
	 * @return void
	 */
	protected static function init(): void
	{
		$setting = new Configuration();

		if (!static::$extension) {
			static::$extension = self::EXTENSION;
		}

		if (!static::$path) {
			static::$path = (string)$setting->global->dir->view;
		}

		if (!static::$cache) {
			static::$cache = (string)$setting->global->dir->cache;
		}

		if (!static::$debug) {
			static::$debug = $setting->global->system->debug;
		}

		static::$config = [
		  	'cache' => static::$cache,
		  	'debug' => static::$debug
		];

		$loader = new Loader(static::$path);
		static::$env = new Environment($loader, static::$config);
	}

	/**
	 * Assign data to view
	 *
	 * @param array $data, string $view
	 * @return {inherit}
	 */
	protected static function setFunction()
	{
        static::$env->addFunction(
        	new Plugin('doAction', function ($action){
            	$hooks = Hooks::getInstance();
            	$hooks->doAction($action);
        	}
    	));
        static::$env->addFunction(
        	new Plugin('applyFilter', function ($action){
            	// $hooks = Hooks::getInstance();
            	// $hooks->applyFilter($action);
        	}
    	));
	}

	/**
	 * Assign data to view
	 *
	 * @param array $data
	 * @param string $view
	 * @return void
	 */
	public static function render($data, $view): void
	{
		echo self::assign($data, $view);
	}

	/**
	 * Render view
	 *
	 * @param array $data
	 * @param string $view
	 * @return string
	 */
	public static function assign($data = [], $view = 'system/default'): string
	{
		self::init();
		self::setFunction();

		// Reurn rendered view
		$view = static::$env->load($view.static::$extension);
		return $view->render($data);
	}
}
