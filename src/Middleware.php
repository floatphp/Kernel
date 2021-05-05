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
	 * @param RouterInterface $router
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
		$module = new Module();
		$router->addRoutes($module->getModulesRoutes());

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
			// Callable
			if ( $this->isCallable() ) {
				$this->doCallable();
			}
			// Class method
			elseif ( $this->isClassMethod() ) {
				$this->doInstance();
			}
			// Module class
			elseif ( $this->isModule() ) {
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
	 * @param void
	 * @return void
	 */
	private function doCallable()
	{
		if ( TypeCheck::isFunction($this->match['target']) ) {
			if ( $this->hasParameter() ) {
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
	 * @param void
	 * @return void
	 */
	private function doInstance()
	{
		$class = $this->parseClass();
		$method = $this->parseMethod();

		// With parameter
		if ( $this->hasParameter() ) {
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
				$instance = new $class();
				if ( $instance->isAuthenticated() ) {
					$instance->$method($var);
				} else {
					header("Location: {$this->getLoginUrl()}");
				}

			} elseif ( $this->isAuthMiddleware($class) ) {
				$instance = new $class;
				if ( $instance->isAuthenticated() ) {
					header("Location: {$this->getAdminUrl()}");
				} else {
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
				$instance = new $class();
				if ( $instance->isAuthenticated() ) {
					$instance->$method();
				} else {
					header("Location: {$this->getLoginUrl()}");
				}

			} elseif ( $this->isAuthMiddleware($class) ) {
				$instance = new $class();
				if ( $instance->isAuthenticated() ) {
					header("Location: {$this->getAdminUrl()}");
				} else {
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
	 * @param void
	 * @return void
	 */
	private function doModuleInstance()
	{
		$class = $this->parseModuleClass();
		$method = $this->parseMethod();

		// With parameter
		if ( $this->hasParameter() ) {
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

			} elseif ( $this->isApiController($class) ) {
				$instance = new $class();
				if ( $instance->isHttpAuthenticated() ) {
					$instance->$method($var);
				} else {
					Response::set('Authorization Required',[],'error',401);
				}
			} else {
				$instance = new $class();
				if ( $instance->isAuthenticated() ) {
					$instance->$method($var);
				} else {
					header("Location: {$this->getLoginUrl()}");
				}
			}
		}

		// Without parameter
		else {
			if ( $this->isFrontController($class) ) {
				$instance = new $class();
				$instance->$method();

			} elseif ( $this->isApiController($class) ) {
				$instance = new $class();
				if ( $instance->isHttpAuthenticated() ) {
					$instance->$method();
				} else {
					Response::set('Authorization Required',[],'error',401);
				}
			} else {
				$instance = new $class();
				if ( $instance->isAuthenticated() ) {
					$instance->$method();
				} else {
					header("Location: {$this->getLoginUrl()}");
				}
			}
		}
	}

	/**
	 * 404
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
	 * Is front controller
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isFrontController($class)
	{
		if ( !$this->isAuthMiddleware($class) ) {
			if ( $this->isFrontClass($class) || $this->hasFrontInterface($class) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Is backend controller
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isBackendController($class)
	{
		if ( !$this->isAuthMiddleware($class) ) {
			if ( $this->isBackendClass($class) || $this->hasBackendInterface($class) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Is API controller
	 *
	 * @access private
	 * @param string $class
	 * @return void
	 */
	private function isApiController($class)
	{
		if ( !$this->isAuthMiddleware($class) ) {
			if ( $this->isApiClass($class) || $this->hasApiInterface($class) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Is auth middleware class
	 *
	 * @access private
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isAuthMiddleware($class)
	{
		if ( TypeCheck::isSubClassOf($class,__NAMESPACE__ . '\AbstractAuthMiddleware') ) {
			return true;

		} elseif ( $this->hasAuthMiddlewareInterface($class) ) {
			return true;
		}
		return false;
	}

	/**
	 * Is front class
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isFrontClass($class)
	{
		if ( TypeCheck::isSubClassOf($class,__NAMESPACE__ . '\FrontController') ) {
			return true;
		}
		return false;
	}

	/**
	 * Is backend class
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isBackendClass($class)
	{
		if ( TypeCheck::isSubClassOf($class,__NAMESPACE__ . '\BackendController') ) {
			return true;
		}
		return false;
	}

	/**
	 * Is API class
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isApiClass($class)
	{
		if ( TypeCheck::isSubClassOf($class,__NAMESPACE__ . '\ApiController') ) {
			return true;
		}
		return false;
	}

	/**
	 * Has backend interface
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function hasBackendInterface($class)
	{
		$interface = 'FloatPHP\Interfaces\Kernel';
		if ( TypeCheck::hasInterface($class,$interface . '\BackendInterface') ) {
			return true;
		}
		return false;
	}

	/**
	 * Has front interface
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function hasFrontInterface($class)
	{
		$interface = 'FloatPHP\Interfaces\Kernel';
		if ( TypeCheck::hasInterface($class,$interface . '\FrontInterface') ) {
			return true;
		}
		return false;
	}

	/**
	 * Has API interface
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function hasApiInterface($class)
	{
		$interface = 'FloatPHP\Interfaces\Kernel';
		if ( TypeCheck::hasInterface($class,$interface . '\ApiInterface') ) {
			return true;
		}
		return false;
	}

	/**
	 * Has authentication middleware interface
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function hasAuthMiddlewareInterface($class)
	{
		$interface = 'FloatPHP\Interfaces\Kernel';
		if ( TypeCheck::hasInterface($class,$interface . '\AuthMiddlewareInterface') ) {
			return true;
		}
		return false;
	}

	/**
	 * Is module
	 *
	 * @access private
	 * @param void
	 * @return bool
	 */
	private function isModule()
	{
		$module = Stringify::lowercase($this->match['target']);
		if ( Stringify::contains($module,'module') ) {
			return true;
		}
		return false;
	}

	/**
	 * Parse class
	 *
	 * @access private
	 * @param void
	 * @return string
	 */
	private function parseClass()
	{
		$target = explode('@',$this->match['target']);
		$class = isset($target[0]) ? $target[0] : false;
		if ( !$class ) {
			$this->do404();
		}
		return "{$this->getControllerNamespace()}{$class}";
	}

	/**
	 * Parse module class
	 *
	 * @access private
	 * @param void
	 * @return string
	 */
	private function parseModuleClass()
	{
		$target = explode('@',$this->match['target']);
		$class = isset($target[0]) ? $target[0] : false;
		if ( !$class ) {
			$this->do404();
		}
		$module = Stringify::replace('Module','',$class);
		return $this->getModuleNamespace() . "{$module}\\{$class}";
	}

	/**
	 * Parse method
	 *
	 * @access private
	 * @param void
	 * @return string
	 */
	private function parseMethod()
	{
		$target = explode('@',$this->match['target']);
		return isset($target[1]) ? $target[1] : 'index';
	}
}
