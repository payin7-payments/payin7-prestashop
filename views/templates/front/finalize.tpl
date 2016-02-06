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

{capture name=path}{l s='Payin7 Order Confirmation' mod='payin7'}{/capture}
{if version_compare($smarty.const._PS_VERSION_,'1.6.0.0','<')}{include file="$tpl_dir./breadcrumb.tpl"}{/if}
<div class="payin7 finalize"></div>
<!-- Payin7 -->
<script>
    //<![CDATA[
    Payin7SDK('finalizeOrder', {$order_data}, {
        dialog: {
            animated: false,
            confirmCloseCancel: true,
            cancelOnMaskClick: false
        }
    });
    //]]>
</script>
<!-- End Payin7 -->