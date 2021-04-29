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

use FloatPHP\Classes\Auth\Session;
use FloatPHP\Classes\Filesystem\TypeCheck;
use FloatPHP\Classes\Filesystem\Stringify;
use FloatPHP\Classes\Http\Server;
use FloatPHP\Classes\Http\Response;
use FloatPHP\Interfaces\Classes\RouterInterface;

final class Middleware
{
	use Configuration;

	/**
	 * @access private
	 */
	private $match;

	/**
	 * Middleware system
	 * $router->addMatchTypes(['name'=>'regex']);
	 *
	 * @param object $router
	 */
	public function __construct(RouterInterface $router)
	{
		// Init configuration
		$this->initConfig();

		// prepare router from config
		$router->setBasePath($this->getBaseRoute());
		// set global router
		$router->addRoutes($this->getRoutes());
		// set modules router
		// $router->addRoutes( Module::setRouter() );
		// match request
		$this->match = $router->match();

		// Provide response
		$this->provide();
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
	 * @return bool
	 */
	private function isCallable()
	{
		if ( TypeCheck::isCallable($this->match['target']) && !$this->isModule() ) {
			return true;
		}
		return false;
	}

	/**
	 * If controller is a class
	 *
	 * @access private
	 * @param void
	 * @return bool
	 */
	private function isClassMethod()
	{
		if ( !TypeCheck::isCallable($this->match['target']) && !$this->isModule() ) {
			return true;
		}
		return false;
	}

	/**
	 * If controller has parameter
	 *
	 * @access private
	 * @param void
	 * @return bool
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
		$class  = $this->getControllerNamespace() . $target[0];
		$method = $target[1];

		// With parameter
		if ( $param ) {
			// handle parameters
			if ( count($this->match['params']) > 1 ) {
				$var = array_merge($this->match['params']);

			} elseif ( count($this->match['params']) == 1 ) {
				$key = key($this->match['params']);
				$var = $this->match['params'][$key];
			}
			if ( $this->isFrontController($class) ) {
				$instance = new $class();
				$instance->$method($var);

			} elseif ( $this->isBackendController($class) ) {
				if ( $this->isAuthenticated() ) {
					$instance = new $class();
					$instance->$method($var);

				} else {
					header("Location: {$this->getLoginUrl()}");
				}

			} elseif ( $this->isAuthMiddleware($class) ) {
				if ( $this->isAuthenticated() ) {
					header("Location: {$this->getAdminUrl()}");

				} else {
					$instance = new $class;
					$instance->$method($var);
				}

			} elseif ( $this->isApiController($class) ) {
				$instance = new $class();
				if ( $instance->isHttpAuthenticated() ) {
					$instance->$method($var);
				} else {
					Response::set('Authorization Required',[],'error',401);
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
					header("Location: {$this->getLoginUrl()}");
				}

			} elseif ( $this->isAuthMiddleware($class) ) {
				if ( $this->isAuthenticated() ) {
					header("Location: {$this->getAdminUrl()}");

				} else {
					$instance = new $class();
					$instance->$method();
				}

			} elseif ( $this->isApiController($class) ) {
				$instance = new $class();
				if ( $instance->isHttpAuthenticated() ) {
					$instance->$method();
				} else {
					Response::set('Authorization Required',[],'error',401);
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
		$module = Stringify::replace('Module','',$target[0]);
		$class  = $this->getModuleNamespace() . "{$module}\\{$target[0]}";
		$method = $target[1];

		// handle parameters
		if ( count($this->match['params']) > 1 ) {
			$var = array_merge($this->match['params']);

		} elseif ( count($this->match['params']) == 1 ) {
			$key = key($this->match['params']);
			$var = $this->match['params'][$key];
		}

		// With parameter
		if ( $param ) {
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
	 * Return 404
	 *
	 * @access private
	 * @param void
	 * @return void
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
	 * @return bool
	 */
	private function isAuthenticated()
	{
		if ( Session::isSetted($this->getSessionId()) ) {
			return true;
		}
		return false;
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @access private
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isAuthMiddleware($class)
	{
		if ( TypeCheck::isSubClassOf($class,__NAMESPACE__ . '\AbstractAuthController') ) {
			return true;
		}
		return false;
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isFrontSubClass($class)
	{
		if ( TypeCheck::isSubClassOf($class,__NAMESPACE__ . '\FrontController') ) {
			return true;
		}
		return false;
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isBackendSubClass($class)
	{
		if ( TypeCheck::isSubClassOf($class,__NAMESPACE__ . '\BackendController') ) {
			return true;
		}
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isApiSubClass($class)
	{
		if ( TypeCheck::isSubClassOf($class,__NAMESPACE__ . '\ApiController') ) {
			return true;
		}
		return false;
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isModuleSubClass($class)
	{
		if ( TypeCheck::isSubClassOf($class,__NAMESPACE__ . '\Module') ) {
			return true;
		}
		return false;
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
		if ( !$this->isAuthMiddleware($class) && $this->isFrontSubClass($class) ) {
			return true;
		}
		return false;
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
		if ( !$this->isAuthMiddleware($class) && $this->isBackendSubClass($class) ) {
			return true;
		}
		return false;
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
		if ( !$this->isAuthMiddleware($class) && $this->isApiSubClass($class) ) {
			return true;
		}
		return false;
	}

	/**
	 * Is Module class but not AuthMiddleware
	 *
	 * @access private
	 * @param void
	 * @return bool
	 */
	private function isModule()
	{
		if ( strpos($this->match['target'],'module') !== false ) {
			return true;
		}
		return false;
	}
}
