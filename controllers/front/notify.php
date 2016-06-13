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
        $payload = $payload ? (array)@json_decode($payload) : null;

        $signature = isset($_POST['signature']) ? $_POST['signature'] : null;
        $call_type = isset($_POST['call_type']) ? $_POST['call_type'] : null;

        // check the request
        if (!$this->debug) {
            if (!isset($_POST) || !$_POST) {
                $this->handleError($this->module->l('Invalid Request'), self::RESP_REQUEST_ERR);
            }
        }

        // check the request
        if (!is_array($payload) || !$signature || !$call_type) {
            $this->handleError($this->module->l('Invalid Request'), self::RESP_REQUEST_ERR);
        }

        // verify the payload
        if (!$this->module->verifySbnPayload($payload, $signature)) {
            $this->handleError($this->module->l('Invalid Request'), self::RESP_VERIFY_PAYLOAD_ERR);
        }

        // check the call type
        if (!in_array($call_type, $this->supported_calls)) {
            $this->handleError($this->module->l('Unsupported'), self::RESP_UNSUPPORTED_CALL_ERR);
        }

        // process
        $message = null;
        $code = null;

        try {
            $success = $this->processPayload($call_type, $payload, $message, $code);
        } catch (Exception $e) {
            $success = false;
            $msg = $e->getMessage() . ' - ' . $e->getTraceAsString() . ' - ' . $e->getFile() . ' - ' . $e->getLine();
            $this->module->getLogger()->info(get_class($this) . ': notify exception :: ' . $msg);

            $this->handleError($msg, self::RESP_GENERIC_ERR);
        }

        $this->handleResponse($message, $code, $success);
    }

    protected function processPayload($call_type, array $payload, & $message, & $code)
    {
        $message = null;
        $code = null;
        $ret = true;

        switch ($call_type) {
            case self::CALL_TYPE_ORDER_STATE_CHANGE : {

                $new_order_state = isset($payload['state']) ? $payload['state'] : null;
                $order_id = isset($payload['order_id']) ? $payload['order_id'] : null;

                if ($order_id) {
                    /** @var \Payin7\Models\OrderModel $order */
                    $order = $this->module->getModelInstance('order');
                    $loaded = $order->loadPayin7Data($order_id);

                    if ($loaded) {
                        return $this->processOrderStateChange($new_order_state, $payload, $order, $message, $code);
                    } else {
                        $message = 'Local order could not be loaded';
                    }
                } else {
                    $message = 'Order ID missing';
                }

                break;
            }
            case self::CALL_TYPE_PING : {
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

    protected function processOrderStateChange($new_order_state, array $payload, \Payin7\Models\OrderModel $order, & $message,
        /** @noinspection PhpUnusedParameterInspection */
                                               & $code)
    {
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

    protected function handleResponse($message, $code, $success = true)
    {
        if (!$success) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        echo ($success ? 'OK' : 'KO') . "\n" .
            (int)$code . "\n" .
            $message . "\n" .
            json_encode(array_filter(array(
                (isset($_POST) ? $_POST : null),
                (isset($_GET) ? $_GET : null),
            )));
        exit(0);
    }
}
