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

use floatPHP\Classes\Connection\Db;

class Model extends Orm
{
	/**
	 * @param array $data
	 */
	public function __construct($data = [])
	{
		$this->init($data);
	}
}
