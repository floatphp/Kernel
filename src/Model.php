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

class Model extends Orm
{
	/**
	 * @param array $data
	 */
	public function __construct($data = [])
	{
		$this->init($data);
	}

    /**
     * Get object from table by Id.
     *
     * @access public
     * @param int $Id
     * @return mixed
     */
    public function get($Id = 0)
    {
        $this->{$this->key} = (int)$Id;
        return $this->find();
    }

    /**
     * Check object exists in table by Id.
     *
     * @access public
     * @param int $Id
     * @return bool
     */
    public function exists($Id = 0) : bool
    {
        return (bool)$this->count([
            $this->key => (int)$Id
        ]);
    }

    /**
     * Add object to table.
     * Returns Id if added.
     *
     * @access public
     * @param array $data
     * @return mixed
     */
    public function add(array $data = [])
    {
        $this->data = $data;
        if ( $this->create() ) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Update object table.
     *
     * @access public
     * @param array $data
     * @return bool
     */
    public function update(array $data = []) : bool
    {
        $this->data = $data;
        return (bool)$this->save();
    }

    /**
     * Remove object from table by Id.
     *
     * @access public
     * @param int $clientId
     * @return bool
     */
    public function remove($Id = 0) : bool
    {
        $this->{$this->key} = (int)$Id;
        return (bool)$this->delete();
    }
}
