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

class Model extends Orm
{
	/**
	 * Init ORM.
	 */
	public function __construct()
	{
		parent::__construct();
	}

    /**
     * Add object | Forced bind (Create).
     *
     * @access public
     * @param array $bind
     * @return mixed
     */
    public function add(array $data)
    {
        if ( $this->bind($data)->create() ) {
            return $this->lastInsertId();
        }
        return false;
    }

    /**
     * Get object by Id | Forced bind (Read).
     *
     * @access public
     * @param mixed $id
     * @return mixed
     */
    public function get($id)
    {
        return $this->bind([
            $this->key => $id
        ])->read();
    }

    /**
     * Save object | Forced bind (Update).
     *
     * @access public
     * @param array $data
     * @return bool
     */
    public function save(array $data) : bool
    {
        return $this->bind($data)->update();
    }

    /**
     * Remove object by Id | Forced bind (Delete).
     *
     * @access public
     * @param mixed $id
     * @return bool
     */
    public function remove($id) : bool
    {
        return $this->bind([
            $this->key => $id
        ])->delete();
    }

    /**
     * Check object exists by Id | Forced bind (Count).
     *
     * @access public
     * @param mixed $id
     * @return bool
     */
    public function exists($id) : bool
    {
        return (bool)$this->bind([
            $this->key => $id
        ])->count();
    }
}
