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

class OrderSubmitModel extends BaseModel
{
    /**
     * @var OrderModel
     */
    protected $_order;

    /**
     * @var string
     */
    protected $_device_type;

    /**
     * @var string
     */
    protected $_source;

    /**
     * @var array
     */
    protected $_sysinfo;

    /**
     * @var string
     */
    protected $_ordered_by_ip_address;

    /**
     * @var string
     */
    protected $_language_code;

    /**
     * @param OrderModel $order
     */
    public function setOrder(OrderModel $order)
    {
        $this->_order = $order;
    }

    public function setDeviceType($device_type = null)
    {
        $this->_device_type = $device_type;
    }

    public function setLanguageCode($lang_code = null)
    {
        $this->_language_code = $lang_code;
    }

    public function setSource($source = null)
    {
        $this->_source = $source;
    }

    public function setSysinfo(array $sysinfo = null)
    {
        $this->_sysinfo = $sysinfo;
    }

    public function setOrderedByIpAddress($ip_address = null)
    {
        $this->_ordered_by_ip_address = $ip_address;
    }

    /**
     * @return array
     */
    protected function _prepareOrderData()
    {
        $data = array_filter(array(
            'currency_code' => $this->_order->getCurrencyCode(),
            'shipping_method_code' => $this->_order->getShippingMethodCode(),
            'shipping_method_title' => $this->_order->getShippingMethodTitle(),
            'created_on' => $this->_order->getCreatedAt(),
            'updated_on' => $this->_order->getUpdatedAt(),
            'state' => $this->_order->getState(),
            'status' => $this->_order->getStatus(),
            'is_gift' => $this->_order->getGiftMessageId() != null,
            'ref_quote_id' => $this->_order->getQuoteId(),
            'cart_secure_key' => $this->_order->getCartSecureKey(),
            'order_subtotal_with_tax' => $this->_order->getSubtotalInclTax(),
            'order_subtotal' => $this->_order->getSubtotal(),
            'order_tax' => $this->_order->getTaxAmount(),
            'order_hidden_tax' => $this->_order->getHiddenTaxAmount(),
            'order_shipping_with_tax' => $this->_order->getShippingInclTax(),
            'order_shipping' => $this->_order->getShippingAmount(),
            'order_discount' => $this->_order->getDiscountAmount(),
            'order_shipping_discount' => $this->_order->getShippingDiscountAmount(),
            'order_total' => $this->_order->getGrandTotal(),
            'order_total_items' => $this->_order->getOrderedItems()
        ));
        return $data;
    }

    /**
     * @return array
     */
    protected function _prepareOrderAddresses()
    {
        /** @var AddressModel[] $addresses */
        $addresses = $this->_order->getAddresses();
        $data = array();

        if ($addresses) {
            foreach ($addresses as $address) {
                $address_data = array_filter(array(
                    'store_address_id' => $address->getCustomerAddressId(),
                    'type' => $address->getAddressType(),
                    'title' => '',
                    'prefix' => $address->getPrefix(),
                    'suffix' => $address->getSuffix(),
                    'first_name' => $address->getFirstname(),
                    'middle_name' => $address->getMiddlename(),
                    'last_name' => $address->getLastname(),
                    'company_name' => $address->getCompany(),
                    'street_address_1' => $address->getStreet1(),
                    'street_address_2' => $address->getStreet2(),
                    'street_address_3' => $address->getStreet3(),
                    'street_address_4' => $address->getStreet4(),
                    'city' => $address->getCity(),
                    'country_code' => $address->getCountry(),
                    'state' => $address->getCountryState(),
                    'region' => $address->getRegion(),
                    'region_code' => $address->getRegionCode(),
                    'zip_code' => $address->getPostcode(),
                    'telephone1' => $address->getTelephone1(),
                    'telephone2' => $address->getTelephone2(),
                    'telephone3' => $address->getTelephone3(),
                    'fax' => $address->getFax(),
                    'vat_number' => $address->getData('customer_taxvat')
                ));
                $data[] = $address_data;
                unset($address);
            }
        }
        return $data;
    }

    /**
     * @return array
     */
    protected function _prepareOrderItems()
    {
        /** @var OrderItemModel[] $items */
        $items = $this->_order->getItems();
        $data = array();

        if ($items) {
            foreach ($items as $item) {
                $item_data = array_filter(array(
                    'item_id' => $item->getId(),
                    'product_id' => $item->getProductId(),
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'url' => $item->getProductUrl(),
                    'image_url' => $item->getImageUrl(),
                    'details' => $item->getShortDescription(),
                    'details_full' => $item->getFullDescription(),
                    'is_virtual' => $item->getIsVirtual(),
                    'quantity' => $item->getQtyOrdered(),
                    'quantity_is_decimal' => $item->getIsQtyDecimal(),
                    'item_subtotal_with_tax' => $item->getPriceInclTax(),
                    'item_subtotal' => $item->getPrice(),
                    'item_tax' => $item->getTaxAmount(),
                    'item_hidden_tax' => $item->getHiddenTaxAmount(),
                    'item_tax_before_discount' => $item->getTaxBeforeDiscount(),
                    'item_shipping_with_tax' => $item->getShippingAmountWithTax(),
                    'item_shipping' => $item->getShippingAmount(),
                    'item_total_before_discount' => $item->getPriceBeforeDiscount(),
                    'item_discount' => $item->getDiscountAmount(),
                    'item_discount_with_tax' => $item->getDiscountAmountWithTax(),
                    'item_total' => $item->getRowTotal(),
                    'item_total_with_tax' => $item->getRowTotalInclTax(),
                    'item_tax_rate' => $item->getTaxRate()
                ));
                $data[] = $item_data;
                unset($item);
            }
        }
        return $data;
    }

    /**
     * @return array
     */
    protected function _prepareCustomerData()
    {
        $customer = $this->_order->getCustomer();

        $data = array_filter(array(
            'customer_id' => $customer->getCustomerId(),
            'customer_is_guest' => $this->_order->getCustomerIsGuest(),
            'verified' => $customer->getIsVerified(),
            'language_code' => $customer->getPreferredLanguageCode(),
            'last_login_on' => $customer->getLastLoginAt(),
            'created_on' => $customer->getCreatedAt(),
            'updated_on' => $customer->getUpdatedAt(),
            'birthdate' => $customer->getDob(),
            'email' => $customer->getEmail(),
            'title' => $customer->getTitle(),
            'prefix' => $customer->getPrefix(),
            'suffix' => $customer->getSuffix(),
            'first_name' => $customer->getFirstName(),
            'middle_name' => $customer->getMiddleName(),
            'last_name' => $customer->getLastName(),
            'company_name' => $customer->getCompany(),
            'gender' => $customer->getGender(),
            'telephone1' => $customer->getTelephone1(),
            'telephone2' => $customer->getTelephone2(),
            'telephone3' => $customer->getTelephone3(),
            'fax' => $customer->getFax(),
            'vat_number' => $customer->getTaxVATNumber(),
            'reg_ip_address' => $customer->getRegIPAddress(),
            'customer_orders_count' => $customer->getOrdersCount()
        ));
        return $data;
    }

    public function getPayin7OrderIdentifier()
    {
        return ($this->_order ? $this->_order->getPayin7OrderIdentifier() : null);
    }

    public function getPayin7IsOrderSent()
    {
        return ($this->_order ? $this->_order->getPayin7OrderSent() : null);
    }

    public function updateOrder()
    {
        if (!$this->_order) {
            return false;
        }

        $this->module->getLogger()->info('Will submit order update to Payin7, ID: ' . $this->_order->getOrderId());

        // begin submitting

        $data = array(
            'order_id' => $this->_order->getOrderId(),
            'unique_order_id' => $this->_order->getPayin7OrderIdentifier(),
            'payment_method' => $this->_order->getPaymentMethodCode(),
            'device_type' => $this->_device_type,
            'ordered_by_ip_address' => $this->_ordered_by_ip_address,
            'source' => $this->_source,
            'order' => json_encode($this->_prepareOrderData()),
            'items' => json_encode($this->_prepareOrderItems()),
            'addresses' => json_encode($this->_prepareOrderAddresses()),
            'customer' => json_encode($this->_prepareCustomerData()),
            'sysinfo' => json_encode($this->_sysinfo)
        );

        $this->module->getLogger()->info('Submitting order update to Payin7: ' . print_r(array(
                'order_id' => $this->_order->getOrderId(),
                'payment_method' => $this->_order->getPaymentMethodCode()
            ), true));

        $client = $this->module->getApiClientInstance();
        $response = $client->updateOrder($data);

        if (!$response['payin7_order_id']) {
            return false;
        }

        // update the order
        $this->_order->setPayin7OrderIdentifier($response['payin7_order_id']);
        $this->_order->setPayin7OrderSent(true);
        $this->_order->setPayin7OrderAccepted((bool)$response['is_accepted']);
        $this->_order->setPayin7AccessToken($response['access_token']);

        $this->module->getLogger()->info('Order update to Payin7, ID: ' .
            $this->_order->getOrderId() . ', data: ' . print_r($response, true));

        return $this;
    }

    public function submitOrder($force = false)
    {
        if (!$this->_order) {
            return false;
        }

        $this->module->getLogger()->info('Will submit order to Payin7, ID: ' . $this->_order->getOrderId());

        $already_sent = $this->getPayin7IsOrderSent();

        // check if already submitted
        if ($already_sent && !$force) {
            $this->module->getLogger()->debug('Order already submitted - not doing anything');
            return $this->getPayin7OrderIdentifier();
        }

        // begin submitting

        $data = array(
            'order_id' => $this->_order->getOrderId(),
            'unique_order_id' => $this->_order->getPayin7OrderIdentifier(),
            'payment_method' => $this->_order->getPaymentMethodCode(),
            'device_type' => $this->_device_type,
            'ordered_by_ip_address' => $this->_ordered_by_ip_address,
            'source' => $this->_source,
            'order' => json_encode($this->_prepareOrderData()),
            'items' => json_encode($this->_prepareOrderItems()),
            'addresses' => json_encode($this->_prepareOrderAddresses()),
            'customer' => json_encode($this->_prepareCustomerData()),
            'sysinfo' => json_encode($this->_sysinfo)
        );

        $this->module->getLogger()->info(($already_sent ? 'RE-' : null) . 'Submitting order to Payin7: ' . print_r(array(
                'order_id' => $this->_order->getOrderId(),
                'payment_method' => $this->_order->getPaymentMethodCode()
            ), true));

        $client = $this->module->getApiClientInstance();
        $response = $client->postOrder($data);

        if (!$response['payin7_order_id']) {
            return false;
        }

        // update the order
        $this->_order->setPayin7OrderIdentifier($response['payin7_order_id']);
        $this->_order->setPayin7OrderSent(true);
        $this->_order->setPayin7OrderAccepted((bool)$response['is_accepted']);
        $this->_order->setPayin7AccessToken($response['access_token']);

        $this->module->getLogger()->info('Order ' . ($already_sent ? 're' : null) . 'submitted to Payin7, ID: ' .
            $this->_order->getOrderId() . ', data: ' . print_r($response, true));

        return $this;
    }
}