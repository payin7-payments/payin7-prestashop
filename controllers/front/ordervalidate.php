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

/** @noinspection PhpIncludeInspection */
require_once(__DIR__ . DS . '_base.php');

class Payin7OrderValidateModuleFrontController extends Payin7BaseModuleFrontController
{
    public function execute()
    {
        $this->module->getLogger()->info(get_class($this) . ': ordervalidate :: ' . print_r((isset($_POST) && $_POST ? $_POST : $_GET), true));

        $payment_method = Tools::getValue('payment_method');

        if (!$payment_method) {
            $this->handleError($this->module->l('Invalid Request'), self::RESP_REQUEST_ERR);
        }

        $cart = $this->context->cart;

        if (!$cart || !$cart->id) {
            $this->handleError($this->module->l('Invalid Order'), self::RESP_INVALID_ORDER_ERR);
        }

        // check user
        $customer_id = $cart->id_customer;

        if (!$customer_id) {
            $this->handleError($this->module->l('Access Denied'), self::RESP_ACCESS_DENIED);
        }

        /** @var \Payin7\Models\OrderModel $order_model */
        $order_model = $this->module->getModelInstance('order');

        $this->module->prepareCartQuote($order_model, true);

        $order_model->setPaymentMethodCode($payment_method);
        $order_model->setPayin7SandboxOrder($this->module->getConfigApiSandboxMode());
        $order_model->setCartSecureKey($cart->secure_key);

        $order_submitted = $this->module->submitOrder($order_model);

        if (!$order_submitted) {
            $this->handleError($this->module->l('Order could not be submitted'), self::RESP_ORDER_SUBMIT_ERR);
            return;
        }

        $this->finalizeOrder($order_model);
    }

    protected function finalizeOrder(\Payin7\Models\OrderModel $order)
    {
        $this->module->getLogger()->info(get_class($this) . ': orderfinalize :: ' . print_r($_POST, true));

        /** @noinspection PhpUndefinedMethodInspection */
        $order_data = array(
            'orderId' => $order->getPayin7OrderIdentifier(),
            'orderUrl' => $this->module->getFrontendOrderCompleteUrl($order,
                false,
                $this->module->getShouldUseSecureConnection(),
                true),
            'cancelUrl' => $this->module->getModuleLink('ordercancel',
                array(
                    'module_action' => 'cancel'
                ),
                $this->module->getShouldUseSecureConnection(),
                'ordercancel_handler'),
            'completeUrl' => $this->module->getModuleLink('ordersuccess',
                array(
                    'module_action' => 'complete'
                ),
                $this->module->getShouldUseSecureConnection(),
                'ordersuccess_handler'),
            'isCheckout' => true
        );

        $this->context->smarty->assign(array_merge($this->module->getPayin7SDKTemplateParams(), array(
            'order_data' => json_encode($order_data),
        )));

        if ($this->module->getIsPrestashop14()) {
            $this->context->smarty->display(_PS_MODULE_DIR_ . 'payin7/views/templates/front/finalize.tpl');
        } else {
            return $this->setTemplate('finalize.tpl');
        }

        return null;
    }
}
