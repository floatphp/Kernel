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

class ErrorController extends FrontController
{
	/**
	 * @access public
	 */
	public $error = [];
	public $default = '404';
	/**
	 * @param string $code, string $message
	 * @return void
	 */
	public function __construct($code = null,$message = null)
	{
		if (is_null($code)) $code = $this->default;
		$this->error['code'] = $code;
		$this->error['message'] = $message;
		$this->error['header'] = header("HTTP/1.0 $code $message");
		$this->render( $this->error,"/system/".$this->error['code']);
		exit();
	}
}
