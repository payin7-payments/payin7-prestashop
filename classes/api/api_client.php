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

namespace Payin7\API;

/** @noinspection PhpIncludeInspection */
use Payin7Payments\Exception\Payin7APIException;
use Payin7Payments\Payin7PaymentsClient;

require_once(__DIR__ . DS . 'payin7-php' . DS . 'vendor' . DS . 'autoload.php');

class ApiClient
{
    const API_CLIENT_CONNECT_TIMEOUT = 10; // in seconds
    const API_CLIENT_TIMEOUT = 60; // in seconds

    const ERR_SERVER_CODE = 20;
    const ERR_CLIENT_CODE = 30;
    const ERR_SYSTEM_CODE = 40;

    /** @var Payin7PaymentsClient */
    protected $_api_client;

    protected $_ssl_verification = true;
    protected $_base_url;

    protected $_integration_id;
    protected $_api_key;

    protected $_connect_timeout = self::API_CLIENT_CONNECT_TIMEOUT;
    protected $_timeout = self::API_CLIENT_TIMEOUT;

    public function getApi()
    {
        return $this->_api_client;
    }

    public function setSslVerification($verification = true)
    {
        $this->_ssl_verification = $verification;
    }

    public function setBaseUrl($url)
    {
        $this->_base_url = $url;
    }

    public function setIntegrationKey($integration_id)
    {
        $this->_integration_id = $integration_id;
    }

    public function setApiKey($api_key)
    {
        $this->_api_key = $api_key;
    }

    public function setConnectTimeout($timeout)
    {
        $this->_connect_timeout = ($timeout ? $timeout : self::API_CLIENT_CONNECT_TIMEOUT);
    }

    public function setTimeout($timeout)
    {
        $this->_timeout = ($timeout ? $timeout : self::API_CLIENT_TIMEOUT);
    }

    protected function _configureDefaults()
    {
        $client = Payin7PaymentsClient::getInstance(array(
            'integration_id' => $this->_integration_id,
            'integration_key' => $this->_api_key,

            'timeout' => $this->_timeout,
            'connect_timeout' => $this->_connect_timeout
        ));

        if (!$this->_ssl_verification) {
            $client->setSslVerification(false, false);
        }

        $client->setBaseUrl($this->_base_url);
        $this->_api_client = $client;
    }

    protected function _logResponseException(\Exception $e)
    {
        if ($e instanceof Payin7APIException) {
            $response = $e->getResponse();
            $code = $response->getStatusCode();
            $body = $response->getBody();

            error_log("[API SERVER ERROR] Status Code: {$code} | Body: {$body}");
        } else {
            error_log("[API SERVER ERROR] " . $e->getMessage() . ', Code: ' . $e->getCode());
        }
    }

    protected function _callApi($api_method, array $data = null)
    {
        $this->_configureDefaults();

        try {
            return $this->_api_client->$api_method($data);
        } catch (\Exception $e) {
            $this->_logResponseException($e);
            throw $e;
        }
    }

    public function getPlatformStatus()
    {
        return $this->_callApi(__FUNCTION__);
    }

    public function getPlatformConfig()
    {
        return $this->_callApi(__FUNCTION__);
    }

    public function postOrderHistory(array $data)
    {
        return $this->_callApi(__FUNCTION__, $data);
    }

    public function postOrder(array $data)
    {
        return $this->_callApi(__FUNCTION__, $data);
    }

    public function updateOrder(array $data)
    {
        return $this->_callApi(__FUNCTION__, $data);
    }
}