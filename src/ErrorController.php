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

use FloatPHP\Classes\Http\Response;

class ErrorController extends FrontController
{
	/**
	 * @param int $code
	 * @param string $message
	 * @param string $error
	 * @param bool $render
	 * @return void
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

			$type     = $this->applyFilter('error-response-type', 'text/html;charset=utf-8');
			$template = $this->applyFilter('error-template', '/system/error');
			$error    = $this->applyFilter('error', $this->translate($error));

			Response::setHttpHeaders($code, $type);
			$this->render([
				'status' => $message,
				'code'   => $code,
				'error'  => $error
			], $template);
			die();

		} else {

			$args = $this->applyFilter('error-http-args', []);
			Response::set($error, $args, 'error', $code);
		}
	}
}
