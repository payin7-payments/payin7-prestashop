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

use Db;
use Exception;

class HistorySubmitModel extends BaseModel
{
    protected $_client_timeout;

    const MAX_CRON_ORDERS = 10;

    public function sendPendingOrderHistory()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'payin7_order_history`
        ORDER BY history_id ASC
        LIMIT ' . (int)self::MAX_CRON_ORDERS;
        $history_data = Db::getInstance()->executeS($sql);

        if (!$history_data) {
            return;
        }

        $cd = count($history_data);

        $this->module->getLogger()->info('Order history sending started - sending ' . $cd . ' updates...');

        try {
            /** @noinspection PhpParamsInspection */
            $success = $this->submit($history_data);
            $this->module->getLogger()->info('Order history sending completed, status: ' . ($success ? 'SUCCESS' : 'ERROR') . ' - ' . $cd);
        } catch (Exception $e) {
            $this->module->getLogger()->err('Order history could not be sent - unhandled exception: ' . $e->getMessage() . ', file: ' .
                $e->getFile() . ', line: ' . $e->getLine() . ', trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    public function setClientTimeout($timeout)
    {
        $this->_client_timeout = $timeout;
    }

    protected function submit(array $data)
    {
        $data_out = array();

        foreach ($data as $history_el) {
            $data_out[] = array(
                'order_id' => $history_el['id_order'],
                'history_id' => $history_el['history_id'],
                'order_unique_id' => $history_el['order_unique_id'],
                'created_on' => $history_el['created_on'],
                'change_type' => $history_el['change_type'],
                'data' => @unserialize($history_el['data']),
            );
            unset($history_el);
        }

        $client = $this->module->getApiClientInstance();

        if ($this->_client_timeout) {
            $client->setConnectTimeout($this->_client_timeout);
            $client->setTimeout($this->_client_timeout);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $response = $client->postOrderHistory(array(
            'history' => json_encode($data_out)
        ));

        if (!is_array($response) || !$response['success']) {
            return false;
        }

        Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'payin7_order_history`');

        return true;
    }
}