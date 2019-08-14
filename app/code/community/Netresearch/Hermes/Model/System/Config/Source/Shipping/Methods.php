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
 * Hermes System Config Shipment Methods Source
 *
 * @deprecated
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      André Herrn <andre.herrn@netresearch.de>
 */
class Netresearch_Hermes_Model_System_Config_Source_Shipping_Methods
    extends Mage_Adminhtml_Model_System_Config_Source_Shipping_Allmethods
{
    /**
     * Get shipping methods.
     *
     * @return array $methods
     */
    public function toOptionArray($isActiveOnlyFlag = false)
    {
        $methods = array(array('value' => '', 'label' => ''));

        $carriers = new Mage_Shipping_Model_Config();
        $carriers = $carriers->getAllCarriers();

        foreach ($carriers as $carrier)
        {
            try {
                $className = Mage::getStoreConfig('carriers/'.$carrier->getId().'/model');
                if ($className)
                {
                    $obj = Mage::getModel($className);
                    foreach ($obj->getAllowedMethods() as $key=>$method)
                    {
                        $code = $carrier->getId()."_".$key;
                        $title = $method." (".$carrier->getId().")";
                        $methods[$code] = array(
                            'label' => $title,
                            'value' => $code);
                    }
                }
            }
            catch (Exception $e) {
                //Some carries seem to be fake carrier and have no valid methods (f.e. M2E)
            }
        }
        
        return $methods;
    }
}