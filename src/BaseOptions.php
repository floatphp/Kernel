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

use FloatPHP\Classes\Auth\Session;
use FloatPHP\Classes\Security\Tokenizer;
use FloatPHP\Classes\Html\Hooks;

class BaseOptions
{
	use Configuration;

    /**
     * @access protected
     * @param void
     * @param mixed
     */
	protected function getToken()
	{
		$token = new Tokenizer();
		$session = new Session();
		$generated = false;
		if ( $session->isRegistered() ) {
			$generated = $token->generate(10);
			$session->set('private-token',$generated);
		} else {
			$generated = $token->generate(10);
			$session->set('public-token',$generated);
		}
		return $generated;
	}

    /**
     * @access protected
     * @param string $token
     * @param boolean
     */
	protected function verifyToken($token)
	{
		$session = new Session();
		if ( $session->isRegistered() ) {
			if ( $token === $session->get('private-token') ) {
				return true;
			}
		} else {
			if ( $token === $session->get('public-token') ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @access protected
	 * @param string $type
	 * @param object $name
	 * @param array $callbakck
	 * @return void
	 */
	protected function hook($type = 'action', $name, $args = [])
	{
		$hook = Hooks::getInstance();
		switch ($type) {
			case 'action':
				$hook->addAction($name,$args);
				break;
			case 'filter':
				$hook->addFilter($name,$args);
				break;
		}
	}

	/**
	 * @access protected
	 * @param int $code
	 * @param string $message
	 * @return object
	 */
	protected function exception($code = 404, $message = '')
	{
		return new ErrorController($code,$message);
	}
}
