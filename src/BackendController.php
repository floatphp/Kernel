<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.4.x
 * @copyright  : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Helpers\Connection\User;
use FloatPHP\Helpers\Framework\Debugger;

class BackendController extends BaseController
{
	/**
	 * @inheritdoc
	 */
	public function __construct(array $content = [])
	{
		// Set view global content
		$id = (int)$this->getSession('userId');
		$content = $this->mergeArray([
			'user'      => (new User)->get($id),
			'execution' => Debugger::getExecutionTime()
		], $content);
		$this->setContent($content);

		// Allow non-blocking requests
		$this->closeSession();
	}

	/**
	 * Check whether user (current) has permissions.
	 *
	 * @access public
	 * @param mixed $role
	 * @return bool
	 */
	public function hasPermissions($role = false) : bool
	{
		if ( $this->isPermissions() && $role ) {
			return $this->hasRole($role);
		}
		return true;
	}

	/**
	 * logout.
	 *
	 * @access public
	 * @return void
	 */
	public function logout() : void
	{
		$this->verifyRequest();
		$this->clearCookie();
		$this->endSession();
		$this->redirect($this->getLoginUrl());
	}
}
