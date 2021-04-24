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

use FloatPHP\Classes\Filesystem\FileCache;

class Cache extends FileCache
{
	use Configuration;

	/**
	 * @param void
	 */
	public function __construct()
	{
		self::setConfig([
			'path' => $this->getCachePath()
		]);
		parent::__construct();
	}
}
