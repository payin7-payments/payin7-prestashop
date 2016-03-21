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

abstract class Payin7OrderRetModuleFrontController extends Payin7BaseModuleFrontController
{
    protected $_is_checkout;

    /** @var \Payin7\Models\OrderModel */
    protected $_order;

    protected $_order_id;
    protected $_is_verified;
    protected $_is_rejected;
    protected $_is_cancelled;
    protected $_is_paid;
    protected $_order_state;

    protected function handleError($message, $code, $force_redirect = false)
    {
        if (!$this->redirect_on_error && !$force_redirect) {
            throw new Exception($message, self::RESP_ERR_BASE + $code);
        } else {
            if ($this->_is_checkout) {
                Tools::redirect($this->getControllerOrderUrl());
            } else {
                Tools::redirect('/index.php?controller=order-history');
            }
            exit(0);
        }
    }

    protected function verifyHashCheck()
    {
        $is_sandbox_order = $this->_order->getPayin7SandboxOrder();
        $secure_key = Tools::getValue('secure_key');
        $hash_check = Tools::GetValue('hash');

        if (!$hash_check || !$this->_order_id) {
            return false;
        }

        $api_key = $is_sandbox_order ?
            $this->module->getConfigApiSandboxKey() :
            $this->module->getConfigApiProductionKey();

        $gen_key = sha1(
            $secure_key .
            $this->_order_id .
            (int)$this->_is_verified .
            (int)$this->_is_rejected .
            (int)$this->_is_cancelled .
            (int)$this->_is_paid .
            $this->_order_state .
            $api_key);

        return ($hash_check == $gen_key);
    }

    protected function verifyIsPost()
    {
        if (!isset($_POST) || !$_POST) {
            $this->handleError($this->module->l('Invalid Request'), self::RESP_REQUEST_ERR);
        }
    }

    protected function getVerifyOrder($verify_key = true)
    {
        $order_id = Tools::getValue('order_id');
        $secure_key = Tools::getValue('secure_key');
        $this->_is_verified = (bool)Tools::GetValue('verified');
        $this->_is_rejected = (bool)Tools::GetValue('rejected');
        $this->_is_cancelled = (bool)Tools::GetValue('cancelled');
        $this->_is_paid = (bool)Tools::GetValue('paid');
        $this->_order_state = Tools::GetValue('state');

        $saved_order = (bool)Tools::getValue('saved_order');
        $this->_is_checkout = !$saved_order;

        if (!$order_id) {
            $this->handleError($this->module->l('Invalid Request'), self::RESP_REQUEST_ERR);
        }

        /** @var \Payin7\Models\OrderModel $order */
        $order = $this->module->getModelInstance('order');
        $loaded = $order->loadPayin7Data($order_id);

        if (!$loaded) {
            $this->handleError($this->module->l('Invalid Order'), self::RESP_INVALID_ORDER_ERR);
        }

        if ($verify_key) {
            // verify the secure key
            $key_verified = $this->module->verifyOrderSecureKey($order, $secure_key);

            if (!$key_verified) {
                $this->handleError($this->module->l('Invalid Order'), self::RESP_INVALID_ORDER_ERR);
            }
        }

        $this->_order = $order;
        $this->_order_id = $order_id;

        // check the hash check
        if (!$this->verifyHashCheck()) {
            $this->handleError($this->module->l('Security Problem'), self::RESP_INVALID_ORDER_HASH_ERR);
        }

        return $order;
    }

    protected function restoreOrderToCart(/** @noinspection PhpUndefinedClassInspection */
        Cart $cart)
    {
        if (!$cart || !ValidateCore::isLoadedObject($cart)) {
            return null;
        }

        Db::getInstance()->execute('BEGIN');

        $new_cart = null;

        try {
            /** @var CartCore $new_cart */
            /** @noinspection PhpUndefinedClassInspection */
            $new_cart = new Cart();
            /** @noinspection PhpUndefinedFieldInspection */
            $new_cart->id_customer = (int)$cart->id_customer;
            /** @noinspection PhpUndefinedFieldInspection */
            $new_cart->id_address_delivery = (int)$cart->id_address_delivery;
            /** @noinspection PhpUndefinedFieldInspection */
            $new_cart->id_address_invoice = (int)$cart->id_address_invoice;
            /** @noinspection PhpUndefinedFieldInspection */
            $new_cart->id_lang = (int)$cart->id_lang;
            /** @noinspection PhpUndefinedFieldInspection */
            $new_cart->id_currency = (int)$cart->id_currency;
            /** @noinspection PhpUndefinedFieldInspection */
            $new_cart->id_carrier = (int)$cart->id_carrier;
            /** @noinspection PhpUndefinedFieldInspection */
            $new_cart->recyclable = (int)$cart->recyclable;
            /** @noinspection PhpUndefinedFieldInspection */
            $new_cart->gift = (int)$cart->gift;
            $new_cart->add();

            /** @noinspection PhpUndefinedMethodInspection */
            $products = $cart->getProducts();

            if ($products) {
                foreach ($products as $p) {
                    $idProduct = $p['id_product'];
                    $idProductAttribute = $p['id_product_attribute'];
                    $qty = $p['cart_quantity'];

                    /** @noinspection PhpUndefinedClassInspection */
                    /** @noinspection PhpUndefinedFieldInspection */
                    $producToAdd = new Product((int)($idProduct), true, (int)($cart->id_lang));

                    /** @noinspection PhpUndefinedFieldInspection */
                    if ((!$producToAdd->id || !$producToAdd->active)) {
                        continue;
                    }

                    /* Check the quantity availability  */
                    if ($idProductAttribute > 0 AND is_numeric($idProductAttribute)) {
                        /** @noinspection PhpUndefinedClassInspection */
                        /** @noinspection PhpUndefinedMethodInspection */
                        /** @noinspection PhpUndefinedFieldInspection */
                        if (!$producToAdd->isAvailableWhenOutOfStock($producToAdd->out_of_stock) AND !Attribute::checkAttributeQty((int)$idProductAttribute, (int)$qty)) {
                            /* There is not enough product attribute in stock - set customer qty to current stock on hand */
                            /** @noinspection PhpUndefinedFunctionInspection */
                            $qty = getAttributeQty($idProductAttribute);
                        }
                    } /** @noinspection PhpUndefinedMethodInspection */ elseif (!$producToAdd->checkQty((int)$qty))
                        /* There is not enough product in stock - set customer qty to current stock on hand */
                        /** @noinspection PhpUndefinedMethodInspection */
                        $qty = $producToAdd->getQuantity($idProduct);

                    $new_cart->updateQty((int)($qty), (int)($idProduct), (int)($idProductAttribute), NULL, 'up');

                    unset($p);
                }
            }

            $new_cart->update();

            Db::getInstance()->execute('COMMIT');
        } catch (Exception $e) {
            Db::getInstance()->execute('ROLLBACK');
            throw $e;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        $this->context->cookie->id_cart = (int)$new_cart->id;

        return $new_cart;
    }
}
