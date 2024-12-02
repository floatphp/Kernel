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

use FloatPHP\Helpers\Framework\Debugger;
use FloatPHP\Interfaces\Classes\RouterInterface;

final class Middleware
{
	use TraitConfiguration;
	use TraitException;

	/**
	 * @access private
	 */
	private $match;

	/**
	 * Middleware routing system.
	 *
	 * @param RouterInterface $router
	 * @see $router->addMatchTypes(['name' => 'regex']);
	 */
	public function __construct(RouterInterface $router)
	{
		// Init configuration
		$this->initConfig();

		// Prepare router from config
		$router->setBase($this->getBaseRoute());

		// Set global router
		$router->addRoutes($this->getRoutes());

		// Set modules router
		$module = new Module();
		$router->addRoutes($module->getModulesRoutes());

		// Match request
		$this->match = $router->match();
	}

	/**
	 * Dispatch request (route) and kill execution.
	 *
	 * @access public
	 * @return void
	 */
	public function dispatch() : void
	{
		if ( $this->match ) {

			if ( $this->isCallable() ) {
				// Callable
				$this->doCallable();

			} elseif ( $this->isClassMethod() ) {
				// Class method
				$this->doInstance();

			} elseif ( $this->isModule() ) {
				// Module class
				$this->doModuleInstance();
			}

		} else {
			$this->throwError(404);
		}

		if ( !Debugger::enabled() ) exit();
	}

	/**
	 * Whether controller is a function.
	 *
	 * @access private
	 * @return bool
	 */
	private function isCallable() : bool
	{
		if ( $this->isType('callable', $this->match['target']) && !$this->isModule() ) {
			return true;
		}
		return false;
	}

	/**
	 * Whether controller is a class.
	 *
	 * @access private
	 * @return bool
	 */
	private function isClassMethod() : bool
	{
		if ( !$this->isType('callable', $this->match['target']) && !$this->isModule() ) {
			return true;
		}
		return false;
	}

	/**
	 * Execute callable.
	 *
	 * @access private
	 * @return void
	 */
	private function doCallable() : void
	{
		if ( $this->isType('function', $this->match['target']) ) {
			$this->match['target']($this->parseVar());
		}
	}

	/**
	 * Instance controller.
	 *
	 * @access private
	 * @return void
	 */
	private function doInstance() : void
	{
		// Parse
		$class = $this->parseClass();
		$method = $this->parseMethod();
		$var = $this->parseVar();
		$role = $this->parsePermissions();

		// Secure access
		$instance = new $class();
		if ( !$instance->hasAccess() ) {
			$instance->throwError(406);
		}

		// Match instance with request
		if ( $this->isFrontController($class) ) {
			$instance->$method($var);

		} elseif ( $this->isBackendController($class) ) {
			if ( $instance->isAuthenticated() ) {
				if ( $instance->hasPermissions($role) ) {
					$instance->$method($var);

				} else {
					$instance->throwError(403);
				}

			} else {
				$this->redirect($this->getLoginUrl());
			}

		} elseif ( $this->isAuthController($class) ) {
			if ( $instance->isAuthenticated() ) {
				$this->redirect($this->getAdminUrl());

			} else {
				$instance->$method($var);
			}

		} elseif ( $this->isApiController($class) ) {
			if ( $instance->isHttpAuthenticated() ) {
				$instance->$method($var);

			} else {
				$this->setResponse('Authorization Required', [], 'error', 401);
			}
		}
	}

	/**
	 * Instance module.
	 *
	 * @access private
	 * @return void
	 */
	private function doModuleInstance() : void
	{
		// Parse
		$class = $this->parseModuleClass();
		$method = $this->parseMethod();
		$var = $this->parseVar();
		$role = $this->parsePermissions();

		// Secure access
		$instance = new $class();
		if ( !$instance->hasAccess() ) {
			$instance->throwError(406);
		}

		// Match instance with request
		if ( $this->isFrontController($class) ) {
			$instance->$method($var);

		} elseif ( $this->isApiController($class) ) {
			if ( $instance->isHttpAuthenticated() ) {
				$instance->$method($var);

			} else {
				$this->setResponse('Authorization Required', [], 'error', 401);
			}

		} else {
			if ( $instance->isAuthenticated() ) {
				if ( $instance->hasPermissions($role) ) {
					$instance->$method($var);

				} else {
					$instance->throwError(403);
				}

			} else {
				$this->redirect($this->getLoginUrl());
			}
		}
	}

	/**
	 * Check front controller.
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
	 * Check backend controller.
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
	 * Check API controller.
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
	 * Check auth controller.
	 *
	 * @access private
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isAuthController($class) : bool
	{
		if ( $this->hasObject('parent', $class, __NAMESPACE__ . '\AbstractAuthController') ) {
			return true;

		} elseif ( $this->hasAuthMiddlewareInterface($class) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check front class.
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isFrontClass($class) : bool
	{
		return $this->hasObject('parent', $class, __NAMESPACE__ . '\FrontController');
	}

	/**
	 * Check backend class.
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isBackendClass($class) : bool
	{
		return $this->hasObject('parent', $class, __NAMESPACE__ . '\BackendController');
	}

	/**
	 * Check API class.
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function isApiClass($class) : bool
	{
		return $this->hasObject('parent', $class, __NAMESPACE__ . '\ApiController');
	}

	/**
	 * Check backend interface.
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function hasBackendInterface($class) : bool
	{
		return $this->hasObject('interface', $class, 'BackendInterface');
	}

	/**
	 * Check front interface.
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function hasFrontInterface($class) : bool
	{
		if ( $this->hasObject('interface', $class, 'FrontInterface') ) {
			return true;
		}
		return false;
	}

	/**
	 * Check API interface.
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function hasApiInterface($class) : bool
	{
		return $this->hasObject('interface', $class, 'ApiInterface');
	}

	/**
	 * Check authentication middleware interface.
	 *
	 * @access private
	 * @param string $class
	 * @return bool
	 */
	private function hasAuthMiddlewareInterface($class) : bool
	{
		return $this->hasObject('interface', $class, 'AuthMiddlewareInterface');
	}

	/**
	 * Check module.
	 *
	 * @access private
	 * @return bool
	 */
	private function isModule() : bool
	{
		$module = $this->lowercase($this->match['target']);
		if ( $this->hasString($module, 'module') ) {
			return true;
		}
		return false;
	}

	/**
	 * Parse module class.
	 *
	 * @access private
	 * @return string
	 */
	private function parseModuleClass() : string
	{
		$target = explode('@', $this->match['target']);
		$class = $target[0] ?? false;
		if ( !$class ) {
			$this->throwError(404);
		}
		$module = $this->removeString('Module', $class);
		return $this->getModuleNamespace() . "{$module}\\{$class}";
	}

	/**
	 * Parse class.
	 *
	 * @access private
	 * @return string
	 */
	private function parseClass() : string
	{
		$target = explode('@', $this->match['target']);
		$class = $target[0] ?? false;
		if ( !$class ) {
			$this->throwError(404);
		}
		return "{$this->getControllerNamespace()}{$class}";
	}

	/**
	 * Parse method.
	 *
	 * @access private
	 * @return string
	 */
	private function parseMethod() : string
	{
		$target = explode('@', $this->match['target']);
		return $target[1] ?? 'index';
	}

	/**
	 * Parse permissions.
	 *
	 * @access private
	 * @return mixed
	 */
	private function parsePermissions() : mixed
	{
		return $this->match['permissions'] ?? false;
	}

	/**
	 * Parse request var.
	 *
	 * @access private
	 * @return mixed
	 */
	private function parseVar() : mixed
	{
		$var = null;
		if ( !empty($this->match['params']) ) {
			if ( count($this->match['params']) > 1 ) {
				$var = $this->mergeArray($this->match['params']);

			} elseif ( count($this->match['params']) == 1 ) {
				$key = key($this->match['params']);
				$var = $this->match['params'][$key];
			}
		}
		return $var;
	}
}
