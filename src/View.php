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

use FloatPHP\Classes\{
    Filesystem\Exception as ErrorHandler,
};
use FloatPHP\Helpers\Html\Template;

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
	 * Define custom callables.
	 *
	 * @access protected
     * @param array $callables
	 * @return void
	 */
	protected function setCallables(array $callables = [])
	{
		$this->callables = $callables;
	}

    /**
     * Define global content.
     *
     * @access protected
     * @param array $content
     * @return void
     */
    protected function setContent(array $content = [])
    {
        $this->content = $content;
    }

    /**
     * Render view.
     *
     * @access protected
	 * @param array $content
     * @param string $tpl
     * @return void
     */
    protected function render($content = [], $tpl = 'system/default')
    {
        echo $this->assign($content, $tpl);
    }

    /**
     * Aassign content to view.
     *
     * @access protected
	 * @param array $content
     * @param string $tpl
	 * @return string
	 */
	protected function assign($content = [], $tpl = 'system/default') : string
	{
        // Set View environment
        $path = $this->applyFilter('view-path', $this->getViewPath());
        $env = Template::getEnvironment($path, [
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
        $env->addFunction(Template::extend('dump', function($var) {
            var_dump($var);
        }));

        $env->addFunction(Template::extend('die', function($var = null) {
            die($var);
        }));

        $env->addFunction(Template::extend('exit', function($status = null) {
            exit($status);
        }));

        $env->addFunction(Template::extend('getSession', function($var = null) {
            return $this->getSession($var);
        }));

        $env->addFunction(Template::extend('hasRole', function($role = null, $userId = null) {
            return $this->hasRole($role, $userId);
        }));

        $env->addFunction(Template::extend('hasCapability', function($capability = null, $userId = null) {
            return $this->hasCapability($capability, $userId);
        }));

        $env->addFunction(Template::extend('getLanguage', function() {
            return $this->getLanguage();
        }));

        $env->addFunction(Template::extend('isValidSession', function() {
			return $this->isValidSession();
        }));

        $env->addFunction(Template::extend('isDebug', function() {
            return $this->isDebug();
        }));

        $env->addFunction(Template::extend('getConfig', function($config = '') {
            return $this->getConfig($config);
        }));

        $env->addFunction(Template::extend('getRoot', function() {
            return $this->getRoot();
        }));

        $env->addFunction(Template::extend('getBaseRoute', function() {
            return $this->getBaseRoute(false);
        }));

        $env->addFunction(Template::extend('getBaseUrl', function() {
            return $this->getBaseUrl();
        }));

        $env->addFunction(Template::extend('getAssetUrl', function() {
            return $this->getAssetUrl();
        }));

        $env->addFunction(Template::extend('getFrontUploadUrl', function() {
            return $this->getFrontUploadUrl();
        }));

        $env->addFunction(Template::extend('getLoginUrl', function() {
            return $this->getLoginUrl();
        }));

        $env->addFunction(Template::extend('getAdminUrl', function() {
            return $this->getAdminUrl();
        }));

        $env->addFunction(Template::extend('getVerifyUrl', function() {
            return $this->getVerifyUrl();
        }));

        $env->addFunction(Template::extend('getTimeout', function() {
			return $this->getTimeout();
        }));

        $env->addFunction(Template::extend('getToken', function($source = '') {
            return $this->getToken($source);
        }));

        $env->addFunction(Template::extend('decodeJSON', function($json = '') {
            return $this->decodeJson($json);
        }));

        $env->addFunction(Template::extend('encodeJSON', function($array = []) {
            return $this->encodeJson($array);
        }));

        $env->addFunction(Template::extend('serialize', function($data = []) {
            return $this->serialize($data);
        }));

        $env->addFunction(Template::extend('unserialize', function($string = '') {
            return $this->unserialize($string);
        }));

        $env->addFunction(Template::extend('doAction', function($hook = '', $args = []) {
            $this->doAction($hook, $args);
        }));

        $env->addFunction(Template::extend('hasAction', function($hook = '', $args = []) {
            $this->hasAction($hook, $args);
        }));

        $env->addFunction(Template::extend('applyFilter', function($hook = '', $method = '') {
            return $this->applyFilter($hook , $method);
        }));

        $env->addFunction(Template::extend('hasFilter', function($hook = '', $method = '') {
            return $this->hasFilter($hook, $method);
        }));

        $env->addFunction(Template::extend('doShortcode', function($shortcode = '', $ignoreHTML = false) {
            return $this->doShortcode($shortcode, $ignoreHTML);
        }));

        $env->addFunction(Template::extend('translate', function($string = '') {
            return $this->translate($string);
        }));

        // Return rendered view
        try {
            $content = $this->mergeArray($this->content, $content);
            $view = $env->load("{$tpl}{$this->getViewExtension()}");
            return $view->render($content);

        } catch (\Exception $e) {
            if ( $this->isDebug() ) {
                die($e->getMessage());
            }
            ErrorHandler::clearLastError();
        }

        return '{}';
	}
}
