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

{foreach from=$payment_methods item=payment_method name=payment_methods}
    {if !$is15up}
        <p class="payment_module payin7" data-code="{$payment_method.code|escape:'html':'UTF-8'}">

            <a class="pay" href="{$payment_method.url}"
               title="{$payment_method.title|escape:'html':'UTF-8'}">
                {if $payment_method.is_unavailable}<span style="font-weight:bold; color:red;">UNAVAILABLE,
                    reason: {$payment_method.unavailability_reason|escape:'html':'UTF-8'}</span>{/if}

                {if $payment_method.remote_config.logo}
                    <img class="method-logo" src="{$payment_method.remote_config.logo|escape:'html':'UTF-8'}"
                         alt="{$payment_method.title|escape:'html':'UTF-8'}"/>
                {/if}
                {$payment_method.title|escape:'html':'UTF-8'}
                {if $payment_method.remote_config.checkout_content}
                    <span>{$payment_method.remote_config.checkout_content|escape:'html':'UTF-8'}</span>
                {/if}
            </a>
            {if $payment_method.remote_config.more_info_content}
                <a class="more-info fancybox"
                   href="#payin7-more-info-{$payment_method.code|escape:'html':'UTF-8'}-container"></a>
            {else}
                {if $payment_method.remote_config.more_info_url}
                    <a class="more-info fancybox"
                       href="{$payment_method.remote_config.more_info_url|escape:'html':'UTF-8'}"
                       target="{$payment_method.remote_config.more_info_target|escape:'html':'UTF-8'}"></a>
                {/if}
            {/if}
        </p>
    {else}
        <div class="row">
            <div class="col-xs-12">
                <p class="payment_module payin7" id="payin7_payment_button"
                   data-code="{$payment_method.code|escape:'html':'UTF-8'}">

                    <a class="pay" href="{$payment_method.url}"
                       title="{$payment_method.title|escape:'html':'UTF-8'}">
                        {if $payment_method.is_unavailable}<span style="font-weight:bold; color:red;">UNAVAILABLE,
                            reason: {$payment_method.unavailability_reason|escape:'html':'UTF-8'}</span>{/if}

                        {if $payment_method.remote_config.logo}
                            <img class="method-logo"
                                 src="{$payment_method.remote_config.logo|escape:'html':'UTF-8'}"
                                 alt="{$payment_method.title|escape:'html':'UTF-8'}"/>
                        {/if}
                        {$payment_method.title|escape:'html':'UTF-8'}
                        {if $payment_method.remote_config.checkout_content}
                            <span>{$payment_method.remote_config.checkout_content|escape:'html':'UTF-8'}</span>
                        {/if}
                    </a>
                    {if $payment_method.remote_config.more_info_content}
                        <a class="more-info fancybox"
                           href="#payin7-more-info-{$payment_method.code|escape:'html':'UTF-8'}-container"></a>
                    {else}
                        {if $payment_method.remote_config.more_info_url}
                            <a class="more-info fancybox"
                               href="{$payment_method.remote_config.more_info_url|escape:'html':'UTF-8'}"
                               target="{$payment_method.remote_config.more_info_target|escape:'html':'UTF-8'}"></a>
                        {/if}
                    {/if}
                </p>
            </div>
        </div>
    {/if}
    <div id="payin7-more-info-{$payment_method.code|escape:'html':'UTF-8'}-container"
         class="payin7-more-info-container">
        {$payment_method.remote_config.more_info_content|escape:'html':'UTF-8'}
    </div>
{/foreach}

<!-- Payin7 -->
<script>
    //<![CDATA[
    var payin7ScriptSrc = {$payin7_script_src};
    {literal}
    (function(a,b,c,d,e,f,g){a['Payin7SDKObject']=e;a[e]=a[e]||function(){
                (a[e].q=a[e].q||[]).push(arguments)};a[e].l=1*new Date();f=b.createElement(c);
        g=b.getElementsByTagName(c)[0];f.async=1;f.src=d;g.parentNode.insertBefore(f,g)
    })(window,document,'script',payin7ScriptSrc,'Payin7SDK');
    {/literal}

    Payin7SDK('init', {$js_config});
    Payin7SDK('checkout', {$checkout_options});
    //]]>
</script>
<!-- End Payin7 -->