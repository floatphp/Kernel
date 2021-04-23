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

use FloatPHP\Classes\Html\Hooks;
use FloatPHP\Classes\Auth\Session;
use FloatPHP\Classes\Security\Tokenizer;
use FloatPHP\Classes\Filesystem\Json;
use FloatPHP\Classes\Filesystem\Stringify;

class View extends BaseOptions
{
    /**
     * @access protected
     * @var array $callables
     */
    protected $callables = false;

	/**
	 * Define custom callables
	 *
	 * @access protected
     * @param array $callables
	 * @return void
	 */
	protected function setCallables($callables = [])
	{
		$this->callables = $callables;
	}

    /**
     * Render view
     *
     * @access protected
	 * @param array $content
     * @param string $template
     * @return void
     */
    protected function render($content = [], $template = 'system/default')
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
	 * @todo translate function
	 * @todo hook functions
	 */
	protected function assign($content = [], $template = 'system/default')
	{
        // Set View environment
        $env = Template::getEnvironment($this->getOverridePath($template),[
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
        $env->addFunction(Template::extend('isLoggedIn', function (){
			$session = new Session();
			return $session->isRegistered();
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
        $env->addFunction(Template::extend('getBaseUrl', function (){
            return $this->getBaseUrl();
        }));
        $env->addFunction(Template::extend('getAssetUrl', function (){
            return $this->getAssetUrl();
        }));
        $env->addFunction(Template::extend('getLoginUrl', function (){
            return $this->getLoginUrl();
        }));
        $env->addFunction(Template::extend('getAdminUrl', function (){
            return $this->getAdminUrl();
        }));
        $env->addFunction(Template::extend('getToken', function (){
			return $this->getToken();
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
            return Stringify::serialize($data);
        }));
        $env->addFunction(Template::extend('unserialize', function ($string){
            return Stringify::unserialize($string);
        }));
        $env->addFunction(Template::extend('applyFilter', function ($tag, $args = []){
            $hooks = Hooks::getInstance();
            return $hooks->applyFilter($tag, $args);
        }));
        $env->addFunction(Template::extend('doAction', function ($tag, $args = []){
            $hooks = Hooks::getInstance();
            $hooks->doAction($tag, $args);
        }));

        // Return rendered view
		$view = $env->load("{$template}{$this->getViewExtension()}");
		return $view->render($content);
	}

    /**
     * Get view path
     *
     * @access protected
     * @param string $template
     * @return string
     * @todo Override
     */
    protected function getOverridePath($template)
    {
        return $this->getViewPath();
    }
}
