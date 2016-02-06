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

require_once(__DIR__ . DS . 'platform_abstract.php');

class PlatformStatusModel extends PlatformAbstractModel
{
    public function getConfigKey()
    {
        return 'platform_status';
    }

    public function getApiMethod()
    {
        return 'getPlatformStatus';
    }

    public function getIsPlatformAvailable()
    {
        $platform_status = $this->getPlatformStatus();
        $integration_status = $this->getIntegrationStatus();

        $platform_is_available =
            isset($platform_status['state']) && $platform_status['state'] &&
            isset($integration_status['state']) && $integration_status['state'];
        return $platform_is_available;
    }

    public function getPlatformStatus()
    {
        return $this->getData('platform');
    }

    public function getIntegrationStatus()
    {
        return $this->getData('integration');
    }

    public function getPaymentMethods()
    {
        return $this->getData('payment_methods');
    }

    public function getIsPaymentMethodAvailable($remote_method_code)
    {
        $payment_methods_status = $this->getPaymentMethods();
        return isset($payment_methods_status[$remote_method_code]) && $payment_methods_status[$remote_method_code];
    }
}