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

if (!class_exists('ModuleFrontController')) {
    class ModuleFrontController
    {
        public function init()
        {
            //
        }

        public function initContent()
        {
            //
        }
    }
}

/** @noinspection PhpUndefinedClassInspection */

/**
 * Class Payin7BaseModuleFrontController
 *
 * @property bool display_column_left
 * @property bool display_column_right
 * @method string setTemplate(string $template)
 */
abstract class Payin7BaseModuleFrontController extends ModuleFrontController
{
    const RESP_ERR_BASE = 1000;
    const RESP_SYSTEM_ERR = 10;
    const RESP_REQUEST_ERR = 20;
    const RESP_ACCESS_DENIED = 30;
    const RESP_ACCESS_DENIED_NO_CUST_MATCH = 31;
    const RESP_INVALID_ORDER_ERR = 50;
    const RESP_INVALID_ORDER_STATE_ERR = 51;
    const RESP_INVALID_ORDER_HASH_ERR = 52;
    const RESP_ORDER_SUBMIT_ERR = 60;

    protected $debug;
    protected $redirect_on_error;

    /** @var Payin7 */
    public $module;

    /** @var Context */
    public $context;

    abstract public function execute();

    public function init()
    {
        /** @noinspection PhpUndefinedClassInspection */
        parent::init();
        $this->display_column_left = false;
        $this->display_column_right = false;

        $this->debug = $this->module->getConfigApiDebugMode();
        $this->redirect_on_error = !$this->debug;
    }

    public function initContent()
    {
        /** @noinspection PhpUndefinedClassInspection */
        parent::initContent();

        return $this->execute();
    }

    protected function getControllerOrderUrl()
    {
        $multi_shipping = Tools::getValue('multi-shipping');
        return 'index.php?controller=order' . ($multi_shipping ? '&multi-shipping=' . $multi_shipping : null);
    }

    protected function handleError($message, $code, $force_redirect = false)
    {
        if (!$this->redirect_on_error && !$force_redirect) {
            throw new Exception($message, self::RESP_ERR_BASE + $code);
        } else {
            Tools::redirect($this->getControllerOrderUrl());
            exit(0);
        }
    }
}
