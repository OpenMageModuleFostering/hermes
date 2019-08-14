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
 * Order Validate Helper for Hermes module
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      André Herrn <andre.herrn@netresearch.de>
 */
class Netresearch_Hermes_Helper_Validate_Order extends Mage_Core_Helper_Data
{
    /**
     *
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    /**
     *
     * @var array
     */
    protected $validation_errors = array();

    /**
     * set order
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return Netresearch_Hermes_Helper_Validate_Order
     */
    public function setOrder($order)
    {
        $this->validation_errors = array();
        $this->order = $order;
        return $this;
    }

    /**
     * get order
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * check if order is valid to be shipped with hermes
     *
     * @return boolean
     */
    public function isValidHermesShipment()
    {
        $check = true;

        //Check if the order is a COD-Order and Amount is greater than 2.500€
        if ($this->isShippedAsCod() && $this->getOrder()->getGrandTotal() > 2500) {
            $check = false;
            $this->validation_errors[] = $this->__('Hermes COD shipments are not allowed for grand totals greater than 2.500€.');
        }

        //Check if the order is a COD-Order and target country is an allowed COD country
        if ($this->isShippedAsCod() && false === $this->isAllowedCodCountry()) {
            $check = false;
            $this->validation_errors[] = $this->__('Hermes COD shipments are not allowed to %s.',
                $this->getOrder()->getShippingAddress()->getCountryModel()->getName()
            );
        }

        //check if country is in available countries list
        if (false === $this->isAllowedCountry()) {
            $check = false;
            $this->validation_errors[] = $this->__('"%s" is not supported by Hermes.',
                $this->getOrder()->getShippingAddress()->getCountryModel()->getName()
            );
        }

        return $check;
    }

    /**
     * check if order is shipped by COD
     *
     * @return boolean
     */
    public function isShippedAsCod()
    {
        return Mage::getModel("hermes/config")
            ->isPaymentMethodForCod($this->getOrder()->getPayment()->getMethod(), $this->getOrder()->getStoreId());
    }

    /**
     * check if order is shipped to an allowed country
     *
     * @return boolean
     */
    public function isAllowedCountry()
    {
        $receiverIso3CountryCode = Mage::helper('hermes')->getIso3CodeByIso2Code(
            $this->getOrder()->getShippingAddress()->getCountryId()
        );

        if (false === Mage::getModel("hermes/config")->isAllowedCountry($receiverIso3CountryCode))
            return false;
    }

    /**
     * check if order is shipped to an allowed cod country
     *
     * @return boolean
     */
    public function isAllowedCodCountry()
    {
        $receiverIso3CountryCode = Mage::helper('hermes')->getIso3CodeByIso2Code(
            $this->getOrder()->getShippingAddress()->getCountryId()
        );

        if (false === Mage::getModel("hermes/config")->isAllowedCodCountry($receiverIso3CountryCode))
            return false;
    }

    /**
     * get validation errors
     */
    public function getValidationErrors()
    {
        return $this->validation_errors;
    }

    /**
     * reset validation errors
     */
    public function resetValidationErrors()
    {
        $this->validation_errors = array();
        return $this;
    }

    /**
     * check if order was created after Hermes installation
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function isCreatedAfterHermesInstallation()
    {
        $installDate = Mage::getModel("hermes/config")->getInstallationDate();
        $orderDate   = strtotime($this->getOrder()->getCreatedAtDate());
        return ($installDate <= $orderDate);
    }
}
