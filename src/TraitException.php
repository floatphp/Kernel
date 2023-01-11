<?php
/**
 * @author     : JIHAD SINNAOUR
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.0.1
 * @category   : PHP framework
 * @copyright  : (c) 2017 - 2023 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://www.floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Helpers\Filesystem\Logger;

trait TraitException
{
	/**
	 * Handle shutdown exception.
	 *
	 * @access public
	 * @var array $callable
	 * @return void
	 */
	public static function handle($callable)
	{
		register_shutdown_function($callable);
	}

	/**
	 * Get last error.
	 *
	 * @access public
	 * @var void
	 * @return string
	 */
	public static function getLastError()
	{
		return error_get_last();
	}

	/**
	 * Clear last error.
	 *
	 * @access public
	 * @var void
	 * @return void
	 */
	public static function clearLastError()
	{
		error_clear_last();
	}

	/**
	 * Trigger user error.
	 *
	 * @access public
	 * @var string $message
	 * @var int $type
	 * @return bool
	 */
	public static function trigger($message, $type = E_USER_NOTICE)
	{
		return trigger_error($message, $type);
	}
	
	/**
	 * Throw exception.
	 * 
	 * @access public
	 * @param int $code
	 * @param string $message
	 * @return object
	 */
	public function exception($code = 404, $message = '')
	{
		return new ErrorController($code, $message);
	}

    /**
	 * Log user error.
	 *
     * @access public
     * @param string $message
     * @param string $type
     * @param string $path
     * @param array $headers
     * @return string
     */
    public function log($message = '', $type = 0, $path = null, $headers = null)
    {
    	$logger = new Logger();
        $logger->log($message, $type, $path, $headers);
        return $message;
    }
}
