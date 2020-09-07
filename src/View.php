<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.1.0
 * @category  : PHP framework
 * @copyright : (c) JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 *
 * This file if a part of FloatPHP Framework
 */

namespace floatPHP\Kernel;

// use VanillePlugin\inc\Json;
// use VanillePlugin\inc\Data;
use floatPHP\Classes\Html\Hooks;
use floatPHP\Interfaces\Kernel\ViewInterface;

class View // implements ViewInterface
{
    use Configuration;
    
    /**
     * @access private
     * @var array $callables
     */
    private $callables = false;

	/**
	 * Define custom callables
	 *
	 * @access public
     * @param array $callables
	 * @return void
	 */
	public function setCallables($callables = [])
	{
		$this->callables = $callables;
	}

    /**
     * Render view
     *
     * @access protected
     * @param {inherit}
     * @return void
     */
    protected function render($content = [], $template = 'default')
    {
        echo $this->assign($content, $template);
    }

	/**
	 * Aassign content to view
	 *
     * @access protected
	 * @param array $content
     * @param string $template
	 * @return string
	 */
	protected function assign($content = [], $template = 'default')
	{
        // Set View environment
        $env = Template::getEnvironment($this->getViewPath(), [
            'cache' => $this->getCachePath(),
            'debug' => $this->isDebug()
        ]);

        // Set custom callables
        if ($this->callables) {
            foreach ($this->callables as $name => $callable) {
                $env->addFunction(Template::extend($name, $callable));
            }
        }
    
		// Add view global functions
        $env->addFunction(Template::extend('dump', function ($var){
            var_dump($var);
        }));
        $env->addFunction(Template::extend('isDebug', function (){
            return $this->isDebug();
        }));
        $env->addFunction(Template::extend('getConfig', function ($config){
            return $this->getConfig($config);
        }));
        $env->addFunction(Template::extend('getRoot', function (){
            return $this->getRoot();
        }));
        $env->addFunction(Template::extend('getBaseUri', function (){
            return $this->getBaseUri();
        }));
        $env->addFunction(Template::extend('getAssetUri', function (){
            return $this->getAssetUri();
        }));
        $env->addFunction(Template::extend('translate', function ($string){
            return $this->translateString($string);
        }));
        $env->addFunction(Template::extend('JSONdecode', function ($json){
            return Json::decode($json);
        }));
        $env->addFunction(Template::extend('JSONencode', function ($array){
            return Json::encode($array);
        }));
        $env->addFunction(Template::extend('exit', function (){
            exit;
        }));
        $env->addFunction(Template::extend('serialize', function ($data){
            return Data::serialize($data);
        }));
        $env->addFunction(Template::extend('unserialize', function ($string){
            return Data::unserialize($string);
        }));
        $env->addFunction(Template::extend('hasFilter', function ($hook){
            return $this->hasFilter($hook);
        }));
        $env->addFunction(Template::extend('applyFilter', function ($hook, $value){
            $hooks = Hooks::getInstance();
            return $hooks->applyFilter($action);
        }));
        $env->addFunction(Template::extend('doAction', function ($hook, $args = null){
            $hooks = Hooks::getInstance();
            return $hooks->doAction($action);
        }));

        // Return rendered view
		$view = $env->load("{$template}{$this->getViewExtension()}");
		return $view->render($content);
	}
}
