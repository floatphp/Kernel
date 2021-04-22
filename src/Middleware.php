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

use FloatPHP\Kernel\Module;
use FloatPHP\Kernel\Exceptions\MiddlewareException;
use FloatPHP\Classes\Http\Router;
use FloatPHP\Classes\Auth\Session;

final class Middleware
{
	use Configuration;

	/**
	 * @access private
	 */
	private $router;
	private $match;
	private $config;

	/**
	 * Middleware system
	 *
	 * @param void
	 * @return void
	 */
	public function __construct()
	{
		$this->initConfig();
		$this->initRouter();
		$this->provide();
	}

	/**
	 * Init routing
	 *
	 * @param void
	 * @return void
	 *
	 * $this->router->addMatchTypes(['name'=>'regex']);
	 */
	private function initRouter()
	{
		// prepare router from config
		$this->router = new Router();
		$this->router->setBasePath($this->getBaseRoute());

		// set global router
		$this->router->addRoutes($this->getRoutes());

		// set modules router
		// $this->router->addRoutes( Module::setRouter() );

		// match request
		$this->match = $this->router->match();
	}

	/**
	 * Provide response
	 *
	 * @access private
	 * @param void
	 * @return void
	 */
	private function provide()
	{
		new Session();
		header('X-Powered-By:floatPHP');

		if ( $this->match ) {
			// callable with paramater
			if ( $this->isCallable() && $this->hasParameter() ) {
				$this->doCallable(true);
			}
			// callable without paramater
			elseif ( $this->isCallable() && !$this->hasParameter() ) {
				$this->doCallable();
			}
			// class method with paramater
			elseif ( $this->isClassMethod() && $this->hasParameter() ) {
				$this->doInstance(true);
			}
			// class method without paramater
			elseif ( $this->isClassMethod() && !$this->hasParameter() ) {
				$this->doInstance();
			}
			// Module class method without paramater
			elseif ( $this->isModule() && $this->hasParameter() ) {
				$this->doModuleInstance(true);
			}
			// Module class method without paramater
			elseif ( $this->isModule() && !$this->hasParameter() ) {
				$this->doModuleInstance();
			}
		} else {
			$this->do404();
		}
	}

	/**
	 * If controller is a function
	 *
	 * @access private
	 * @param void
	 * @return boolean
	 */
	private function isCallable()
	{
		if ( is_callable($this->match['target']) && !$this->isModule() ) {
			return true;
		}
		return false;
	}

	/**
	 * If controller is a class
	 *
	 * @access private
	 * @param void
	 * @return boolean
	 */
	private function isClassMethod()
	{
		if ( !is_callable($this->match['target']) && !$this->isModule() ) {
			return true;
		}
		return false;
	}

	/**
	 * If controller has parameter
	 *
	 * @access private
	 * @param void
	 * @return boolean
	 */
	private function hasParameter()
	{
		if ( !empty($this->match['params']) ) {
			return true;
		}
		return false;
	}

	/**
	 * Execute Callable, with and without parameter
	 *
	 * @access private
	 * @param boolean $param false
	 * @return void
	 */
	private function doCallable($param = false)
	{
		if ( function_exists($this->match['target']) ) {
			if ($param) {
				$this->match['target']($this->match['params']);
			} else {
				$this->match['target']();
			}
		}
	}

	/**
	 * Execute Controller, with and without parameter
	 *
	 * @access private
	 * @param boolean $param
	 * @return void
	 */
	private function doInstance($param = false)
	{
		$target = explode('@', $this->match['target']);
		$class  = '\App\Controllers\\'.$target[0];
		$method = $target[1];

		// With parameter
		if ($param) {
			// handle parameters
			if (count($this->match['params']) > 1) {
				$var = array_merge($this->match['params']);

			} elseif (count($this->match['params']) == 1) {
				$key = key($this->match['params']);
				$var = $this->match['params'][$key];
			}

			if ($this->isFrontController($class)) {
				$instance = new $class();
				$instance->$method($var);

			} elseif ( $this->isBackendController($class) ) {
				if ( $this->isAuthenticated() ) {
					$instance = new $class();
					$instance->$method($var);

				} else {
					$login = $this->getLoginUrl();
					header("Location: $login");
				}

			} elseif ( $this->isAuthMiddleware($class) ) {
				if ( $this->isAuthenticated() ) {
					$admin = $this->getAdminUrl();
					header("Location: $admin");

				} else {
					$instance = new $class;
					$instance->$method($var);
				}

			} elseif ( $this->isApiController($class) ) {
				if ( $this->isHttpAuthenticated() ) {
					$instance = new $class();
					$instance->$method($var);
				}
			}
		}

		// Without parameter
		else {
			if ( $this->isFrontController($class) ) {
				$instance = new $class();
				$instance->$method();

			} elseif ( $this->isBackendController($class) ) {
				if ( $this->isAuthenticated() ) {
					$instance = new $class();
					$instance->$method();

				} else {
					$login = $this->getLoginUrl();
					header("Location: $login");
				}

			} elseif ( $this->isAuthMiddleware($class) ) {
				if ( $this->isAuthenticated() ) {
					$admin = $this->getAdminUrl();
					header("Location: $admin");

				} else {
					$instance = new $class();
					$instance->$method();
				}

			} elseif ( $this->isApiController($class) ) {
				if ( $this->isHttpAuthenticated() ) {
					$instance = new $class();
					$instance->$method();
				}
			}
		}
	}

	/**
	 * Execute Module, with and without parameter
	 *
	 * @access private
	 * @param boolean $param
	 * @return void
	 */
	private function doModuleInstance($param = false)
	{
		$target = explode('@', $this->match['target']);
		$module = str_replace('Module', '', $target[0]);
		$class  = "\App\Modules\\{$module}\\" . $target[0];
		$method = $target[1];

		// handle parameters
		if (count($this->match['params']) > 1) {
			$var = array_merge($this->match['params']);

		} elseif (count($this->match['params']) == 1) {
			$key = key($this->match['params']);
			$var = $this->match['params'][$key];
		}

		// With parameter
		if ($param) {
			$instance = new $class();
			$instance->$method($var);
		}

		// Without parameter
		else {
			$instance = new $class();
			$instance->$method();
		}
	}

	/**
	 * Return http message
	 *
	 * @access private
	 * @param void
	 * @return mixed
	 */
	private function do404()
	{
		new \FloatPHP\Kernel\ErrorController(404);
	}

	/**
	 * Is user authenticated
	 *
	 * @access private
	 * @param void
	 * @return boolean
	 */
	private function isAuthenticated()
	{
		if ( isset($_SESSION[$this->getSession()]) ) {
			return true;
		}
		return false;
	}

	/**
	 * Is http authenticated
	 *
	 * @access private
	 * @param void
	 * @return true
	 */
	private function isHttpAuthenticated()
	{
		if ( isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) ) {
			$username = $this->getApiUsername();
			$password = $this->getApiPassword();

		    if ( ($_SERVER['PHP_AUTH_USER'] !== $username) || ($_SERVER['PHP_AUTH_PW'] !== $password) ) {
			    header('HTTP/1.0 401 Unauthorized');
			    echo 'Authorization Required.';
			    exit;
		    } else {
		    	return true;
		    }

		} else {
		    header('HTTP/1.0 401 Unauthorized');
		    echo 'Authorization Required.';
		    exit;
		}
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @access private
	 * @access private
	 * @param string $class
	 * @return boolean
	 */
	private function isAuthMiddleware($class)
	{
		if ( $class == '\App\Controllers\AuthController' ) {
			return true;
		}
		return false;
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @access private
	 * @param string $class
	 * @return true
	 */
	private function isFrontSubClass($class)
	{
		if ( is_subclass_of($class,'\FloatPHP\Kernel\FrontController') ) {
			return true;
		}
		return false;
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @access private
	 * @param string $class
	 * @return true
	 */
	private function isBackendSubClass($class)
	{
		if ( is_subclass_of($class,'\FloatPHP\Kernel\BackendController') ) {
			return true;
		}
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @access private
	 * @param string $class
	 * @return true
	 */
	private function isApiSubClass($class)
	{
		if ( is_subclass_of($class,'\FloatPHP\Kernel\ApiController') ) return true;
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @access private
	 * @param string $class
	 * @return true
	 */
	private function isModuleSubClass($class)
	{
		if ( is_subclass_of($class,'\FloatPHP\Kernel\Module') ) return true;
	}

	/**
	 * Is FrontController class and not AuthMiddleware
	 *
	 * @access private
	 * @param string $class
	 * @return void
	 */
	private function isFrontController($class)
	{
		if ( !$this->isAuthMiddleware($class) && $this->isFrontSubClass($class) ) return true;
	}

	/**
	 * Is BackEndController class but not AuthMiddleware
	 *
	 * @access private
	 * @param string $class
	 * @return void
	 */
	private function isBackendController($class)
	{
		if ( !$this->isAuthMiddleware($class) && $this->isBackendSubClass($class) ) return true;
	}

	/**
	 * Is BackEndController class but not AuthMiddleware
	 *
	 * @access private
	 * @param string $class
	 * @return void
	 */
	private function isApiController($class)
	{
		if ( !$this->isAuthMiddleware($class) && $this->isApiSubClass($class) ) return true;
	}

	/**
	 * Is Module class but not AuthMiddleware
	 *
	 * @access private
	 * @param void
	 * @return boolean
	 */
	private function isModule()
	{
		if ( strpos($this->match['target'], 'module') !== false ) {
			return true;
		}
		return false;
	}
}
