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
 * Hermes Shipment controller
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Netresearch_Hermes_ShipmentController
    extends Mage_Adminhtml_Controller_Action
{

    /**
     * uses for semi-automatic shipping
     * creates shipments and according hermes parcels for one or more
     * orders (= massActio)
     */
    public function createShipmentsAction()
    {
        $orderIds           = $this->getRequest()->getParam('order_ids');
        $notifyCustomer     = $this->getRequest()->getParam('notifyCustomer');
        $parcelClass        = Mage::getModel('hermes/config')->isTieredPriceMerchant() ? $this->getRequest()->getParam('parcelClass') : '';

        $parcelSuccessCount = 0;
        $parcelErrorCount   = 0;

        if (!is_array($orderIds) || sizeof($orderIds) <= 0) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('hermes')->__('No order was selected!'));
        } else {
            $orders = Mage::getModel('sales/order')
                ->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $orderIds))
                ->load();
            if (0 < $orders->count()) {
                $orderHelper     = Mage::helper('hermes/order');
                $createdShipments   = $orderHelper->shipOrders($orders, $parcelClass, $notifyCustomer);
                $parcelErrorCount   = sizeof($createdShipments['errors']);
                $parcelSuccessCount = sizeof($createdShipments['success']);
                foreach ($createdShipments['errors'] as $orderNumber=>$error) {
                    Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('hermes')->__('Order %s threw error: %s', $orderNumber, $error)
                    );
                }
            } else {
               $parcelErrorCount = sizeof($orderIds);
            }

        }
        if (0 < $parcelSuccessCount) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('hermes')->__('%d Hermes shipment(s) created', $parcelSuccessCount)
            );
            Mage::getSingleton('adminhtml/session')->addWarning(
            Mage::helper('hermes')->__(
                'Shipment(s) will be transmitted to Hermes within a short time. If you are in a hurry, you could <a href="%s">trigger prompt transmission</a>.',
                Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/parcel/transmitHermesParcels')
            ));
        }
        if (0 < $parcelErrorCount) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('hermes')->__('%d Hermes shipment(s) could not be created', $parcelErrorCount)
            );
        }
        $this->_redirectReferer();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/shipment/create_hermes_shipments');
    }
}
