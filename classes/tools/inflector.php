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

class Inflector
{
    # returns a string from format: underscored, spaced to: ThisIsTheString
    public static function subcamelize($underscored_subject, $sanitize = true)
    {
        $underscored_subject = self::camelize($underscored_subject, $sanitize);
        $underscored_subject{0} = strtolower($underscored_subject{0});
        return $underscored_subject;
    }

    # returns a string from format: underscored, spaced to: thisIsTheString

    public static function camelize($underscored_subject, $sanitize = true)
    {
        // allow passing a sanitize parameter to speed up this where
        // sanitizing is not necessary (it makes it a LOT slower)
        $r = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($underscored_subject))));
        $r = $sanitize ? StringUtils::toAlphaNum($r) : $r;
        return $r;
    }

    # returns a well formated string to a controller class name - TheController => cTheController

    public static function controllerize($camelized_controller_name)
    {
        return 'c' . $camelized_controller_name;
    }

    public static function asClassName($input, $type)
    {
        $input = StringUtils::toAlphaNum($input, array('-', '_', '.', ':'));
        $input = self::camelize($input);

        return $type . $input;
    }

    public static function asFolderName($input)
    {
        $input = StringUtils::toAlphaNum($input, array('-', '.', ':'));
        return $input;
    }

    # when we need to remove 'c','v','t' to find a filename
    public static function asFilename($input)
    {
        return substr($input, 1, strlen($input));
    }

    public static function asFuncName($input)
    {
        $input = StringUtils::toAlphaNum($input, array('-', '_', '.', ':'));
        $input = self::camelize($input);

        $input{0} = strtolower($input{0});

        return $input;
    }

    public static function asVarName($input)
    {
        $input = StringUtils::toAlphaNum($input, array('-', '_', '.', ':'));
        $input = self::camelize($input);

        $input{0} = strtolower($input{0});

        return $input;
    }

    public static function underscore($camelCasedWord)
    {
        $replace = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
        return $replace;
    }

    public static function humanize($camelCasedWord)
    {
        $replace = ucfirst(strtolower(preg_replace('/(?<=\\w)([A-Z])/', ' \\1', $camelCasedWord)));
        return $replace;
    }
}