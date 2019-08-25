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

namespace floatPHP\Kernel;

use floatPHP\Classes\Storage\Json;

class Module extends BaseController
{
	protected static $dir;
	protected static $config;
	protected $router;
	protected $content = [];

	public function __construct()
	{
		static::$dir = glob('App/Modules/*',GLOB_ONLYDIR);
		$this->autoload();
	}

	private function autoload()
	{
		// load module list
		foreach ( static::$dir as $name )
		{
			$json = new Json("{$name}/module"); // module.json
			$config = $json->parseObject();
			$module = "\App\Modules\\{$config->namespace}\\{$config->namespace}Module";
			new $module;
		}
	}

	public static function router()
	{
		// load module router list
		$wrapper = [];
		if (static::$dir)
		{
			foreach ( static::$dir as $key => $name )
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
		$this->debug = $url;
		self::hook('action', 'add-js', function(){
			self::render(['src' => $this->debug],'system/script');
		});
	}

	protected function addCSS($url)
	{
		$this->debug = $url;
		self::hook('action', 'add-css', function(){
			self::render(['src' => $this->debug],'system/style');
		});
	}

	protected function render($data = [] , $template = null)
	{
		echo $this->assign($data,$template);
	}

	protected function assign($data = [] , $template = null)
	{
		View::setConfig(['path' => 'App/Modules/']);
		return View::assign($data,$template);
	}
}
