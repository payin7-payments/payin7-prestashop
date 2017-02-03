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

if (!defined('__INC__')) {
    die();
}

include __DIR__ . '/../../config/config.inc.php';

$is14 = version_compare(_PS_VERSION_, '1.5', '<');

if (!$is14) {
    die();
}

$front_ctrl = new FrontController();
$front_ctrl->init();

require_once './payin7.php';
$module = new Payin7();

function _execCompatController($class, $filename)
{
    global $module;

    include __DIR__ . '/../../header.php';

    /** @noinspection PhpIncludeInspection */
    require_once __DIR__ . DS . 'controllers' . DS . 'front' . DS . $filename . '.php';

    $context = Context::getContext();

    $clsn = $class . 'ModuleFrontController';

    /** @var Payin7BaseModuleFrontController $ctrl */
    $ctrl = new $clsn();
    $ctrl->context = $context;
    $ctrl->module = $module;

    try {
        echo $ctrl->execute();
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }

    include dirname(__FILE__) . '/../../footer.php';
}


