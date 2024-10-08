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

trait TraitException
{
	use \FloatPHP\Helpers\Framework\inc\TraitFormattable,
		\FloatPHP\Helpers\Framework\inc\TraitRequestable;

	/**
	 * Throw error controller.
	 * 
	 * @access public
	 * @param int $code
	 * @param string $message
	 * @return void
	 */
	public function throwError($code = 404, $message = null)
	{
		$render = true;
		if ( $this->hasItem('method', $this, 'getApiBaseUrl') ) {
			$url = $this->getServer('request-uri');
			$api = $this->getApiBaseUrl();
			if ( $this->searchString($url, $api) || $this->hasRequest() ) {
				$render = false;
			}
		}
		new ErrorController($code, $message, $render);
	}
}
