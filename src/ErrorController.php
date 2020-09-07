<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.1.0
 * @category  : PHP framework
 * @copyright : (c) JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 *
 * This file if a part of FloatPHP Framework
 */

namespace floatPHP\Kernel;

use floatPHP\Classes\Http\Status;

class ErrorController extends FrontController
{
	/**
	 * @access private
	 */
	private const CODE = 404;

	/**
	 * @param string $code 404
	 * @param string $message null
	 * @return void
	 */
	public function __construct($code = self::CODE, $message = null)
	{
		$this->initConfig();
		if (!$message) {
			$message = Status::getMessage($code);
		}
		header("HTTP/1.1 {$code} {$message}");
		$this->render([
			'code'    => $code,
			'message' => $message
		],'/system/error');
		exit();
	}
}
