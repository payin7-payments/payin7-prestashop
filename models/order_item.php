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
 * Class OrderItemModel
 * @package Payin7\Models
 * @method int getId()
 * @method void setId($value = null)
 * @method int getProductId()
 * @method void setProductId($value = null)
 * @method string getName()
 * @method void setName($value = null)
 * @method string getSku()
 * @method void setSku($value = null)
 * @method string getProductUrl()
 * @method void setProductUrl($value = null)
 * @method string getImageUrl()
 * @method void setImageUrl($value = null)
 * @method string getShortDescription()
 * @method void setShortDescription($value = null)
 * @method string getFullDescription()
 * @method void setFullDescription($value = null)
 * @method bool getIsVirtual()
 * @method void setIsVirtual($value = null)
 * @method int getQtyOrdered()
 * @method void setQtyOrdered($value = null)
 * @method bool getIsQtyDecimal()
 * @method void setIsQtyDecimal($value = null)
 * @method double getPriceInclTax()
 * @method void setPriceInclTax($value = null)
 * @method double getPrice()
 * @method void setPrice($value = null)
 * @method double getTaxAmount()
 * @method void setTaxAmount($value = null)
 * @method double getHiddenTaxAmount()
 * @method void setHiddenTaxAmount($value = null)
 * @method double getTaxBeforeDiscount()
 * @method void setTaxBeforeDiscount($value = null)
 * @method double getShippingAmountWithTax()
 * @method void setShippingAmountWithTax($value = null)
 * @method double getShippingAmount()
 * @method void setShippingAmount($value = null)
 * @method void setPriceBeforeDiscount($value = null)
 * @method double getPriceBeforeDiscount()
 * @method double getDiscountAmount()
 * @method void setDiscountAmount($value = null)
 * @method double getDiscountAmountWithTax()
 * @method void setDiscountAmountWithTax($value = null)
 * @method double getRowTotal()
 * @method void setRowTotal($value = null)
 * @method double getRowTotalInclTax()
 * @method void setRowTotalInclTax($value = null)
 * @method double getTaxRate()
 * @method void setTaxRate($value = null)
 *
 */
class OrderItemModel extends BaseModel
{
}