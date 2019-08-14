<?php

/**
 * Netresearch_Hermes
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
 * Hermes System Config Shipment LabelPlacements Options Source Test
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Netresearch_Hermes_Test_Model_System_Config_Source_Shipping_LabelPlacementsTest 
    extends EcomDev_PHPUnit_Test_Case_Config
{
    
    /**
    * tests if the the source class (Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelFileFormatOptions) 
    * is present
    * @return Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelPlacements()
    */
   public function testSourceClassExists()
   {
        $this->assertTrue(class_exists('Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelPlacements'));
        return new Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelPlacements();
   }
    
   /**
    * @depends testSourceClassExists
    * @loadExpectation
    */
    public function testToOptionArray(Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelPlacements $sourceLabelPlacementOptions)
    {
        $this->assertTrue(method_exists($sourceLabelPlacementOptions, "toOptionArray"));
        $this->assertTrue(is_array($sourceLabelPlacementOptions->toOptionArray()));
        $options = $sourceLabelPlacementOptions->toOptionArray();
        $this->assertNotEmpty($options);
        $this->assertEquals($this->expected()->getCount(), count($options), "label options are smaller than expected");
        $labelPlacementOptions = $this->expected()->getLabelValues();
        foreach ($options as $option) {
            $this->assertTrue(in_array($option['value'], 
                array_keys($labelPlacementOptions)), 
                "Shipment label options are smaller than expected");
        }
    }
    
}
