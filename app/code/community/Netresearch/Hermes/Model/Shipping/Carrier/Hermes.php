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
 * @category  Netresearch
 * @package   Netresearch_Hermes
 * @author    Thomas Birke <thomas.birke@netresearch.de>
 * @copyright 2012 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.netresearch.de/magento/
 */


/**
 * Netresearch_Hermes_Model_Shipping_Carrier_Hermes
 *
 * @category  Netresearch
 * @package   Netresearch_Hermes
 * @author    Thomas Birke <thomas.birke@netresearch.de>
 * @copyright 2012 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.netresearch.de/magento/
 */
class Netresearch_Hermes_Model_Shipping_Carrier_Hermes extends Mage_Shipping_Model_Carrier_Abstract
{
    const CODE = 'hermes';

    protected $_code = self::CODE;

    public function getAllowedMethods()
    {
        return array($this->_code => 'Hermes');
    }

    public function isActive()
    {
        return true;
    }

    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * we won't use this carrier for shipping rates collection,
     * so we just return an empty result
     * 
     * @see Mage_Shipping_Model_Carrier_Abstract::collectRates()
     *
     * @param Mage_Shipping_Model_Rate_Request $request 
     * @return void
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        return Mage::getModel('shipping/rate_result');
    }

    /**
     * get tracking information
     *
     * @see Mage_Usa_Model_Shipping_Carrier_Abstract::getTrackingInfo()
     * @return Mage_Shipping_Model_Tracking_Result|false
     */
    public function getTrackingInfo($tracking)
    {
        $result = $this->getTracking($tracking);

        if ($result instanceof Mage_Shipping_Model_Tracking_Result) {
            if ($trackings = $result->getAllTrackings()) {
                return $trackings[0];
            }
        } elseif (is_string($result) && 0 < strlen($result)) {
            return $result;
        }

        return false;
    }

    /**
     * @see Mage_Usa_Model_Shipping_Carrier_Dhl::getTracking()
     * @return Mage_Shipping_Model_Tracking_Result
     */
    public function getTracking($trackings)
    {
        if (!is_array($trackings)) {
            $trackings = array($trackings);
        }

        $result = Mage::getModel('shipping/tracking_result');
        foreach ($trackings as $trackingNumber) {
            $status = Mage::getModel('shipping/tracking_result_status');
            $status->setCarrierTitle('Hermes');
            $status->setCarrier('hermes');
            $status->setTracking($trackingNumber);
            $status->setPopup(true);
            $status->setUrl(Mage::getModel('hermes/config')->getTrackingUrl($trackingNumber));
            $result->append($status);
        }

        return $result;
    }
}

