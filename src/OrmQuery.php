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

use FloatPHP\Interfaces\Kernel\OrmQueryInterface;
use FloatPHP\Classes\Filesystem\{
    TypeCheck, Arrayify
};

final class OrmQuery implements OrmQueryInterface
{
	/**
	 * @access public
	 * @var array $query
	 */
	public $query = [];

	/**
	 * @param array $query
	 */
	public function __construct($query = [])
	{
		$this->query = $this->setDefault($query);
	}

	/**
	 * @access public
	 * @param array $query
	 * @return array
	 */
	private function setDefault($query = [])
	{
		$query = Arrayify::merge([
			'table'     => '',
			'column'    => '*',
			'where'     => '',
			'bind'      => [],
			'orderby'   => '',
			'limit'     => '',
			'isSingle'  => false,
			'isColumn'  => false,
			'isRow'     => false,
			'fetchMode' => null
		], $query);


		if ( TypeCheck::isArray($query['where']) ) {
			$temp = [];
			foreach ($query['where'] as $property => $value) {
				$temp[] = "`{$property}` = {$value}";
			}
			$query['where'] = implode(' AND ',$temp);
		}

		$query['where'] = !empty($query['where'])
		? "WHERE {$query['where']}" : '';

		$query['limit'] = !empty($query['limit'])
		? "LIMIT {$query['limit']}" : '';

		$query['orderby'] = !empty($query['orderby'])
		? "ORDER BY {$query['orderby']}" : '';

		return $query;
	}
}
