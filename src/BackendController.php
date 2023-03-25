<?php
/**
 * @author     : JIHAD SINNAOUR
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.0.2
 * @category   : PHP framework
 * @copyright  : (c) 2017 - 2023 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://www.floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Classes\{
    Http\Session, Http\Cookie
};
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
