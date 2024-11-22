<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.3.x
 * @copyright  : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Interfaces\Kernel\{ViewInterface, CallableInterface};

/**
 * View controller.
 *
 * - Hooking
 * - Rendering
 * - Authentication
 * - Configuration
 * - Translation
 * - Formatting
 * - IO
 * - Caching
 * - Requesting
 * - Viewing
 * - Throwing
 */
class View extends Base implements ViewInterface
{
    use \FloatPHP\Helpers\Framework\inc\TraitViewable;

    /**
     * @access private
     * @var array $callables, Custom view functions
     * @var array $content, Global view content
     */
    private $callables = [];
    private $content = [];

    /**
     * @inheritdoc
     */
    public function setCallables(?CallableInterface $callable = null) : void
    {
        $default = $this->getDefaultCallables();
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
    protected function setContent(array $content = []) : void
    {
        $this->content = $content;
    }

    /**
     * @inheritdoc
     */
    public function render(string $file = 'default', array $content = [], bool $end = false) : void
    {
        echo $this->assign($file, $content);
        if ( $end ) die;
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
            'dump'              => function ($var) : void {
                var_dump($var);
            },
            'die'               => function (?string $string = null) : never {
                die($string);
            },
            'isDebug'           => function () : bool {
                return $this->isDebug();
            },
            'getConfig'         => function (?string $key = null) : mixed {
                return $this->getConfig($key);
            },
            'getRoot'           => function (?string $sub = null) : string {
                return $this->getRoot($sub);
            },
            'getBaseRoute'      => function () : string {
                return $this->getBaseRoute(trailingSlash: false);
            },
            'getFrontUploadUrl' => function () : string {
                return $this->getFrontUploadUrl();
            },
            'getLoginUrl'       => function () : string {
                return $this->getLoginUrl();
            },
            'getAdminUrl'       => function () : string {
                return $this->getAdminUrl();
            },
            'getVerifyUrl'      => function () : string {
                return $this->getVerifyUrl();
            },
            'getBaseUrl'        => function () : string {
                return $this->getBaseUrl();
            },
            'getAssetUrl'       => function () : string {
                return $this->getAssetUrl();
            },
            'getTimeout'        => function () : int {
                return $this->getTimeout();
            },
            'getToken'          => function (?string $source = null) : string {
                return $this->getToken(action: $source);
            },
            'getLanguage'       => function () : string {
                return $this->getLanguage();
            },
            'isValidSession'    => function () : bool {
                return $this->isValidSession();
            },
            'hasRole'           => function ($role = null, $userId = null) : bool {
                return $this->hasRole($role, $userId);
            },
            'hasCapability'     => function ($capability = null, $userId = null) {
                return $this->hasCapability($capability, $userId);
            },
            'translate'         => function (?string $string) : string {
                return $this->translate($string);
            },
            'translateVar'      => function (string $string, $vars = null) : string {
                return $this->translateVar($string, $vars);
            },
            'unJson'            => function (string $value, bool $isArray = false) : mixed {
                return $this->decodeJson($value, $isArray);
            },
            'toJson'            => function ($value) : string {
                return $this->encodeJson($value);
            },
            'serialize'         => function ($value) : mixed {
                return $this->serialize($value);
            },
            'unserialize'       => function (string $value) : mixed {
                return $this->unserialize($value);
            },
            'limitString'       => function (?string $string, int $limit) : string {
                return $this->limitString($string, $limit);
            },
            'hasFilter'         => function (string $hook, $callback = false) : bool {
                return $this->hasFilter($hook, $callback);
            },
            'applyFilter'       => function (string $hook, $value, ...$args) : mixed {
                return $this->applyFilter($hook, $value, ...$args);
            },
            'hasAction'         => function (string $hook, $callback = false) : mixed {
                return $this->hasAction($hook, $callback);
            },
            'doAction'          => function (string $hook, ...$args) : void {
                $this->doAction($hook, ...$args);
            },
            'doShortcode'       => function ($shortcode = '', $ignoreHTML = false) : void {
                $this->doShortcode($shortcode, $ignoreHTML);
            }
        ];

        if ( $this->isAdmin() ) {
            return $this->mergeArray([], $global);
        }

        return $this->mergeArray([
            'exit'       => function (?int $status = null) : never {
                exit($status);
            },
            'getSession' => function (?string $key = null) : mixed {
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
    protected function getPath() : mixed
    {
        $path = $this->getViewPath();
        return $this->applyFilter('template-path', $path);
    }
}
