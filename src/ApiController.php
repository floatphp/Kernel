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

namespace floatphp\Kernel;

class ApiController
{
	/**
	 * @access protected
	 */
	protected $api;
	/**
	 * @access public
	 */
	public $name = 'GeneratorAPI';
	public $version = '0.1';
	/**
	 *
	 * @param void
	 * @return json
	 */
	protected function info()
	{
		header('Content-Type: application/json');
		echo json_encode($this->version);
	}
}
