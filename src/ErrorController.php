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

class ErrorController extends FrontController
{
	/**
	 * @access public
	 */
	const CODE = 404;

	/**
	 * @param int $code
	 * @param string $message
	 * @param string $error
	 * @return void
	 */
	public function __construct($code = self::CODE, $error = null)
	{
		// Init configuration
		$this->initConfig();
		
		Response::setHttpHeaders($code,'text/html; charset=utf-8');
		$this->render([
			'status' => Response::getMessage($code),
			'code'   => $code,
			'error'  => $error
		], '/system/error');
		die();
	}
}
