<?php
/**
 * 2015-2016 Copyright (C) Payin7 S.L.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * DISCLAIMER
 *
 * Do not modify this file if you wish to upgrade the Payin7 module automatically in the future.
 *
 * @author    Payin7 S.L. <info@payin7.com>
 * @copyright 2015-2016 Payin7 S.L.
 * @license   http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 */

namespace Payin7\Tools;

class Unicode
{
    public static $has_mb;

    public static function strlen($string, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strlen($string, $encoding) : strlen($string);
    }

    public static function ucfirst($string, $encoding = 'UTF8')
    {
        if (self::$has_mb) {
            $firstChar = mb_substr($string, 0, 1, $encoding);
            $then = mb_substr($string, 1, null, $encoding);
            return mb_strtoupper($firstChar, $encoding) . $then;
        } else {
            return ucfirst($string);
        }
    }

    public static function strtoupper($string, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strtoupper($string, $encoding) : strtoupper($string);
    }

    public static function strtolower($string, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strtolower($string, $encoding) : strtolower($string);
    }

    public static function substr($str, $start, $length = null, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_substr($str, $start, $length, $encoding) : substr($str, $start, $length);
    }

    public static function strpos($haystack, $needle, $offset = null, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strpos($haystack, $needle, $offset, $encoding) : strripos($haystack, $needle, $offset);
    }

    public static function strripos($haystack, $needle, $offset = null, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strripos($haystack, $needle, $offset, $encoding) : strripos($haystack, $needle, $offset);
    }
}

Unicode::$has_mb = function_exists('mb_strtolower');
