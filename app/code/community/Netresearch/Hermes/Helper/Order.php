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
 * Order Helper for Hermes module
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Hermes_Helper_Order extends Mage_Core_Helper_Data
{

    protected $parcelValidator = null;


    /**
     * create shipments and parcels for orders in the given collection
     *
     * @param Mage_Sales_Model_Mysql4_Order_Collection $orders
     * @param string                                   $parcelClass
     * @param boolean                                  $notifyCustomer
     *
     * @return array List of error/success messages
     */
    public function shipOrders($orders, $parcelClass=null, $notifyCustomer = false)
    {
        $result = array(
            'success' => array(),
            'errors'   => array()
        );
        foreach ($orders as $order) {
            if ($this->orderCanBeShipped($order)) {
                $messages = $this->shipOrder($order, $parcelClass, $notifyCustomer);
                // array_merge didn't worked here, do it manually
                if (array_key_exists('success', $messages)) {
                    $result['success'][$order->getIncrementId()] = $messages['success'];
                }
                if (array_key_exists('errors', $messages)) {
                    $result['errors'][$order->getIncrementId()] = $messages['errors'];
                }
            } else {
                $result['errors'][$order->getIncrementId()] = Mage::helper('hermes')->__('This order is not automatically shippable by Hermes.');
            }
        }
        return $result;
    }


    /**
     * create shipment and parcel for a given order
     *
     * @param Mage_Sales_Model_Order $order
     * @param string                 $parcelClass
     * @param boolean                $notifyCustomer
     *
     * @return array List of error/success messages
     */
    protected function shipOrder(Mage_Sales_Model_Order $order, $parcelClass, $notifyCustomer)
    {
        $messages = array();
        $parcelValidator = $this->getOrderValidator();
        $parcelValidator->setOrder($order);
        $success = false;
        $message = null;
        if ($parcelValidator->isValidHermesShipment()) {
            try {
                $shipment = $this->createShipment($order);
                /** @var $shipment Mage_Sales_Model_Order_Shipment */
                $parcel = $this->createParcel($shipment, $parcelClass);

                if ($parcel && $parcel->getId()) {
                    $success = true;
                    $shipment->sendEmail($notifyCustomer);

                } else {
                    $message = Mage::helper('hermes')->__('Could not generate Hermes parcel.') . var_export($parcel->getId(), true);
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
            }
        } else {
            $message = Mage::helper('hermes')->__('This order is not automatically shippable by Hermes.');
        }
        $messages[$success ? 'success' : 'errors'] = $message;
        return $messages;
    }


    /**
     * gets the order validation helper
     *
     * @return Netresearch_Hermes_Helper_Validate_Order
     */
    protected function getOrderValidator()
    {
        if (is_null($this->parcelValidator)) {
            $this->parcelValidator = Mage::helper("hermes/validate_order");
        }
        return $this->parcelValidator;
    }


    /**
     * create parcel for a shipment
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param string                          $parcelClass
     *
     * @return Netresearch_Hermes_Model_Parcel
     */
    public function createParcel($shipment, $parcelClass=null)
    {
        /** @var $parcel Netresearch_Hermes_Model_Parcel */
        $parcel = Mage::getModel('hermes/parcel')
            ->setShipment($shipment)
            ->setParcelClass($parcelClass)
            ->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_QUEUED);
        $parcelValidator = Mage::helper("hermes/validate_order");
        /* @var $parcelValidator Netresearch_Hermes_Helper_Validate_Order */
        if ($parcelValidator->setOrder($shipment->getOrder())->isShippedAsCod()) {
            $parcel->setIncludeCashOnDelivery('1');
            $grandTotalEurocent = 100 * $shipment->getOrder()->getGrandTotal();
            $parcel->setAmountCashOnDeliveryEurocent((int) $grandTotalEurocent);
        }
        return $parcel->save();
    }

     /**
     * Create shipment.
     *
     * @param  Mage_Sales_Model_Order           $order
     * @return Mage_Sales_Model_Order_Shipment  $shipment
     */
    public function createShipment(Mage_Sales_Model_Order $order)
    {
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipment = Mage::getModel('sales/service_order', $order)
            ->prepareShipment($this->getQtys($order));
        $shipment->register();
        $shipment->save();
        $order->save();
        return $shipment;
    }


    /**
     * Get order quantities.
     *
     * @param  Mage_Sales_Model_Order   $order
     * @return array                    $qtys
     */
    public function getQtys(Mage_Sales_Model_Order $order)
    {
        $qtys = array();
        foreach ($order->getAllItems() as $item) {
            $qtys[$item->getId()] = $item->getQtyToShip();
        }
        return $qtys;
    }

    /**
     * Checks if an order can be shipped
     *
     * @param  Mage_Sales_Model_Order   $order
     * @return boolean - true if the order can be shipped, false otherwise
     */
    protected function orderCanBeShipped($order)
    {
        $currentPaymentMethod =$order->getPayment()->getMethod();
        $paymentMethodsForAutoMode = Mage::getModel('hermes/config')->getAutocreatePaymentMethods();
        if (!in_array($currentPaymentMethod, $paymentMethodsForAutoMode)) {
            return false;
        }
        if (0 < $order->getShipmentsCollection()->count()) {
            return false;
        }
        foreach ($order->getItemsCollection() as $item) {
            if ($item->getIsVirtual()) {
                return false;
            }
        }

        return true;
    }



    /**
     * retrieves orders
     *
     * @return Mage_Sales_Model_Mysql4_Order_Collection
     */
    public function getOrderCollection()
    {
        $config = Mage::getModel('hermes/config');
        $automaticOrderStatus = $config->getAutocreateOrderStatuses();
        $installationDate = $config->getInstallationDate();
        $maxDaysInPast = $config->getMaxDaysInPast();
        $maxOrderDaysInPastExpression = new Zend_Db_Expr(
            'DATE_SUB(CURRENT_DATE, INTERVAL ' . $maxDaysInPast . ' DAY)'
        );
        $collection = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToFilter('status', array('in' => $automaticOrderStatus))
            ->addAttributeToFilter('created_at', array('gteq' => $maxOrderDaysInPastExpression))
            ->addAttributeToFilter('created_at', array('gteq' => $installationDate))
            ->setOrder('entity_id', 'ASC');
        return $collection;
    }


    /**
     * checks if the order is shipped partially
     * @return boolean
     */
    public function isPartialShipment($shipment)
    {
        $order = $shipment->getOrder();
        //Check if it is a cod order
        $orderHelper = Mage::helper('hermes/validate_order');
        $orderHelper->setOrder($order);
        if (false === $orderHelper->isShippedAsCod()) {
            return false;
        }
        //Build shipment item array
        $shipment_items = array();
        foreach ($shipment->getItemsCollection()->getItems() as $shipment_item) {

            //Ignore doublette simple/configurable shipment items
            if ((float) $shipment_item->getPrice()==0) {
                continue;
            }
            $shipment_items[$shipment_item->getSku()] = $shipment_item->getQty();
        }

        //Loop through every order item and look if it is existing in the shipment too
        foreach ($order->getAllItems() as $order_item) {
            //Ignore doublette simple/configurable order items
            if ((float) $order_item->getPrice()==0 || '' !=$order_item->getParentItemId() || $order_item->getIsVirtual()) {
                continue;
            }
            if (false === isset($shipment_items[$order_item->getSku()])) {
                return true;
            }
            if ($shipment_items[$order_item->getSku()] != (int) $order_item->getQtyOrdered()) {
                return true;
            }
        }
        return false;
    }
}
