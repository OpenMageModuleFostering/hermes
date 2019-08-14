<?php

/**
 * Netresearch Hermes
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @copyright   Copyright (c) 2012 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 *  Sales Order Shipment Create Hermes Block
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      André Herrn <andre.herrn@netresearch.de>
 */
class Netresearch_Hermes_Block_Adminhtml_Sales_Order_Shipment_Create_Hermes extends Mage_Adminhtml_Block_Template
{

    /**
     * 
     * @var Mage_Sales_Model_Shipment
     */
    protected $shipment = null;

    /**
     * 
     * @var Netresearch_Hermes_Helper_Validate_Order
     */
    protected $hermes_validate_order = null;

    /**
     * Get Magento shipment
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getMageShipment()
    {
        return Mage::registry('current_shipment');
    }

    /**
     * Check if shipping method is allowed
     *
     * @return boolean
     */
    public function isAllowedShippingMethod()
    {
        return Mage::getModel('hermes/config')
                ->isAllowedShippingMethod($this->getMageShipment()->getOrder()->getShippingMethod());
    }

    /**
     * Get validate order helper
     *
     * @return Netresearch_Hermes_Helper_Validate_Order
     */
    public function getValidateOrderHelper()
    {
        if (null == $this->hermes_validate_order) {
            $this->hermes_validate_order = Mage::helper("hermes/validate_order");
        }
        return $this->hermes_validate_order;
    }

    /**
     * Check if it is a valid hermes shipment
     *
     * @return boolean
     */
    public function isValidHermesShipment()
    {
        return $this->getValidateOrderHelper()
                ->setOrder($this->getMageShipment()->getOrder())
                ->isValidHermesShipment();
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->getValidateOrderHelper()->getValidationErrors();
    }

    /**
     * checks if the merchand is a tiered price merchand
     * 
     * @return boolean
     */
    public function isTieredPriceMerchand()
    {
        return Mage::getModel('hermes/config')
                ->isTieredPriceMerchant();
    }

    /**
     * retrieves all possible Hermes product classes
     * for the destination country  
     * 
     * @return array the product classes or empty array 
     *               if shipment is not supported by hermes 
     * @throws Exception in case the destination country code 
     *                   is not found in Hermes product list or 
     *                   the product_classes node is missing for the country
     */
    public function getParcelClassesForCountry()
    {
        $parcelClasses = array();
        if ($this->isValidHermesShipment()) {
            $productsList = Mage::getModel('hermes/config')
                ->getListOfProductsProducts();
            $destinationCountryCode = Mage::helper('hermes')
                ->getIso3CodeByIso2Code(
                $this->getMageShipment()
                ->getOrder()
                ->getShippingAddress()
                ->getCountryId());
            if (!array_key_exists($destinationCountryCode, $productsList))
                throw new Netresearch_Hermes_Block_Adminhtml_Sales_Order_Shipment_Create_Exception(
                    sprintf('destination country %s is not found in Hermes Product List', $destinationCountryCode));
            if (!array_key_exists('product_classes', $productsList[$destinationCountryCode]))
                throw new Netresearch_Hermes_Block_Adminhtml_Sales_Order_Shipment_Create_Exception(
                    sprintf('product_classes not found for %s in Hermes Product List', $destinationCountryCode));

            $parcelClasses[''] = 'Standard';

            if (array_key_exists('all', $productsList[$destinationCountryCode]['product_classes'])) {
                unset($productsList[$destinationCountryCode]['product_classes']['all']);
                $defaultProductClasses = array_values(Mage::getModel('hermes/config')->getAllProductClasses());
                foreach ($defaultProductClasses as $defaultProductClass) {
                    $productsList[$destinationCountryCode]['product_classes'][$defaultProductClass] = $defaultProductClass;
                }
            }
            foreach (array_keys($productsList[$destinationCountryCode]['product_classes']) as $value) {
                $parcelClasses[$value] = $value;
            }
        }
        return $parcelClasses;
    }

    /**
     * Get the Hermes parcel associated with the current shipment.
     * 
     * @return Netresearch_Hermes_Model_Parcel
     */
    public function getParcel()
    {
        return Mage::getModel('hermes/parcel')->load(
                $this->getMageShipment()->getEntityId(), 'shipment_id'
        );
    }

    /**
     *
     * @return boolean 
     */
    public function isPdfEnabled()
    {
        return Mage::getModel('hermes/config')
                ->isPdfEnabled();
    }

    /**
     *
     * @return boolean
     */
    public function isJpegEnabled()
    {
        return Mage::getModel('hermes/config')
                ->isJpegEnabled();
    }

}
