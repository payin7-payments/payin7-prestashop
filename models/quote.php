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

use Context;

require_once(__DIR__ . DS . 'order_item.php');
require_once(__DIR__ . DS . 'customer.php');
require_once(__DIR__ . DS . 'address.php');

/**
 * Class QuoteModel
 * @package Payin7\Models
 * @method string getPaymentMethodCode()
 * @method void setPaymentMethodCode($value = null)
 * @method string getCurrencyCode()
 * @method void setCurrencyCode($value = null)
 * @method string getShippingMethodCode()
 * @method void setShippingMethodCode($value = null)
 * @method string getShippingMethodTitle()
 * @method void setShippingMethodTitle($value = null)
 * @method string getCreatedAt()
 * @method void setCreatedAt($value = null)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt($value = null)
 * @method string getState()
 * @method void setState($value = null)
 * @method string getStatus()
 * @method void setStatus($value = null)
 * @method int getGiftMessageId()
 * @method void setGiftMessageId($value = null)
 * @method int getQuoteId()
 * @method void setQuoteId($value = null)
 * @method double getSubtotalInclTax()
 * @method void setSubtotalInclTax($value = null)
 * @method double getSubtotal()
 * @method void setSubtotal($value = null)
 * @method double getTaxAmount()
 * @method void setTaxAmount($value = null)
 * @method double getHiddenTaxAmount()
 * @method void setHiddenTaxAmount($value = null)
 * @method double getShippingInclTax()
 * @method void setShippingInclTax($value = null)
 * @method double getShippingAmount()
 * @method void setShippingAmount($value = null)
 * @method double getDiscountAmount()
 * @method void setDiscountAmount($value = null)
 * @method double getShippingDiscountAmount()
 * @method void setShippingDiscountAmount($value = null)
 * @method double getGrandTotal()
 * @method void setGrandTotal($value = null)
 * @method int getOrderedItems()
 * @method void setOrderedItems($value = null)
 *
 */
class QuoteModel extends BaseModel
{
    /** @var AddressModel[] */
    protected $_addresses;

    /** @var OrderItemModel[] */
    protected $_items;

    /** @var CustomerModel */
    protected $_customer;

    protected $_customer_is_guest;

    public function initialize(Context $context, $module)
    {
        parent::initialize($context, $module);

        if (!$this->getBillingAddress()) {
            $billing = new AddressModel();
            $billing->initialize($context, $module);
            $billing->setAddressType(AddressModel::TYPE_BILLING);
            $this->setAddress(AddressModel::TYPE_BILLING, $billing);
        }

        if (!$this->getShippingAddress()) {
            $shipping = new AddressModel();
            $shipping->initialize($context, $module);
            $shipping->setAddressType(AddressModel::TYPE_SHIPPING);
            $this->setAddress(AddressModel::TYPE_SHIPPING, $shipping);
        }

        if (!$this->getCustomer()) {
            $customer = new CustomerModel();
            $customer->initialize($context, $module);
            $this->setCustomer($customer);
        }
    }

    /**
     * @param AddressModel[] $addresses
     */
    public function setAddresses(array $addresses)
    {
        $this->_addresses = $addresses;
    }

    public function getAddresses()
    {
        return $this->_addresses;
    }

    public function setAddress($address_type, AddressModel $address)
    {
        $this->_addresses[$address_type] = $address;
    }

    public function getBillingAddress()
    {
        return (isset($this->_addresses[AddressModel::TYPE_BILLING]) ? $this->_addresses[AddressModel::TYPE_BILLING] : null);
    }

    public function getShippingAddress()
    {
        return (isset($this->_addresses[AddressModel::TYPE_SHIPPING]) ? $this->_addresses[AddressModel::TYPE_SHIPPING] : null);
    }

    /**
     * @param OrderItemModel[] $items
     */
    public function setItems(array $items)
    {
        $this->_items = $items;
    }

    public function getItems()
    {
        return $this->_items;
    }

    public function addItem(OrderItemModel $item)
    {
        $this->_items[] = $item;
    }

    public function setCustomer(CustomerModel $customer)
    {
        $this->_customer = $customer;
    }

    public function getCustomer()
    {
        return $this->_customer;
    }

    public function setCustomerIsGuest($is_guest)
    {
        $this->_customer_is_guest = $is_guest;
    }

    public function getCustomerIsGuest()
    {
        return $this->_customer_is_guest;
    }
}
