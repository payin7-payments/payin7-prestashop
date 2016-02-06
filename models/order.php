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

require_once(__DIR__ . DS . 'quote.php');

/**
 * Class OrderModel
 * @package Payin7\Models
 * @method string getOrderId()
 * @method void setOrderId($value = null)
 * @method string getPayin7OrderIdentifier()
 * @method void setPayin7OrderIdentifier($value = null)
 * @method bool getPayin7OrderSent()
 * @method void setPayin7OrderSent($value = null)
 * @method bool getPayin7OrderAccepted()
 * @method void setPayin7OrderAccepted($value = null)
 * @method string getPayin7AccessToken()
 * @method void setPayin7AccessToken($value = null)
 * @method string getPayin7SandboxOrder()
 * @method void setPayin7SandboxOrder($value = null)
 */
class OrderModel extends QuoteModel
{
    public function loadPayin7DataById($order_id)
    {
        if (!$order_id) {
            return false;
        }

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'payin7_order` WHERE id_order = ' . (int)$order_id;
        $data = Db::getInstance()->getRow($sql);

        if (!$data) {
            return false;
        }

        $this->setOrderId($order_id);
        $this->setPayin7OrderIdentifier($data['payin7_order_identifier']);
        $this->setPayin7OrderAccepted((bool)$data['payin7_order_accepted']);
        $this->setPayin7OrderSent((bool)$data['payin7_order_sent']);
        $this->setPayin7SandboxOrder((bool)$data['payin7_sandbox_order']);
        $this->setPayin7AccessToken($data['payin7_access_token']);

        return $this;
    }

    public function loadPayin7Data($order_identifier)
    {
        if (!$order_identifier) {
            return false;
        }

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'payin7_order` WHERE payin7_order_identifier = \'' . pSQL($order_identifier) . '\'';
        $data = Db::getInstance()->getRow($sql);

        if (!$data) {
            return false;
        }

        $this->setOrderId((int)$data['id_order']);
        $this->setPayin7OrderIdentifier($data['payin7_order_identifier']);
        $this->setPayin7OrderAccepted((bool)$data['payin7_order_accepted']);
        $this->setPayin7OrderSent((bool)$data['payin7_order_sent']);
        $this->setPayin7SandboxOrder((bool)$data['payin7_sandbox_order']);
        $this->setPayin7AccessToken($data['payin7_access_token']);

        return $this;
    }

    public function savePayin7Data()
    {
        $identifier = $this->getPayin7OrderIdentifier();
        $order_id = $this->getOrderId();

        if (!$identifier || !$order_id) {
            return false;
        }

        $sql = 'REPLACE INTO `' . _DB_PREFIX_ . 'payin7_order`
        (id_order, payin7_order_sent, payin7_order_accepted, payin7_order_identifier, payin7_sandbox_order, payin7_access_token)
        VALUES(' . $order_id . ', ' .
            (int)$this->getPayin7OrderSent() . ', ' .
            (int)$this->getPayin7OrderAccepted() . ', ' .
            '\'' . pSQL($this->getPayin7OrderIdentifier()) . '\', ' .
            (int)$this->getPayin7SandboxOrder() . ', ' .
            '\'' . pSQL($this->getPayin7AccessToken()) . '\') ';
        return Db::getInstance()->execute($sql);
    }
}
