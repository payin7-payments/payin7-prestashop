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

require_once(__DIR__ . DS . '_base.php');

class Payin7OrderFinalizeModuleFrontController extends Payin7BaseModuleFrontController
{
    public function execute()
    {
        $uid = Tools::getValue('order_id');

        if (!$uid) {
            $this->handleError($this->module->l('Invalid Request'), self::RESP_REQUEST_ERR);
        }

        /** @var \Payin7\Models\OrderModel $order */
        $order = $this->module->getModelInstance('order');
        $loaded = $order->loadPayin7Data($uid);

        if (!$loaded) {
            $this->handleError($this->module->l('Invalid Order'), self::RESP_INVALID_ORDER_ERR);
        }

        if (!$order->getPayin7OrderSent() || $order->getPayin7OrderAccepted()) {
            $this->handleError($this->module->l('Order already processed'), self::RESP_INVALID_ORDER_ERR);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $order_data = $order_data = array(
            'orderId' => $order->getPayin7OrderIdentifier(),
            'orderUrl' => $this->module->getFrontendOrderCompleteUrl($order,
                false,
                $this->module->getShouldUseSecureConnection()),
            'cancelUrl' => $this->module->getModuleLink('ordercancel',
                array(
                    'order_id' => $uid,
                    'module_action' => 'cancel'
                ),
                $this->module->getShouldUseSecureConnection()),
            'completeUrl' => $this->module->getModuleLink('ordersuccess',
                array(
                    'order_id' => $uid,
                    'module_action' => 'complete'
                ),
                $this->module->getShouldUseSecureConnection()),
            'isCheckout' => true
        );

        $this->context->smarty->assign(array(
            'order_data' => json_encode($order_data),
        ));

        if ($this->module->getIsPrestashop14()) {
            $this->context->smarty->display(_PS_MODULE_DIR_ . 'payin7/views/templates/front/finalize.tpl');
        } else {
            return $this->setTemplate('finalize.tpl');
        }

        return null;
    }
}
