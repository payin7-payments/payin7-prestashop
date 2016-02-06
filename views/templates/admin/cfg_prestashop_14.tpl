{*/**
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
*/*}

<form action="" name="submitPayin7Settings"
      id="submitPayin7Settings" method="post">
    <input type="hidden" name="submitPayin7Settings" value="1"/>
    <fieldset>
        <legend>
            <img src="../img/admin/contact.gif"/>{l s='Payin7 Integration' mod='payin7'}</legend>
        <table border="0" width="100%" cellpadding="0" cellspacing="0" id="form">
            <tr>
                <td colspan="2" style="padding-bottom: 20px;">
                    <p style="float:left; width: 700px">
                        {l s='Below you can find all the configurable settings of Payin7 integration. If you are uncertain what settings to use please visit the Customer Support URL below and login with your username and password: ' mod='payin7'}
                        <br/><br/><a href="https://customers.payin7.com" target="_blank"
                                     style="font-weight: bold; text-decoration: underline;">https://customers.payin7.com</a>
                    </p>
                    <a href="https://customers.payin7.com" target="_blank"><img
                                style="display: inline-block; text-align: left; float: right; border: none;" width="100"
                                src="{$module_dir|escape:'html':'UTF-8'}views/img/logo_512x512.png"/></a>
                </td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;"><label
                            for="PAYIN7_API_SANDBOX_MODE">{l s='Sandbox mode' mod='payin7'}</label></td>
                <td><select name="PAYIN7_API_SANDBOX_MODE" id="PAYIN7_API_SANDBOX_MODE" size="1" class="selects">
                        {html_options values=$selectValues output=$outputEnvironment selected=$formConfigValues.PAYIN7_API_SANDBOX_MODE}
                    </select></td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;"><label
                            for="PAYIN7_API_USE_SECURE">{l s='Secure communication' mod='payin7'}</label></td>
                <td><select name="PAYIN7_API_USE_SECURE" id="PAYIN7_API_USE_SECURE" size="1" class="selects">
                        {html_options values=$selectValues output=$outputEnvironment selected=$formConfigValues.PAYIN7_API_USE_SECURE}
                    </select></td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;"><label
                            for="PAYIN7_API_DEBUG_MODE">{l s='Debugging' mod='payin7'}</label></td>
                <td><select name="PAYIN7_API_DEBUG_MODE" id="PAYIN7_API_DEBUG_MODE" size="1" class="selects">
                        {html_options values=$selectValues output=$outputEnvironment selected=$formConfigValues.PAYIN7_API_DEBUG_MODE}
                    </select></td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;"><label
                            for="PAYIN7_API_INTEGRATION_ID">{l s='Integration ID' mod='payin7'}</label></td>
                <td><input type="text" name="PAYIN7_API_INTEGRATION_ID" id="PAYIN7_API_INTEGRATION_ID"
                           value="{$formConfigValues.PAYIN7_API_INTEGRATION_ID|escape:'htmlall':'UTF-8'}"
                           style="width: 300px;"/></td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;"><label
                            for="PAYIN7_API_SANDBOX_KEY">{l s='Sandbox API Key' mod='payin7'}</label></td>
                <td><input type="text" name="PAYIN7_API_SANDBOX_KEY" id="PAYIN7_API_SANDBOX_KEY"
                           value="{$formConfigValues.PAYIN7_API_SANDBOX_KEY|escape:'htmlall':'UTF-8'}"
                           style="width: 300px;"/></td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;"><label
                            for="PAYIN7_API_PRODUCTION_KEY">{l s='Production API Key' mod='payin7'}</label></td>
                <td><input type="text" name="PAYIN7_API_PRODUCTION_KEY" id="PAYIN7_API_PRODUCTION_KEY"
                           value="{$formConfigValues.PAYIN7_API_PRODUCTION_KEY|escape:'htmlall':'UTF-8'}"
                           style="width: 300px;"/></td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;"><label
                            for="PAYIN7_API_VERSION">{l s='API Version' mod='payin7'}</label></td>
                <td><input type="text" name="PAYIN7_API_VERSION" id="PAYIN7_API_VERSION"
                           value="{$formConfigValues.PAYIN7_API_VERSION|escape:'htmlall':'UTF-8'}"
                           style="width: 50px;"/></td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;"><label
                            for="PAYIN7_API_SERVER_HOSTNAME">{l s='Server Hostname' mod='payin7'}</label></td>
                <td><input type="text" name="PAYIN7_API_SERVER_HOSTNAME" id="PAYIN7_API_SERVER_HOSTNAME"
                           value="{$formConfigValues.PAYIN7_API_SERVER_HOSTNAME|escape:'htmlall':'UTF-8'}"
                           style="width: 300px;"/></td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;"><label
                            for="PAYIN7_API_SERVER_PORT">{l s='Server Port' mod='payin7'}</label></td>
                <td><input type="text" name="PAYIN7_API_SERVER_PORT" id="PAYIN7_API_SERVER_PORT"
                           value="{$formConfigValues.PAYIN7_API_SERVER_PORT|escape:'htmlall':'UTF-8'}"
                           style="width: 50px;"/></td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;"><label
                            for="PAYIN7_PAYMENT_MIN_ORDER_AMOUNT">{l s='Minimum Order Amount' mod='payin7'}</label></td>
                <td><input type="text" name="PAYIN7_PAYMENT_MIN_ORDER_AMOUNT" id="PAYIN7_PAYMENT_MIN_ORDER_AMOUNT"
                           value="{$formConfigValues.PAYIN7_PAYMENT_MIN_ORDER_AMOUNT|escape:'htmlall':'UTF-8'}"
                           style="width: 50px;"/></td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;"><label
                            for="PAYIN7_PAYMENT_MAX_ORDER_AMOUNT">{l s='Maximum Order Amount' mod='payin7'}</label></td>
                <td><input type="text" name="PAYIN7_PAYMENT_MAX_ORDER_AMOUNT" id="PAYIN7_PAYMENT_MAX_ORDER_AMOUNT"
                           value="{$formConfigValues.PAYIN7_PAYMENT_MAX_ORDER_AMOUNT|escape:'htmlall':'UTF-8'}"
                           style="width: 50px;"/></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td><input class="button" name="btnSubmit"
                           value="{l s='Update' mod='payin7'}" type="submit"/></td>
            </tr>
        </table>
    </fieldset>
</form>