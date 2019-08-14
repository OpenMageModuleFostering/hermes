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
 * Hermes System Config Shipment Methods Source Test
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      André Herrn <andre.herrn@netresearch.de>
 */
class Netresearch_Hermes_Test_Model_System_Config_Source_Shipping_Methods extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testSourceClassExists()
    {
        $this->assertTrue(class_exists('Netresearch_Hermes_Model_System_Config_Source_Shipping_Methods'));
        return new Netresearch_Hermes_Model_System_Config_Source_Shipping_Methods();
    }
    
    /**
     * @depends testSourceClassExists
     */
    public function testToOptionArray(Netresearch_Hermes_Model_System_Config_Source_Shipping_Methods $sourceShipmentOptions)
    {
        $this->assertTrue(method_exists($sourceShipmentOptions, "toOptionArray"));
        $this->assertTrue(is_array($sourceShipmentOptions->toOptionArray()));
        
        $options = $sourceShipmentOptions->toOptionArray();
        $this->assertNotEmpty($options);
        $this->assertTrue(count($options) >= count($this->_getCarriers()), "Shipping methods were smaller then Magento Shipping Carriers");
    }
    
    protected function _getCarriers()
    {
        $carriers = new Mage_Shipping_Model_Config();
        return $carriers->getAllCarriers();
    }
}