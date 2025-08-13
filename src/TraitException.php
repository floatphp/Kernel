<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.5.x
 * @copyright  : (c) 2018 - 2025 Jihad Sinnaour <me@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

trait TraitException
{
	use \FloatPHP\Helpers\Framework\tr\TraitFormattable,
		\FloatPHP\Helpers\Framework\tr\TraitRequestable;

	/**
	 * Throw error controller.
	 *
	 * @access public
	 * @param int $code
	 * @param string $message
	 * @return void
	 */
	public function throwError(int $code = 404, ?string $message = null) : void
	{
		$render = true;
		if ( $this->hasObject('method', $this, 'getApiUrl') ) {
			$url = $this->getServer('request-uri');
			$api = $this->getApiUrl();
			if ( $this->hasString($url, $api) ) {
				$render = false;
			}
		}
		new ErrorController($code, $message, $render);
	}
}
