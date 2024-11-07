<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.2.x
 * @copyright  : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Interfaces\Kernel\{
    ViewInterface, CallableInterface
};

class View extends Base implements ViewInterface
{
    use \FloatPHP\Helpers\Framework\inc\TraitViewable;

    /**
     * @access private
     * @var array $callables
     * @var array $content
     */
    private $callables = [];
    private $content = [];

	/**
	 * @inheritdoc
	 */
	public function setCallables(?CallableInterface $callable = null)
	{
        $default   = $this->getDefaultCallables();
        $callables = ($callable) ? $callable->getCallables() : [];
		$this->callables = $this->mergeArray($default, $callables);
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
     * @inheritdoc
     */
    public function render(string $file = 'default', array $content = [], bool $end = false)
    {
        echo $this->assign($file, $content);
        if ( $end ) {
            die;
        }
    }

	/**
	 * @inheritdoc
	 */
	public function assign(string $file = 'default', array $content = []) : string
	{
        // Get environment
        $env = $this->getEnvironment($this->getPath(), [
            'cache' => "{$this->getCachePath()}/view",
            'debug' => $this->isDebug()
        ]);

        // Set callables
        if ( !$this->callables ) {
            $this->setCallables();
        }

        // Load callables
        foreach ($this->callables as $name => $callable) {
            $env->addFunction($this->extend($name, $callable));
        }

        // Return rendered view
        try {
            $view = $env->load("{$file}{$this->getViewExtension()}");
            $content = $this->mergeArray($this->content, $content);
            return $view->render($content);

        } catch (\Exception | \RuntimeException $e) {
            if ( $this->isDebug() ) {
                die($e->getMessage());
            }
            $this->clearLastError();
        }

        return '{}';
	}

    /**
     * Get default callables.
     *
     * @access protected
     * @return array
     */
    protected function getDefaultCallables() : array
    {
        $global = [
			'dump' => function($var) {
                var_dump($var);
            },
			'die' => function(?string $string = null) {
                die($string);
            },
			'isDebug' => function() : bool {
                return $this->isDebug();
            },
			'getConfig' => function(?string $key = null) {
                return $this->getConfig($key);
            },
			'getRoot' => function(?string $sub = null) : string {
                return $this->getRoot($sub);
            },
            'getBaseRoute' => function() {
                return $this->getBaseRoute(false);
            },
            'getFrontUploadUrl' => function() {
                return $this->getFrontUploadUrl();
            },
            'getLoginUrl' => function() {
                return $this->getLoginUrl();
            },
            'getAdminUrl' => function() {
                return $this->getAdminUrl();
            },
            'getVerifyUrl' => function() {
                return $this->getVerifyUrl();
            },
			'getBaseUrl' => function() : string {
                return $this->getBaseUrl();
            },
			'getAssetUrl' => function() : string {
                return $this->getAssetUrl();
            },
            'getTimeout' => function() {
                return $this->getTimeout();
            },
            'getToken' => function($source = '') {
                return $this->getToken($source);
            },
            'getLanguage' => function() {
                return $this->getLanguage();
            },
            'isValidSession' => function() {
                return $this->isValidSession();
            },
            'hasRole' => function($role = null, $userId = null) {
                return $this->hasRole($role, $userId);
            },
            'hasCapability' => function($capability = null, $userId = null) {
                return $this->hasCapability($capability, $userId);
            },
			'translate' => function(?string $string) : string {
                return $this->translate($string);
            },
			'translateVar' => function(string $string, $vars = null) : string {
                return $this->translateVar($string, $vars);
            },
			'unJson' => function(string $value, bool $isArray = false) {
                return $this->decodeJson($value, $isArray);
            },
			'toJson' => function($value) {
                return $this->encodeJson($value);
            },
			'serialize' => function($value) {
                return $this->serialize($value);
            },
			'unserialize' => function(string $value) {
                return $this->unserialize($value);
            },
			'limitString' => function(?string $string, int $limit) {
                return $this->limitString($string, $limit);
            },
			'hasFilter' => function(string $hook, $callback = false) {
                return $this->hasFilter($hook, $callback);
            },
			'applyFilter' => function(string $hook, $value, ...$args) {
                return $this->applyFilter($hook, $value, ...$args);
            },
			'hasAction' => function(string $hook, $callback = false) {
                return $this->hasAction($hook, $callback);
            },
			'doAction' => function(string $hook, ...$args) {
                $this->doAction($hook, ...$args);
            },
			'doShortcode' => function($shortcode = '', $ignoreHTML = false) {
                $this->doShortcode($shortcode, $ignoreHTML);
            }
        ];

        if ( $this->isAdmin() ) {
            return $this->mergeArray([], $global);
        }

        return $this->mergeArray([
			'exit' => function(?int $status = null) {
                exit($status);
            },
			'getSession' => function(?string $key = null) {
                return $this->getSession($key);
            }
        ], $global);
    }

    /**
     * Get template path (Overridden),
     * [Filter: template-path].
     *
     * @access protected
     * @return mixed
     */
    protected function getPath()
    {
        $path = $this->getViewPath();
        return $this->applyFilter('template-path', $path);
    }
}
