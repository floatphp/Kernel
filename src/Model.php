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
 * This file is a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

class Model extends Orm
{
    /**
     * Add item.
     *
     * @access public
     * @param array $bind
     * @return bool
     */
    public function add(array $data, ?int &$id = null) : bool
    {
        if ( $this->bind($data)->create() ) {
            $id = $this->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Get item by Id.
     *
     * @access public
     * @param mixed $id
     * @return mixed
     */
    public function get($id) : mixed
    {
        return $this->bind([
            $this->key => $id
        ])->read();
    }

    /**
     * Save item.
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
     * Remove item by Id.
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
     * Check item exists by Id.
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
