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

/**
 * Class AddressModel
 * @package Payin7\Models
 * @method int getCustomerAddressId()
 * @method void setCustomerAddressId($value = null)
 * @method string getAddressType()
 * @method void setAddressType($value = null)
 * @method string getPrefix()
 * @method void setPrefix($value = null)
 * @method string getSuffix()
 * @method void setSuffix($value = null)
 * @method string getFirstname()
 * @method void setFirstname($value = null)
 * @method string getMiddlename()
 * @method void setMiddlename($value = null)
 * @method string getLastname()
 * @method void setLastname($value = null)
 * @method string getCompany()
 * @method void setCompany($value = null)
 * @method string getStreet1()
 * @method void setStreet1($value = null)
 * @method string getStreet2()
 * @method void setStreet2($value = null)
 * @method string getStreet3()
 * @method void setStreet3($value = null)
 * @method string getStreet4()
 * @method void setStreet4($value = null)
 * @method string getCity()
 * @method void setCity($value = null)
 * @method string getCountry()
 * @method void setCountry($value = null)
 * @method string getCountryState()
 * @method void setCountryState($value = null)
 * @method string getRegion()
 * @method void setRegion($value = null)
 * @method string getRegionCode()
 * @method void setRegionCode($value = null)
 * @method string getPostcode()
 * @method void setPostcode($value = null)
 * @method string getTelephone1()
 * @method void setTelephone1($value = null)
 * @method string getTelephone2()
 * @method void setTelephone2($value = null)
 * @method string getTelephone3()
 * @method void setTelephone3($value = null)
 * @method string getFax()
 * @method void setFax($value = null)
 * @method string getTaxVATNumber()
 * @method void setTaxVATNumber($value = null)
 */
class AddressModel extends BaseModel
{
    const TYPE_SHIPPING = 'shipping';
    const TYPE_BILLING = 'billing';
}