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

require_once __DIR__ . DS . 'platform_abstract.php';

class PlatformConfigModel extends PlatformAbstractModel
{
    public function getConfigKey()
    {
        return 'platform_config';
    }

    public function getApiMethod()
    {
        return 'getPlatformConfig';
    }

    public function getIsPlatformAvailable()
    {
        $platform_status = $this->getData('platform');
        $integration_status = $this->getData('integration');

        $platform_is_available =
            isset($platform_status['state']) && $platform_status['state'] &&
            isset($integration_status['state']) && $integration_status['state'];
        return $platform_is_available;
    }

    public function getIsPaymentMethodAvailable($remote_method_code)
    {
        $payment_methods_status = $this->getData('payment_methods');
        return isset($payment_methods_status[$remote_method_code]) && $payment_methods_status[$remote_method_code];
    }

    public function getPaymentMethodsConfig()
    {
        return $this->getData('payment_methods');
    }

    public function getPaymentMethodConfig($remote_method_code)
    {
        $methods = $this->getPaymentMethodsConfig();
        return (isset($methods[$remote_method_code]) ? $methods[$remote_method_code] : null);
    }
}