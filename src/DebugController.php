<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.0.0
 * @category  : PHP framework
 * @copyright : (c) JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 */

namespace floatPHP\Kernel;

class DebugController extends FrontController
{
	/**
	 * @access protected
	 */
	protected $template = 'system/debug';
	
	/**
	 * @param array $data, string $template
	 * @return void
	 */
	protected function assign($data = [] , $template = null)
	{
		parent::assign($data,$this->template);
	}
}
