<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.0.0
 * @category  : PHP framework
 * @copyright : (c) JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 */

namespace floatPHP\Kernel;

use floatPHP\Kernel\Module;
use floatPHP\Kernel\Exceptions\MiddlewareException;
use floatPHP\Classes\Http\Router;
use floatPHP\Classes\Auth\Session;

final class Middleware
{
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
		$this->init();
		$this->provide();
	}

	/**
	 * init routing
	 *
	 * @param void
	 * @return void
	 *
	 * $this->router->addMatchTypes(['name'=>'regex']);
	 */
	private function init()
	{
		// get global configuration
		$this->config = new Configuration();

		// prepare router from config
		$this->router = new Router();
		$this->router->setBasePath( $this->config->global->root );

		// set global router
		$this->router->addRoutes( $this->config->routes );
		// set modules router
		$this->router->addRoutes( Module::router() );

		// var_dump(Module::router());die();

		// match request
		$this->match = $this->router->match();
	}

	/**
	 * provide response
	 *
	 * @param void
	 * @return void
	 *
	 */
	private function provide()
	{
		new Session();

		if ( $this->match )
		{
			// callable with paramater
			if ( $this->isCallable() && $this->hasParameter() )
			{
				$this->doCallable(true);
			}
			// callable without paramater
			elseif ( $this->isCallable() && !$this->hasParameter() )
			{
				$this->doCallable();
			}
			// class method with paramater
			elseif ( $this->isClassMethod() && $this->hasParameter() )
			{
				$this->doInstance(true);
			}
			// class method without paramater
			elseif ( $this->isClassMethod() && !$this->hasParameter() )
			{
				$this->doInstance();
			}
			// Module class method without paramater
			elseif ( $this->isModule() && $this->hasParameter() )
			{
				$this->doModuleInstance(true);
			}
			// Module class method without paramater
			elseif ( $this->isModule() && !$this->hasParameter() )
			{
				$this->doModuleInstance();
			}
		}
		else
		{
			$this->do404();
		}
	}

	/**
	 * If controller is a callable or class
	 *
	 * @param void
	 * @return void
	 */
	private function isCallable()
	{
		if ( is_callable($this->match['target']) && !$this->isModule() ) return true;
	}

	/**
	 * If controller is a callable or class
	 *
	 * @param void
	 * @return void
	 */
	private function isClassMethod()
	{
		if ( !is_callable($this->match['target']) && !$this->isModule() ) return true;
	}

	/**
	 * If controller has parameter
	 *
	 * @param void
	 * @return void
	 */
	private function hasParameter()
	{
		if( !empty($this->match['params']) ) return true;
	}

	/**
	 * execute callable, with and without parameter
	 *
	 * @param void
	 * @return void
	 */
	private function doCallable($param = false)
	{
		if (function_exists($this->match['target'])) 
		{
			if ($param) $this->match['target']($this->match['params']);
			else $this->match['target']();
		}
	}

	/**
	 * execute controller, with and without parameter
	 *
	 * @param void
	 * @return void
	 */
	private function doInstance($param = false)
	{
		$target = explode('@', $this->match['target']);
		$class  = '\App\Controllers\\'.$target[0];
		$method = $target[1];

		// With parameter
		if ($param)
		{
			// handle parameters
			if (count($this->match['params']) > 1)
			{
				$var = array_merge($this->match['params']);
			}
			elseif (count($this->match['params']) == 1)
			{
				$key = key($this->match['params']);
				$var = $this->match['params'][$key];
			}

			if ($this->isFrontController($class)) 
			{
				$instance = new $class();
				$instance->$method($var);
			}
			elseif ( $this->isBackendController($class) )
			{
				if ( $this->isAuthenticated() )
				{
					$instance = new $class();
					$instance->$method($var);
				}
				else
				{
					$login = (string)$this->config->global->system->login;
					header("Location: $login");
				}
			}
			elseif ( $this->isAuthMiddleware($class) )
			{
				if ( $this->isAuthenticated() )
				{
					$admin = (string)$this->config->global->system->admin;
					header("Location: $admin");
				}
				else
				{
					$instance = new $class;
					$instance->$method($var);
				}
			}
			elseif ( $this->isApiController($class) ) 
			{
				if ( $this->isHttpAuthenticated() )
				{
					$instance = new $class();
					$instance->$method($var);
				}
			}
		}

		// Without parameter
		else
		{
			if ( $this->isFrontController($class) ) 
			{
				$instance = new $class();
				$instance->$method();
			}
			elseif ( $this->isBackendController($class) )
			{
				if ( $this->isAuthenticated() )
				{
					$instance = new $class();
					$instance->$method();
				}
				else
				{
					$login = (string)$this->config->global->system->login;
					header("Location: $login");
				}
			}
			elseif ( $this->isAuthMiddleware($class) )
			{
				if ( $this->isAuthenticated() )
				{
					$admin = (string)$this->config->global->system->admin;
					header("Location: $admin");
				}
				else
				{
					$instance = new $class();
					$instance->$method();
				}
			}
			elseif ( $this->isApiController($class) ) 
			{
				if ( $this->isHttpAuthenticated() )
				{
					$instance = new $class();
					$instance->$method();
				}
			}
		}
	}

	/**
	 * execute controller, with and without parameter
	 *
	 * @param void
	 * @return void
	 */
	private function doModuleInstance($param = false)
	{
		$target = explode('@', $this->match['target']);
		$module = str_replace('Module', '', $target[0]);
		$class  = "\App\Modules\\{$module}\\" . $target[0];
		$method = $target[1];

		// handle parameters
		if (count($this->match['params']) > 1)
		{
			$var = array_merge($this->match['params']);
		}
		elseif (count($this->match['params']) == 1)
		{
			$key = key($this->match['params']);
			$var = $this->match['params'][$key];
		}

		// With parameter
		if ($param)
		{
			$instance = new $class();
			$instance->$method($var);
		}

		// Without parameter
		else
		{
			$instance = new $class();
			$instance->$method();
		}
	}

	/**
	 * return http message
	 *
	 * @param void
	 * @return mixed
	 */
	private function do404()
	{
		new \floatphp\Kernel\ErrorController('404','Not found');
	}

	/**
	 * Is user authenticated
	 *
	 * @param void
	 * @return true
	 */
	private function isAuthenticated()
	{
		if ( isset($_SESSION['userID']) ) return true;
	}

	/**
	 * Is http authenticated
	 *
	 * @param void
	 * @return true
	 */
	private function isHttpAuthenticated()
	{
		if ( isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) )
		{
			$username = (string)$this->config->global->api->username;
			$password = (string)$this->config->global->api->password;

		    if (($_SERVER['PHP_AUTH_USER'] !== $username) || ($_SERVER['PHP_AUTH_PW'] !== $password))
		    {
			    header('HTTP/1.0 401 Unauthorized');
			    echo 'Authorization Required.';
			    exit;
		    }
		    else
		    {
		    	return true;
		    }
		}
		else
		{
		    header('HTTP/1.0 401 Unauthorized');
		    echo 'Authorization Required.';
		    exit;
		}
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @param string $class
	 * @return true
	 */
	private function isAuthMiddleware($class)
	{
		if ($class == '\App\Controllers\AuthController') return true;
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @param string $class
	 * @return true
	 */
	private function isFrontSubClass($class)
	{
		if ( is_subclass_of($class,'\floatphp\Kernel\FrontController') ) return true;
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @param string $class
	 * @return true
	 */
	private function isBackendSubClass($class)
	{
		if ( is_subclass_of($class,'\floatphp\Kernel\BackendController') ) return true;
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @param string $class
	 * @return true
	 */
	private function isApiSubClass($class)
	{
		if ( is_subclass_of($class,'\floatphp\Kernel\ApiController') ) return true;
	}

	/**
	 * Is AuthMiddleware class
	 *
	 * @param string $class
	 * @return true
	 */
	private function isModuleSubClass($class)
	{
		if ( is_subclass_of($class,'\floatphp\Kernel\Module') ) return true;
	}

	/**
	 * Is FrontController class and not AuthMiddleware
	 *
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
	 * @param string $class
	 * @return void
	 */
	private function isApiController($class)
	{
		if ( !$this->isAuthMiddleware($class) && $this->isApiSubClass($class) ) return true;
	}

	/**
	 * Is BackEndController class but not AuthMiddleware
	 *
	 * @param string $class
	 * @return void
	 */
	private function isModule()
	{
		if ( strpos($this->match['target'],'Module') ) return true;
	}
}
