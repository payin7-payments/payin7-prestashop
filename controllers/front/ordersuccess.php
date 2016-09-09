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
require_once(__DIR__ . DS . '_orderret.php');

class Payin7OrderSuccessModuleFrontController extends Payin7OrderRetModuleFrontController
{
    public function execute()
    {
        $this->module->getLogger()->info(get_class($this) . ': ordersuccess :: ' . print_r($_POST, true));

        // check if POST
        $this->verifyIsPost();

        //$this->clearCart();
        // clear the cart - do not delete it as we might have not yet received a payin7 notification
        // and if the cart item is deleted - the background notification will not be able to convert it into an order!
        $this->context->cart = new Cart();

        $this->context->smarty->assign(array_merge($this->module->getPayin7SDKTemplateParams(), array(/*'order_identifier' => json_encode($order->getPayin7OrderIdentifier())*/
        )));

        if ($this->module->getIsPrestashop14()) {
            $this->context->smarty->display(_PS_MODULE_DIR_ . 'payin7/views/templates/front/success.tpl');
        } else {
            return $this->setTemplate('success.tpl');
        }

        return null;
    }
}
