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

use FloatPHP\Classes\Http\Response;
use FloatPHP\Classes\Filesystem\TypeCheck;
use FloatPHP\Classes\Filesystem\Stringify;
use FloatPHP\Classes\Filesystem\Arrayify;
use FloatPHP\Interfaces\Classes\RouterInterface;

final class Middleware
{
	use TraitConfiguration;

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
	}

	/**
	 * Dispatch request & provide response
	 *
	 * @access public
	 * @param void
	 * @return void
	 */
	public function dispatch()
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
	private function isCallable() : bool
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
	private function isClassMethod() : bool
	{
		if ( !TypeCheck::isCallable($this->match['target']) && !$this->isModule() ) {
			return true;
		}
		return false;
	}

	/**
	 * Execute callable
	 *
	 * @access private
	 * @param void
	 * @return void
	 */
	private function doCallable()
	{
		if ( TypeCheck::isFunction($this->match['target']) ) {
			$this->match['target']($this->parseVar());
		}
	}

	/**
	 * Instance controller
	 *
	 * @access private
	 * @param void
	 * @return void
	 */
	private function doInstance()
	{
		// Parse
		$class = $this->parseClass();
		$method = $this->parseMethod();
		$var = $this->parseVar();
		$roles = $this->parsePermissions();

		// Secure access
		$instance = new $class();
		if ( !$instance->hasAccess() ) {
			$instance->exception(406);
		}

		// Match instance with request
		if ( $this->isFrontController($class) ) {
			$instance->$method($var);

		} elseif ( $this->isBackendController($class) ) {
			if ( $instance->isAuthenticated() ) {
				if ( $instance->hasPermissions($roles) ) {
					$instance->$method($var);
				} else {
					$instance->exception(401);
				}
			} else {
				header("Location: {$this->getLoginUrl()}");
			}

		} elseif ( $this->isAuthController($class) ) {
			if ( $instance->isAuthenticated() ) {
				header("Location: {$this->getAdminUrl()}");
			} else {
				$instance->$method($var);
			}

		} elseif ( $this->isApiController($class) ) {
			if ( $instance->isHttpAuthenticated() ) {
				$instance->$method($var);
			} else {
				Response::set('Authorization Required',[],'error',401);
			}
		}
	}

	/**
	 * Instance module
	 *
	 * @access private
	 * @param void
	 * @return void
	 */
	private function doModuleInstance()
	{
		// Parse
		$class = $this->parseModuleClass();
		$method = $this->parseMethod();
		$var = $this->parseVar();

		// Secure access
		$instance = new $class();
		if ( !$instance->hasAccess() ) {
			$instance->exception(406);
		}

		// Match instance with request
		if ( $this->isFrontController($class) ) {
			$instance->$method($var);

		} elseif ( $this->isApiController($class) ) {
			if ( $instance->isHttpAuthenticated() ) {
				$instance->$method($var);
			} else {
				Response::set('Authorization Required',[],'error',401);
			}

		} else {
			if ( $instance->isAuthenticated() ) {
				$instance->$method($var);
			} else {
				header("Location: {$this->getLoginUrl()}");
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
	private function isFrontController($class) : bool
	{
		if ( !$this->isAuthController($class) ) {
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
	private function isBackendController($class) : bool
	{
		if ( !$this->isAuthController($class) ) {
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
	private function isApiController($class) : bool
	{
		if ( !$this->isAuthController($class) ) {
			if ( $this->isApiClass($class) || $this->hasApiInterface($class) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Is auth controller
	 *
	 * @access private
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isAuthController($class) : bool
	{
		if ( TypeCheck::isSubClassOf($class,__NAMESPACE__ . '\AbstractAuthController') ) {
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
	private function isFrontClass($class) : bool
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
	private function isBackendClass($class) : bool
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
	private function isApiClass($class) : bool
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
	private function hasBackendInterface($class) : bool
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
	private function hasFrontInterface($class) : bool
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
	private function hasApiInterface($class) : bool
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
	private function hasAuthMiddlewareInterface($class) : bool
	{
		$interface = 'FloatPHP\Interfaces\Kernel';
		if ( TypeCheck::hasInterface($class,$interface . '\AuthMiddlewareInterface') ) {
			return true;
		}
		return false;
	}

	/**
	 * Check module
	 *
	 * @access private
	 * @param void
	 * @return bool
	 */
	private function isModule() : bool
	{
		$module = Stringify::lowercase($this->match['target']);
		if ( Stringify::contains($module,'module') ) {
			return true;
		}
		return false;
	}

	/**
	 * Parse module class
	 *
	 * @access private
	 * @param void
	 * @return string
	 */
	private function parseModuleClass() : string
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
	 * Parse class
	 *
	 * @access private
	 * @param void
	 * @return string
	 */
	private function parseClass() : string
	{
		$target = explode('@',$this->match['target']);
		$class = isset($target[0]) ? $target[0] : false;
		if ( !$class ) {
			$this->do404();
		}
		return "{$this->getControllerNamespace()}{$class}";
	}

	/**
	 * Parse method
	 *
	 * @access private
	 * @param void
	 * @return string
	 */
	private function parseMethod() : string
	{
		$target = explode('@',$this->match['target']);
		return isset($target[1]) ? $target[1] : 'index';
	}

	/**
	 * Parse permissions
	 *
	 * @access private
	 * @param void
	 * @return mixed
	 */
	private function parsePermissions()
	{
		return isset($this->match['permissions']) 
		? $this->match['permissions'] : false;
	}

	/**
	 * Parse request var
	 *
	 * @access private
	 * @param void
	 * @return mixed
	 */
	private function parseVar()
	{
		$var = null;
		if ( !empty($this->match['params']) ) {
			if ( count($this->match['params']) > 1 ) {
				$var = Arrayify::merge($this->match['params']);
			} elseif ( count($this->match['params']) == 1 ) {
				$key = key($this->match['params']);
				$var = $this->match['params'][$key];
			}
		}
		return $var;
	}
}
