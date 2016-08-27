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
class Payin7NotifyModuleFrontController extends ModuleFrontController
{
    const API_VER_HEADER = 'X-Notify-Api-Ver';
    const API_VER = 2;

    const RESP_ERR_BASE = 2000;
    const RESP_GENERIC_ERR = 40;
    const RESP_REQUEST_ERR = 50;
    const RESP_VERIFY_PAYLOAD_ERR = 51;
    const RESP_UNSUPPORTED_CALL_ERR = 52;

    const CALL_TYPE_ORDER_STATE_CHANGE = 'order_state_change';
    const CALL_TYPE_PING = 'ping';

    protected $supported_calls = array(
        self::CALL_TYPE_ORDER_STATE_CHANGE,
        self::CALL_TYPE_PING
    );

    protected $debug;

    /** @var Payin7 */
    public $module;

    public function init()
    {
        /** @noinspection PhpUndefinedClassInspection */
        parent::init();

        $this->debug = $this->module->getConfigApiDebugMode();

        $this->execute();
    }

    public function execute()
    {
        $this->module->getLogger()->info(get_class($this) . ': notify :: ' . print_r($_POST, true));

        $payload = isset($_POST['payload_b64']) ? $_POST['payload_b64'] : null;
        $payload = $payload ? @base64_decode($payload) : null;
        $payload = $payload ? (array)@json_decode($payload) : array();

        $signature = isset($_POST['signature']) ? $_POST['signature'] : null;
        $call_type = isset($_POST['call_type']) ? $_POST['call_type'] : null;

        // check the request
        if (!$this->debug) {
            if (!isset($_POST) || !$_POST) {
                $this->handleError($this->module->l('Invalid Request 1'), self::RESP_REQUEST_ERR);
            }
        }

        // check the request
        if (!is_array($payload) || !$signature || !$call_type) {
            $this->handleError($this->module->l('Invalid Request 2'), self::RESP_REQUEST_ERR);
        }

        // verify the payload
        if (!$this->module->verifySbnPayload($payload, $signature)) {
            $this->handleError($this->module->l('Payload could not be validated'), self::RESP_VERIFY_PAYLOAD_ERR);
        }

        // check the call type
        if (!in_array($call_type, $this->supported_calls)) {
            $this->handleError($this->module->l('Unsupported'), self::RESP_UNSUPPORTED_CALL_ERR);
        }

        // process
        $message = null;
        $code = null;
        $data = null;

        try {
            $success = $this->processPayload($call_type, $payload, $message, $code, $data);
        } catch (Exception $e) {
            $success = false;
            $msg = $e->getMessage() . ' - ' . $e->getTraceAsString() . ' - ' . $e->getFile() . ' - ' . $e->getLine();
            $this->module->getLogger()->info(get_class($this) . ': notify exception :: ' . $msg);

            $this->handleError($msg, self::RESP_GENERIC_ERR);
        }

        $this->handleResponse($message, $code, $success, $data);
    }

    protected function processPayload($call_type, array $payload, & $message, & $code, array & $data = null)
    {
        $message = null;
        $data = null;
        $code = null;
        $ret = true;

        switch ($call_type) {
            case self::CALL_TYPE_ORDER_STATE_CHANGE: {

                $new_order_state = isset($payload['state']) ? $payload['state'] : null;
                $create_if_missing = isset($payload['create_order']) ? $payload['create_order'] : null;
                $order_id = isset($payload['order_id']) ? $payload['order_id'] : null;
                $payment_method = isset($payload['payment_method']) ? $payload['payment_method'] : null;
                $is_sandbox = isset($payload['is_sandbox']) ? $payload['is_sandbox'] : false;
                $store_order_id = isset($payload['store_order_id']) ? $payload['store_order_id'] : null;

                if (!$order_id) {
                    $message = 'Invalid order id';
                    $ret = false;
                }

                $order = null;

                if ($ret && $create_if_missing) {
                    if (!$store_order_id) {
                        // no store order id passed - order may not exist yet
                        $cart_id = isset($payload['store_cart_id']) ? $payload['store_cart_id'] : null;
                        $cart_secure_key = isset($payload['store_cart_secure_key']) ? $payload['store_cart_secure_key'] : null;

                        if (!$cart_id || !$cart_secure_key) {
                            $message = 'Cart id / skey / pdata not passed';
                            $ret = false;
                        } else {
                            /** @var CartCore $cart */
                            /** @noinspection PhpUndefinedClassInspection */
                            $cart = new Cart((int)$cart_id);

                            /** @noinspection PhpUndefinedClassInspection */
                            if ($cart && Validate::isLoadedObject($cart) && $cart->id) {
                                if ($cart->secure_key != $cart_secure_key) {
                                    $message = 'Cart secure key does not match';
                                    $ret = false;
                                } else {
                                    // check if order already exists
                                    /** @noinspection PhpUndefinedClassInspection */
                                    $order_p = Order::getOrderByCartId((int)($cart->id));

                                    /** @noinspection PhpUndefinedClassInspection */
                                    if (!$order_p || !Validate::isLoadedObject($order_p)) {
                                        try {
                                            $order = $this->createOrderFromCart($cart,
                                                $order_id,
                                                $payment_method,
                                                $is_sandbox,
                                                $message, $code);
                                        } catch (Exception $e) {
                                            throw new Exception('Could not create order from cart: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
                                        }
                                    }
                                }
                            } else {
                                $message = 'Cart item not found';
                                $ret = false;
                            }
                        }
                    }

                    // order id passed - order exists - just update the state
                    if ($order_id) {
                        /** @var \Payin7\Models\OrderModel $order */
                        $order = $this->module->getModelInstance('order');
                        $loaded = $order->loadPayin7Data($order_id);

                        if ($loaded) {
                            try {
                                $ret = $this->processOrderStateChange($new_order_state, $payload, $order, $message, $code);
                            } catch (Exception $e) {
                                throw new Exception('Could not process order state change: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
                            }
                        } else {
                            $message = 'Local order could not be loaded';
                        }
                    } else {
                        $message = 'Order ID was not set';
                    }

                    $data = array(
                        'order' => $order->getData()
                    );

                    /*
                // resend the order info to Payin7 in case data has changed
                if ($update_order_to_payin7 && $ret && $order) {
                   try {
                        // submit it back to Payin7 to send additinal data created in this process
                        $order_submitted = $this->module->submitOrder($order, Payin7::SOURCE_NOTIFY, true);

                        if (!$order_submitted) {
                            throw new Exception('Could not submit created order to payin7');
                        }
                    } catch (Exception $e) {
                        // don't fail here
                        $this->module->getLogger()->info(get_class($this) . ': payin7 order submission exception :: ' . $e->getMessage());
                    }
                    }*/
                }

                break;
            }
            case self::CALL_TYPE_PING: {
                $message = 'PONG' . "\n" .
                    json_encode($this->module->getSysinfo());
                break;
            }
            default: {
                return false;
            }
        }

        return $ret;
    }

    protected function createOrderFromCart($cart, $payin7_order_id, $payment_method, $is_sandbox, & $message,
        /** @noinspection PhpUnusedParameterInspection */
                                           & $code)
    {
        $this->module->getLogger()->info(get_class($this) . ': createOrderFromCart :: ' . $cart->id . ', pd: ' . $payin7_order_id);

        $message = null;
        $code = null;

        if (!$cart ||
            !$payin7_order_id ||
            !$payment_method
        ) {
            $message = 'Invalid payment method';
            return false;
        }

        /** @var \Payin7\Models\OrderModel $order_model */
        $order_model = $this->module->getModelInstance('order');
        $order_id = null;

        Db::getInstance()->execute('BEGIN');

        try {
            // validate and store the order
            /** @noinspection PhpUndefinedMethodInspection */
            $this->module->validateOrder(
                $cart->id,
                $this->module->getConfigIdOrderStatePending(),
                $cart->getOrderTotal(),
                $this->module->displayName,
                null,
                array(),
                $cart->id_currency,
                false,
                $cart->secure_key
            );

            /** @noinspection PhpUndefinedFieldInspection */
            $order_id = $this->module->currentOrder;

            if (!$order_id) {
                Db::getInstance()->execute('ROLLBACK');
                $this->handleError($this->module->l('Invalid params'), self::RESP_REQUEST_ERR);
            }

            $order_model->setPaymentMethodCode($payment_method);
            $order_model->setPayin7SandboxOrder($is_sandbox);
            $order_model->setCartSecureKey($cart->secure_key);
            $order_model->setOrderId($order_id);
            $order_model->setPayin7OrderIdentifier($payin7_order_id);
            $order_model->setPayin7OrderAccepted(true);
            $order_model->setPayin7OrderSent(true);
            $order_model->savePayin7Data();

            Db::getInstance()->execute('COMMIT');
        } catch (Exception $e) {
            Db::getInstance()->execute('ROLLBACK');
            throw $e;
        }

        $this->module->getLogger()->info(get_class($this) . ': new order created :: ' . $order_id);

        return $order_model;
    }

    protected function processOrderStateChange($new_order_state, array $payload, \Payin7\Models\OrderModel $order, & $message,
        /** @noinspection PhpUnusedParameterInspection */
                                               & $code)
    {
        $message = null;
        $code = null;

        switch ($new_order_state) {
            case 'cancel' : {

                /** @var OrderCore $orderm */
                /** @noinspection PhpUndefinedClassInspection */
                $orderm = new Order($order->getOrderId());

                if (ValidateCore::isLoadedObject($orderm)) {
                    $state = $orderm->current_state;

                    if ($state == $this->module->getConfigIdOrderStatePending()) {
                        // temporarily disable updating history back to Payin7
                        $this->module->setHistoryUpdateEnabled(false);

                        $orderm->setCurrentState($this->module->getConfigIdOrderStateCancelled());

                        // reenable history
                        $this->module->setHistoryUpdateEnabled(true);

                        $message = 'Local order state set to: ' . $this->module->getConfigIdOrderStateCancelled();
                    } else {
                        $message = 'Local order is not in pending state';
                    }
                } else {
                    $message = 'Local order could not be loaded (2)';
                }

                break;
            }

            case 'active': {

                $is_verified = isset($payload['is_verified']) ? (bool)$payload['is_verified'] : false;
                $is_paid = isset($payload['is_paid']) ? (bool)$payload['is_paid'] : false;

                // update the order state
                if (!$order->getPayin7OrderAccepted()) {
                    $order->setPayin7OrderAccepted(true);
                    $order->savePayin7Data();
                }

                if ($is_verified &&
                    $is_paid
                ) {
                    /** @var OrderCore $orderm */
                    /** @noinspection PhpUndefinedClassInspection */
                    $orderm = new Order($order->getOrderId());

                    $state = $orderm->current_state;

                    if ($state == $this->module->getConfigIdOrderStatePending()) {
                        // temporarily disable updating history back to Payin7
                        $this->module->setHistoryUpdateEnabled(false);

                        $orderm->setCurrentState($this->module->getConfigIdOrderStateAccepted());

                        // reenable history
                        $this->module->setHistoryUpdateEnabled(true);

                        $message = 'Local order state set to: ' . $this->module->getConfigIdOrderStateAccepted();
                    } else {
                        $message = 'Local order is not in pending state';
                    }
                } else {
                    $message = 'Order not verified / paid';
                }

                break;
            }
        }

        return true;
    }

    protected function handleError($message, $code)
    {
        $this->handleResponse($message, $code, false);
    }

    protected function handleResponse($message = null, $code = null, $success = true, array $data = null)
    {
        if (!$success) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        header('Content-Type: application/json');
        header(self::API_VER_HEADER . ': ' . self::API_VER);

        echo json_encode(array(
            'status' => ($success ? 'OK' : 'KO'),
            'status_message' => $message,
            'status_code' => $code,
            'data' => (array)$data,
            'request' => array_filter(array(
                (isset($_POST) ? (array)$_POST : null),
                (isset($_GET) ? (array)$_GET : null),
            ))
        ));

        exit(0);
    }
}
