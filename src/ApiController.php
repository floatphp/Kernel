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

use FloatPHP\Classes\Http\Server;
use FloatPHP\Classes\Http\Response;
use FloatPHP\Classes\Filesystem\Stringify;
use FloatPHP\Classes\Filesystem\Arrayify;
use FloatPHP\Classes\Security\Encryption;
use FloatPHP\Helpers\Filesystem\Transient;

class ApiController extends BaseController
{
	/**
	 * Is HTTP authenticated
	 *
	 * @access public
	 * @param void
	 * @return bool
	 */
	public function isHttpAuthenticated() : bool
	{
		// Init configuration
		$this->initConfig();

		// Basic authentication
		if ( $this->applyFilter('basic-authentication',true) ) {
			if ( Server::isBasicAuth() ) {
				$username = Server::getBasicAuthUser();
				$password = Server::getBasicAuthPwd();
	        	// API authenticate override
				$this->doAction('api-authenticate',[
					'username' => $username,
					'address'  => Server::getIP(),
					'method'   => 'basic'
				]);
			    if ( $username == $this->getApiUsername() 
			    	&& $password == $this->getApiPassword() ) {
				    return true;
			    }
			}
		}

		// Bearer token
		if ( ($token = Server::getBearerToken()) ) {
 			return $this->isGranted($token);
		}
		return false;
	}

	/**
	 * Is HTTP granted (Token)
	 *
	 * @access protected
	 * @param string $token
	 * @return bool
	 */
	protected function isGranted($token) : bool
	{
        $encryption = new Encryption($token,$this->getSecret(true));
        $access = $encryption->decrypt();
        $pattern = '/\{(.*?)\}:\{(.*?)\}/';
        $username = Stringify::match($pattern,$encryption->decrypt(),1);
        $password = Stringify::match($pattern,$encryption->decrypt(),2);
        if ( $username && $password ) {
        	// API authenticate override
			$this->doAction('api-authenticate',[
				'username' => $username,
				'address'  => Server::getIP(),
				'method'   => 'token'
			]);
			if ( $username == $this->getApiUsername() 
				&& $password == $this->getApiPassword() ) {
			    return true;
			}
        }
		return false;
	}

	/**
	 * API Authentication protection
	 *
	 * @access protected
	 * @param int $max
	 * @param int $seconds
	 * @param bool $address
	 * @param bool $method
	 * @return void
	 */
	protected function protect($max = 120, $seconds = 60, $address = true, $method = true)
	{
		$this->addAction('api-authenticate',function($args = []) use ($max,$seconds,$address,$method){
			// Exception
			$exception = (array)$this->applyFilter('api-exception',[]);
			if ( Arrayify::inArray($args['username'],$exception) ) {
				return;
			}
			// Authentication
			$transient = new Transient();
			$key = "api-authenticate-{$args['username']}";
			if ( $address ) {
				$key = "{$key}-{$args['address']}";
			}
			if ( $method ) {
				$key = "{$key}-{$args['method']}";
			}
			$attempts = 0;
			if ( !($attempts = $transient->getTemp($key)) ) {
				$transient->setTemp($key,1,$seconds);
			} else {
				$transient->setTemp($key,$attempts + 1,$seconds);
			}
			$max = (int)$max;
			if ( $attempts >= $max && $max !== 0 ) {
				$msg = $this->applyFilter('api-authenticate-attempt-message','Access forbidden');
				$this->setHttpResponse($msg,[],'error',429);
			}
		});
	}

	/**
	 * @access protected
	 * @param string $message
	 * @param array $content
	 * @param string $status
	 * @param int $code
	 * @return void
	 */
	protected function setHttpResponse($message = '', $content = [], $status = 'success', $code = 200)
	{
		Response::set($message,$content,$status,$code);
	}
}
