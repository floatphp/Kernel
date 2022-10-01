<?php
/**
 * @author     : JIHAD SINNAOUR
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.0.0
 * @category   : PHP framework
 * @copyright  : (c) 2017 - 2021 JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link       : https://www.floatphp.com
 * @license    : MIT License
 *
 * This file if a part of FloatPHP Framework
 */

namespace FloatPHP\Kernel;

use FloatPHP\Classes\Http\Session;
use FloatPHP\Classes\Http\Cookie;
use FloatPHP\Helpers\Framework\Permission;

class BackendController extends BaseController
{
	/**
	 * @access public
	 * @param mixed $roles
	 * @return bool
	 */
	public function hasPermissions($roles = false) : bool
	{
		if ( $this->isPermissions() ) {
			if ( $roles ) {
				return Permission::hasRole($roles);
			}
		}
		return true;
	}

	/**
	 * @access public
	 * @param void
	 * @return void
	 */
	public function logout()
	{
		Session::end();
		Cookie::clear();
		die();
	}
}
