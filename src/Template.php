<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.1.0
 * @category  : PHP framework
 * @copyright : (c) JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 *
 * This file if a part of FloatPHP Framework
 */

namespace floatPHP\Kernel;

use Twig_Loader_Filesystem as Loader;
use Twig_Environment as Environment;
use Twig_SimpleFunction as BuiltInFunction;

class Template
{
    /**
     * @param string $path
     * @param array $settings
     * @return object Environment
     */
    public static function getEnvironment($path, $settings = [])
    {
        return new Environment(new Loader($path), $settings);
    }

    /**
     * @param string $name
     * @param string|array $function
     * @return object BuiltInFunction
     */
    public static function extend($name, $function)
    {
        return new BuiltInFunction($name, $function);
    }
}
