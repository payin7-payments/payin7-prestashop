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

class OrderHistoryModel extends BaseModel
{
    const ORDER_STATE_CHANGED = 'order_state_changed';
    const ORDER_UPDATED = 'order_updated';
    const DOC_UPDATED = 'doc_updated';

    const DOC_TYPE_SHIPMENT = 'shipment';
    const DOC_TYPE_INVOICE = 'invoice';
    const DOC_TYPE_CREDIT_MEMO = 'creditmemo';

    protected function _filterModelSimpleData(array $data = null)
    {
        $out_data = array();

        if ($data) {
            foreach ($data as $k => $v) {
                if (is_string($v) || is_numeric($v) || is_array($v)) {
                    $out_data[$k] = $v;
                }
                unset($k, $v);
            }
        }

        return $out_data;
    }

    public function markOrderDocumentUpdated(/** @noinspection PhpUndefinedClassInspection */
        \Order $order, $payin7_order_identifier, $document_type, $document_object = null)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->_saveOrderHistory($order, $payin7_order_identifier, self::DOC_UPDATED, array(
            'document_type' => $document_type,
            'document_data' => ($document_object ? $this->_filterModelSimpleData($document_object->getFields()) : null)
        ));
    }

    public function markOrderUpdated(/** @noinspection PhpUndefinedClassInspection */
        \Order $order, $payin7_order_identifier)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->_saveOrderHistory($order, $payin7_order_identifier, self::ORDER_UPDATED, $this->_filterModelSimpleData($order->getFields()));
    }

    public function markOrderStateChanged(/** @noinspection PhpUndefinedClassInspection */
        \Order $order, $payin7_order_identifier, /** @noinspection PhpUndefinedClassInspection */
        \OrderState $status)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->_saveOrderHistory($order, $payin7_order_identifier, self::ORDER_STATE_CHANGED, array_merge(array(
            'status_id' => $status->id,
            'status_module_name' => $status->module_name
        ), (array)$order->getFields()));
    }

    protected function _saveOrderHistory(/** @noinspection PhpUndefinedClassInspection */
        \Order $order, $payin7_order_identifier, $change_type, $data = null)
    {
        if (!$payin7_order_identifier) {
            return null;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        Db::getInstance()->insert('payin7_order_history', array(
            'id_order' => $order->id,
            'order_unique_id' => $payin7_order_identifier,
            'created_on' => date('Y-m-d H:i:s'),
            'change_type' => $change_type,
            'data' => ($data ? @serialize($data) : null)
        ));

        return $this;
    }
}