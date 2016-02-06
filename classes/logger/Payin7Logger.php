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

class Payin7Logger
{
    private static $logger;

    private $filename;
    private $debug_enabled;

    public static function getInstance()
    {
        if (!self::$logger) {
            self::$logger = new Payin7Logger();
        }
        return self::$logger;
    }

    public function setDebugEnabled($enabled = true)
    {
        $this->debug_enabled = $enabled;
        return $this;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    protected function _log($message, $type)
    {
        @error_log('[' . date('Y-m-d H:i:s') . '] {' . $type . '}: ' . $message, 3, $this->filename);
    }

    public function info($message)
    {
        $this->_log($message, 'info');
    }

    public function err($message)
    {
        $this->_log($message, 'err');
    }

    public function debug($message)
    {
        if ($this->debug_enabled) {
            $this->_log($message, 'debug');
        }
    }
}