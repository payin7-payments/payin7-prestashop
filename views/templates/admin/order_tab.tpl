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

<div class="row">
    <div class="col-lg-12">
        <div class="well panel payin7-order-details">
            <div class="panel-heading">
                <i class="icon-shopping-cart"></i>
                {l s='Payin7 Order Details' mod='payin7'}
            </div>
            <div class="panel-body">
                <p>{l s='Order Type:' mod='payin7'} <span class="val">{$order_type|escape:'html':'UTF-8'}</span></p>
                <p>{l s='Submitted to Payin7:' mod='payin7'} <span
                            class="val">{$order_submitted|escape:'html':'UTF-8'}</span></p>
                <p>{l s='Completed by Customer:' mod='payin7'} <span
                            class="val">{$order_completed|escape:'html':'UTF-8'}</span></p>
                <p>{l s='Payin7 Order Identifier:' mod='payin7'} <span
                            class="val">{$order_identifier|escape:'html':'UTF-8'}</span></p>
                {if $order_payin7_backend_link}
                    <p><a href="{$order_payin7_backend_link|escape:'html':'UTF-8'}" class="open-in-payin7"
                          target="_blank">{l s='Open in Payin7' mod='payin7'}</a></p>
                {/if}
            </div>
        </div>
    </div>
</div>
<!-- Payin7 -->
<script>
    //<![CDATA[
    Payin7SDK('adminOrderView', {$order_identifier_js});
    //]]>
</script>
<!-- End Payin7 -->