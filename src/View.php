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

use FloatPHP\Classes\Html\Template;
use FloatPHP\Classes\Http\Session;
use FloatPHP\Classes\Security\Tokenizer;
use FloatPHP\Classes\Filesystem\File;
use FloatPHP\Classes\Filesystem\Json;
use FloatPHP\Classes\Filesystem\Stringify;
use FloatPHP\Classes\Filesystem\Arrayify;
use FloatPHP\Helpers\Framework\Permission;

class View extends Base
{
    /**
     * @access protected
     * @var array $callables
     * @var array $content
     */
    protected $callables = false;
    protected $content = [];

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
     * Define global content
     *
     * @access protected
     * @param array $content
     * @return void
     */
    protected function setContent($content = [])
    {
        $this->content = $content;
    }

    /**
     * Render view
     *
     * @access protected
	 * @param array $content
     * @param string $tpl
     * @return void
     */
    protected function render($content = [], $tpl = 'system/default')
    {
        echo $this->assign(Arrayify::merge($this->content,$content),$tpl);
    }

	/**
	 * Aassign content to view
	 *
     * @access protected
	 * @param array $content
     * @param string $tpl
	 * @return string
	 */
	protected function assign($content = [], $tpl = 'system/default')
	{
        // Set View environment
        $path = $this->applyFilter('view-path',$this->getViewPath());
        $env = Template::getEnvironment($path,[
            'cache' => "{$this->getCachePath()}/view",
            'debug' => $this->isDebug()
        ]);

        // Set custom callables
        if ( $this->callables ) {
            foreach ($this->callables as $name => $callable) {
                $env->addFunction(Template::extend($name, $callable));
            }
        }
    
		// Add view global functions
        $env->addFunction(Template::extend('dump', function ($var){
            var_dump($var);
        }));
        $env->addFunction(Template::extend('die', function ($var = null){
            die($var);
        }));
        $env->addFunction(Template::extend('exit', function ($status = null){
            exit($status);
        }));
        $env->addFunction(Template::extend('getSession', function ($var = null){
            return Session::get($var);
        }));
        $env->addFunction(Template::extend('hasCapability', function ($capability = null, $userId = null){
            return Permission::hasCapability($capability,$userId);
        }));
        $env->addFunction(Template::extend('hasRole', function ($role = null, $userId = null){
            return Permission::hasRole($role,$userId);
        }));
        $env->addFunction(Template::extend('getLanguage', function (){
            return $this->getLanguage();
        }));
        $env->addFunction(Template::extend('isLoggedIn', function (){
			return $this->isLoggedIn();
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
        $env->addFunction(Template::extend('getBaseRoute', function (){
            return $this->getBaseRoute(false);
        }));
        $env->addFunction(Template::extend('getBaseUrl', function (){
            return $this->getBaseUrl();
        }));
        $env->addFunction(Template::extend('getAssetUrl', function (){
            return $this->getAssetUrl();
        }));
        $env->addFunction(Template::extend('getFrontUploadUrl', function (){
            return $this->getFrontUploadUrl();
        }));
        $env->addFunction(Template::extend('getLoginUrl', function (){
            return $this->getLoginUrl();
        }));
        $env->addFunction(Template::extend('getAdminUrl', function (){
            return $this->getAdminUrl();
        }));
        $env->addFunction(Template::extend('getToken', function ($action = ''){
			return $this->getToken($action);
        }));
        $env->addFunction(Template::extend('decodeJSON', function ($json = ''){
            return Json::decode($json);
        }));
        $env->addFunction(Template::extend('encodeJSON', function ($array = []){
            return Json::encode($array);
        }));
        $env->addFunction(Template::extend('serialize', function ($data = []){
            return Stringify::serialize($data);
        }));
        $env->addFunction(Template::extend('unserialize', function ($string = ''){
            return Stringify::unserialize($string);
        }));
        $env->addFunction(Template::extend('doAction', function ($hook = '', $args = []){
            $this->doAction($hook,$args);
        }));
        $env->addFunction(Template::extend('hasAction', function ($hook = '', $args = []){
            $this->hasAction($hook,$args);
        }));
        $env->addFunction(Template::extend('applyFilter', function ($hook = '', $method = ''){
            return $this->applyFilter($hook,$method);
        }));
        $env->addFunction(Template::extend('hasFilter', function ($hook = '', $method = ''){
            return $this->hasFilter($hook,$method);
        }));
        $env->addFunction(Template::extend('doShortcode', function ($shortcode = '', $ignoreHTML = false){
            return $this->doShortcode($shortcode,$ignoreHTML);
        }));
        $env->addFunction(Template::extend('translate', function ($string = ''){
            return $this->translate($string);
        }));

        // Return rendered view
		$view = $env->load("{$tpl}{$this->getViewExtension()}");
		return $view->render(Arrayify::merge($this->content,$content));
	}
}
