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

class Payin7OrderValidateModuleFrontController extends Payin7BaseModuleFrontController
{
    public function execute()
    {
        // this can be called both by GET / POST
        /*if (!isset($_POST)) {
            $this->handleError($this->module->l('Invalid Request'), self::RESP_REQUEST_ERR);
        }*/

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

        Db::getInstance()->execute('BEGIN');

        try {
            // validate and store the order
            /** @noinspection PhpUndefinedMethodInspection */
            /** @noinspection PhpUndefinedFieldInspection */
            $this->module->validateOrder(
                $cart->id,
                $this->module->getConfigIdOrderStatePending(),
                $cart->getOrderTotal(),
                $this->module->displayName,
                null,
                array(),
                (int)Context::getContext()->currency->id,
                false,
                $this->context->cart->secure_key
            );

            /** @noinspection PhpUndefinedFieldInspection */
            $order_id = $this->module->currentOrder;

            if (!$order_id) {
                Db::getInstance()->execute('ROLLBACK');
                $this->handleError($this->module->l('Invalid params'), self::RESP_REQUEST_ERR);
            }

            $order_model->setOrderId($order_id);

            $this->handleCommOrder($order_model);

            Db::getInstance()->execute('COMMIT');
        } catch (Exception $e) {
            Db::getInstance()->execute('ROLLBACK');
            throw $e;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $redir_url = $this->module->getModuleLink('orderfinalize',
            array('order_id' => $order_model->getPayin7OrderIdentifier()),
            $this->module->getShouldUseSecureConnection());

        if ($this->module->getIsPrestashop14()) {
            header('Location: ' . $redir_url);
            exit();
        } else {
            Tools::redirect($redir_url);
        }
    }

    protected function handleCommOrder(\Payin7\Models\OrderModel $order_model)
    {
        // not submitted - submit it to Payin7

        $source = 'frontend';
        $ordered_by_ip_address = \Payin7\Tools\StringUtils::getIpAddress();
        $locale = $this->module->getCurrentLocaleCode();

        /** @var \Payin7\Models\OrderSubmitModel $order_submit */
        $order_submit = $this->module->getModelInstance('order_submit');
        $order_submit->setSysinfo($this->module->getSysinfo());
        $order_submit->setOrderedByIpAddress($ordered_by_ip_address);
        $order_submit->setSource($source);
        $order_submit->setOrder($order_model);
        $order_submit->setLanguageCode($locale);

        try {
            $submit_status = $order_submit->submitOrder(true);

            if (!$submit_status) {
                Db::getInstance()->execute('ROLLBACK');
                $this->handleError($this->module->l('Order could not be submitted'), self::RESP_ORDER_SUBMIT_ERR);
            }

            // save any order changes introduced by the submitter
            $order_model->savePayin7Data();

        } catch (\Payin7Payments\Exception\ClientErrorResponseException $e) {
            Db::getInstance()->execute('ROLLBACK');
            $this->handleError($e->getFullServerErrorMessage(), self::RESP_ORDER_SUBMIT_ERR);
        } catch (Exception $e) {
            if ($this->debug) {
                throw $e;
            }
            Db::getInstance()->execute('ROLLBACK');
            $this->handleError('System Error', self::RESP_SYSTEM_ERR);
        }
    }
}
