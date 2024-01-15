<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.1.0
 * @copyright  : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

class BackendController extends BaseController
{
	/**
	 * Check whether user (current) has permissions.
	 * 
	 * @access public
	 * @param mixed $role
	 * @return bool
	 */
	public function hasPermissions($role = false) : bool
	{
		if ( $this->isPermissions() ) {
			if ( $role ) {
				return $this->hasRole($role);
			}
		}
		return true;
	}

	/**
	 * User logout.
	 * 
	 * @access public
	 * @return void
	 */
	public function logout()
	{
		$this->verifyRequest();
		$this->clearCookie();
		$this->endSession();
		$this->redirect($this->getLoginUrl());
	}
}
