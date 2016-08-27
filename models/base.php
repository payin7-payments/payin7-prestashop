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

namespace Payin7\Models;

use Context;
use Payin7;
use Payin7\Tools\Inflector;

abstract class BaseModel
{
    /** @var Context */
    protected $_context;

    /** @var Payin7 */
    public $module;

    /** @var array|null */
    protected $_data;

    public function __construct()
    {
        //
    }

    public function __get($name)
    {
        return $this->getData($name);
    }

    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) == 'get') {
            // getter
            $sstr = substr($name, 3, strlen($name));
            $sstr = $sstr ? Inflector::underscore($sstr) : null;

            if ($sstr) {
                return $this->getData($sstr);
            }
        } elseif (substr($name, 0, 3) == 'set') {
            // setter
            $sstr = substr($name, 3, strlen($name));

            $sstr = $sstr ? Inflector::underscore($sstr) : null;

            if ($sstr) {
                $this->setData($sstr, ($arguments ? $arguments[0] : null));
            }
        }

        return null;
    }

    public function __toString()
    {
        return (string)print_r((array)$this->_data, true);
    }

    public function initialize(Context $context, $module)
    {
        $this->_context = $context;
        $this->module = $module;
    }

    public function getData($key = null)
    {
        return (isset($key) ? (isset($this->_data[$key]) ? $this->_data[$key] : null) : $this->_data);
    }

    public function setData($key, $data = null)
    {
        if (is_array($key) || (!$key && !$data)) {
            $this->_data = $key;
        } else {
            $this->_data[$key] = $data;
        }
    }

    public function getContext()
    {
        return $this->_context;
    }
}