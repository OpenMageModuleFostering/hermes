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
 * Netresearch_Hermes_Block_Adminhtml_Sales_Order_Grid_Renderer_Icon
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Hermes_Block_Adminhtml_Sales_Order_Grid_Renderer_Icon
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function getHermesStatusOutput($row)
    {
        $status = array(
            'queued' => 0,
            'failed' => 0,
            'canceled' => 0,
            'sent' => 0
        );
        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        foreach ($row->getShipmentsCollection() as $shipment) {
            /* @var $parcel Netresearch_Hermes_Model_Shipment */
            $parcel = Mage::getModel('hermes/parcel')->load($shipment->getId(), 'shipment_id');
            if ($parcel && $parcel->getId()) {
                if ($parcel->isFailed() || $parcel->isCancelFailed()) {
                    $status['failed']++;
                } elseif ($parcel->isProcessed() || $parcel->isClosed()) {
                    $status['sent']++;
                } elseif ($parcel->isCanceled() || $parcel->isQueuedToCancel()) {
                    $status['canceled']++;
                } else {
                    $status['queued']++;
                }
            }
        }
        if (0 < $status['failed']) {
            $longMessage = Mage::helper('hermes')->__('%d parcels could not be transmitted to Hermes', $status['failed']);
            $image = 'logo_small_failed.png';
        } elseif (0 < $status['sent']) {
            $longMessage = Mage::helper('hermes')->__('%d parcels were transmitted to Hermes', $status['sent']);
            $image = 'logo_small.png';
        } elseif (0 < $status['queued']) {
            $longMessage = Mage::helper('hermes')->__('%d parcels are queued for transmission to Hermes', $status['queued']);
            $image = 'logo_small_grey.png';
        } else {
            return '';
        }
        return ' <div class="hermes_status"><img src="' . $this->getSkinUrl('images/hermes/'.$image) . '" alt="Hermes" title="' . $longMessage . '" />'
        . '</div>';
    }

    public function render(Varien_Object $row)
    {
        return parent::render($row) . $this->getHermesStatusOutput($row);
    }
}

