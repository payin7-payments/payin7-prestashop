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
use Db;
use Exception;

abstract class PlatformAbstractModel extends BaseModel
{
    const DATA_REFRESH_TIMEOUT = 300; // in seconds

    private static $remote_update_processed_once = array();

    abstract public function getConfigKey();

    abstract public function getApiMethod();

    public function initialize(Context $context, $module)
    {
        parent::initialize($context, $module);

        $this->loadData();
    }

    public function getLastUpdated()
    {
        return $this->getData('last_updated');
    }

    public function loadData($force_remote_update = false)
    {
        $data_key = $this->getConfigKey();

        $last_updated = $this->getLastUpdated();

        $refresh_timeout = $this->module->getConfigApiDebugMode() ? 3 : self::DATA_REFRESH_TIMEOUT;
        $needs_update = $this->module->getConfigApiDebugMode() || $force_remote_update || !$last_updated || !$this->getData() ||
            ($last_updated && $last_updated + $refresh_timeout < time());

        if ($needs_update && (!isset(self::$remote_update_processed_once[$data_key]) || $force_remote_update)) {
            self::$remote_update_processed_once[$data_key] = true;

            // update the data
            if (!$new_data = $this->updatePlatformData($data_key)) {
                return null;
            }

            $this->setData($new_data);
        } else {
            $this->loadDataInternal();
        }
    }

    protected function loadDataInternal()
    {
        $data_key = $this->getConfigKey();

        $db = Db::getInstance();

        $data = $db->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'payin7_data WHERE data_key = \'' . pSQL($data_key) . '\'');

        if ($data) {
            $last_updated = $data['last_updated'];
            $this->setData((array)@json_decode($data['data'], true));
            $this->setData('last_updated', $last_updated);
        }
    }

    /**
     * @param $data_key
     * @return null
     */
    protected function updatePlatformData($data_key)
    {
        $api_method = $this->getApiMethod();

        $client = $this->module->getApiClientInstance();

        $config = null;

        try {
            $config = $client->$api_method();
        } catch (Exception $e) {
            $this->module->getLogger()->err('Could not fetch remote platform data: ' . $data_key . ': ' . $e->getMessage());
            return null;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $config = $config ? $config->toArray() : null;

        if ($config) {
            $data = @json_encode($config);

            $db = Db::getInstance();

            $sql = 'INSERT INTO  `' . _DB_PREFIX_ . 'payin7_data` (data_key, last_updated, data)
            VALUES(
            \'' . pSQL($data_key) . '\',
            CURRENT_TIMESTAMP,
            \'' . pSQL($data) . '\')
            ON DUPLICATE KEY UPDATE
              last_updated = CURRENT_TIMESTAMP,
              data = \'' . pSQL($data) . '\'
            ';
            $db->execute($sql);

            $this->setData($config);
            $this->setData('last_updated', time());

            $this->module->getLogger()->info('Platform data updated: ' . $data_key . ': ' . print_r($config, true));
        }

        return $config;
    }
}