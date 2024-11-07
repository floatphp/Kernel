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

use FloatPHP\Classes\Http\Response;

class ErrorController extends FrontController
{
	/**
	 * @param int $code
	 * @param string $message
	 * @param string $error
	 * @param bool $render
	 * @uses initConfig()
	 * @uses resetConfig()
	 */
	public function __construct($code = 404, $error = null, $render = true)
	{
		// Init configuration
		$this->initConfig();

		$message = $this->applyFilter('error-message', Response::getMessage($code));

		if ( !$error ) {
			$error = $message;
		}

		if ( $render ) {

			$type  = $this->applyFilter('error-response-type', 'text/html;charset=utf-8');
			$file  = $this->applyFilter('error-template', '/system/error');
			$error = $this->applyFilter('error', $this->translate($error));

			Response::setHttpHeaders($code, $type);
			$this->render($file, [
				'status' => $message,
				'code'   => $code,
				'error'  => $error
			]);
			
			die();

		} else {

			$args = $this->applyFilter('error-http-args', []);
			Response::set($error, $args, 'error', $code);
		}

		// Reset configuration
        $this->resetConfig();
	}
}
