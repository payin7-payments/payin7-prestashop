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
 * Class CustomerModel
 * @package Payin7\Models
 * @method int getCustomerId()
 * @method void setCustomerId($value = null)
 * @method string getGender()
 * @method void setGender($value = null)
 * @method bool getIsVerified()
 * @method void setIsVerified($value = null)
 * @method string getPreferredLanguageCode()
 * @method void setPreferredLanguageCode($value = null)
 * @method string getLastLoginAt()
 * @method void setLastLoginAt($value = null)
 * @method string getCreatedAt()
 * @method void setCreatedAt($value = null)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt($value = null)
 * @method string getDob()
 * @method void setDob($value = null)
 * @method string getEmail()
 * @method void setEmail($value = null)
 * @method string getTitle()
 * @method void setTitle($value = null)
 * @method string getPrefix()
 * @method void setPrefix($value = null)
 * @method string getSuffix()
 * @method void setSuffix($value = null)
 * @method string getFirstName()
 * @method void setFirstName($value = null)
 * @method string getMiddleName()
 * @method void setMiddleName($value = null)
 * @method string getLastName()
 * @method void setLastName($value = null)
 * @method string getCompany()
 * @method void setCompany($value = null)
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
 * @method string getRegIPAddress()
 * @method void setRegIPAddress($value = null)
 * @method int getOrdersCount()
 * @method void setOrdersCount($value = null)
 */
class CustomerModel extends BaseModel
{
    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';
}
