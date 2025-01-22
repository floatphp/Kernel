<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.4.x
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
	 * @accees public
	 * @param int $code
	 * @param ?string $error
	 * @param bool $render, Render error message
	 */
	public function __construct(int $code = 404, ?string $error = null, bool $render = true)
	{
		$message = $this->applyFilter('error-message', Response::getMessage($code));

		if ( !$error ) {
			$error = $message;
		}

		if ( $render ) {

			$type = $this->applyFilter('error-response-type', 'text/html;charset=utf-8');
			$file = $this->applyFilter('error-template', '/system/error');
			$error = $this->applyFilter('error', $this->translate($error));

			Response::setHttpHeader($code, $type);
			$this->render($file, [
				'status' => $message,
				'code'   => $code,
				'error'  => $error
			]);
			exit();

		} else {

			$args = $this->applyFilter('error-http-args', []);
			Response::set($error, $args, 'error', $code);
		}
	}
}
