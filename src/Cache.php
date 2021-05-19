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
use FloatPHP\Classes\Filesystem\TypeCheck;

class Cache extends FileCache
{
	use Configuration;

	/**
	 * @param void
	 */
	public function __construct()
	{
		// Init configuration
		$this->initConfig();
		// Set cache configuration
		if ( TypeCheck::isNull(self::$config) ) {
			self::setConfig([
				'path' => "{$this->getCachePath()}/temp"
			]);
		}
		// Set cache TTL
		if ( TypeCheck::isNull(self::$ttl) ) {
			self::expireIn($this->getExpireIn());
		}
		// Instance cache
		parent::__construct();
	}
}
