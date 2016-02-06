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

<div class="panel">
    <h3><i class="icon icon-eur"></i> {l s='Payin7' mod='payin7'}</h3>
    <div class="row header">
        <div class="col-lg-8">
            <p><strong>{l s='Finance your Dreams with Payin7!' mod='payin7'}</strong></p><br/>
            <p>
                {l s='Below you can find all the configurable settings of Payin7 integration. If you are uncertain what settings to use please visit the Support URL below and login with your username and password: ' mod='payin7'}
                <br/><a href="https://customers.payin7.com" target="_blank">https://support.payin7.com</a>
            </p>
        </div>
        <div class="col-lg-4 pull-right text-right">
            <img src="{$module_dir|escape:'html':'UTF-8'}views/img/logo_512x512.png"
                 title="{l s='Payin7' mod='payin7'}"
                 class="payin7-config-img col-xs-6 col-md-4 pull-right"/>
        </div>
    </div>
</div>

{if version_compare($smarty.const._PS_VERSION_,'1.6.0.0','<')}
    <style>
        #configuration_toolbar {
            display: none
        }
    </style>
{/if}
