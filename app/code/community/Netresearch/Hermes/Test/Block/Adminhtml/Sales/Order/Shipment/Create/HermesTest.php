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
 * Hermes Sales Order Shipment Create Hermes Block unittest
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      André Herrn <andre.herrn@netresearch.de>
 */
class Netresearch_Hermes_Test_Block_Adminhtml_Sales_Order_Shipment_Create_HermesTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Netresearch_Hermes_Helper_Validate_Order
     */
    protected $helper;
    
    /**
     * @var Netresearch_Hermes_Model_Config
     */
    protected $config;
    
    /**
     * @var Netresearch_Hermes_Block_Adminhtml_Sales_Order_Shipment_Create_Hermes
     */
    protected $block;
    
    /**
     * @var Mage_Core_Model_Store
     */
    protected $store;

    public function setUp()
    {
        $this->helper = Mage::helper('hermes/validate_order');
        $this->config = Mage::getModel('hermes/config');
        $this->store = Mage::app()->getStore(0)->load(0);
        $this->block = Mage::getSingleton('core/layout')
            ->createBlock('hermes/adminhtml_sales_order_shipment_create_hermes');
        $this->block->getValidateOrderHelper()->resetValidationErrors();
        parent::setUp();
    }

    public function testBlockExists()
    {
        $this->assertTrue('Netresearch_Hermes_Block_Adminhtml_Sales_Order_Shipment_Create_Hermes'
            == get_class($this->block));
    }

    /**
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     */
    public function testIsAllowedShippingMethod()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        Mage::register("current_shipment", $shipment);
        $this->store->setConfig('hermes/shipment_options/disabled_shipping_methods', '');
        $this->assertTrue($this->block->isAllowedShippingMethod());

        $this->store->setConfig('hermes/shipment_options/disabled_shipping_methods', 'flatrate_flatrate');
        $this->assertFalse($this->block->isAllowedShippingMethod());

        $this->store->setConfig('hermes/shipment_options/disabled_shipping_methods', '');
    }

    /**
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     */
    public function testIsValidHermesShipmentValid()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        Mage::unregister("current_shipment");
        Mage::register("current_shipment", $shipment);
        $this->assertTrue($this->block->isValidHermesShipment());
        $this->assertEmpty($this->block->getValidationErrors());
    }

    /**
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     */
    public function testIsValidHermesShipmentValidCod()
    {
        //set checkmo to cod and amount < 2500
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        Mage::unregister("current_shipment");
        Mage::register("current_shipment", $shipment);
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', 'checkmo');
        $this->assertTrue($this->block->isValidHermesShipment());
        $this->assertEmpty($this->block->getValidationErrors());
    }

    /**
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     */
    public function testIsValidHermesShipmentInvalidCod()
    {
        //set checkmo to cod and an amount > 2500
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', 'checkmo');
        $shipment = Mage::getModel('sales/order_shipment')->load(4);
        Mage::unregister("current_shipment");
        Mage::register("current_shipment", $shipment);
        $this->assertFalse($this->block->isValidHermesShipment());
        $this->assertNotEmpty($this->block->getValidationErrors());
    }

    /**
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     */
    public function testIsValidHermesShipmentInvalidUsa()
    {
        //check usa customer
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', '');
        $shipment = Mage::getModel('sales/order_shipment')->load(3);
        Mage::unregister("current_shipment");
        Mage::register("current_shipment", $shipment);
        $this->assertFalse($this->block->isValidHermesShipment());
        $this->assertNotEmpty($this->block->getValidationErrors());

        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', '');
    }

    /**
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     */
    public function testIsValidHermesShipmentValidUk()
    {
        //check usa customer
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', '');
        $shipment = Mage::getModel('sales/order_shipment')->load(5);
        Mage::unregister("current_shipment");
        Mage::register("current_shipment", $shipment);
        $valid = $this->block->isValidHermesShipment();
        $this->assertEquals(array(), $this->block->getValidationErrors());
        $this->assertTrue($valid);
    }

    /**
     * checks if the merchand is tiered price merchand
     */
    public function testIsTieredPriceMerchant()
    {
        $this->assertTrue(is_bool($this->block->isTieredPriceMerchand()));
    }

    /**
     * special case: germany -> the product classes contains 'all' as value
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     * @loadExpectation productClasses
     */
    public function testGetParcelClassesForCountryDeu()
    {
        $this->loadAndRegisterShipment(2);
        $mock = $this->getMock('Netresearch_Hermes_Model_Config');
        $mock->expects($this->any())
            ->method('getListOfProductsProducts')
            ->will($this->returnValue(array(
                                        'DEU' => array(
                                            'product_classes' => array(
                                                'all' => array('all')
                                            )
                                        )
                                    )));
        $mock->expects($this->any())
            ->method('getAllProductClasses')
            ->will($this->returnValue(array(
                                    Netresearch_Hermes_Model_Config::PRODUCT_CLASS_EXTRA_SMALL,
                                    Netresearch_Hermes_Model_Config::PRODUCT_CLASS_SMALL,
                                    Netresearch_Hermes_Model_Config::PRODUCT_CLASS_MEDIUM,
                                    Netresearch_Hermes_Model_Config::PRODUCT_CLASS_LARGE,
                                    Netresearch_Hermes_Model_Config::PRODUCT_CLASS_EXTRA_LARGE
                                )));
        
        $this->replaceByMock('model', 'hermes/config', $mock);
        $parcelClasses = $this->block->getParcelClassesForCountry();
        $this->assertTrue(is_array($parcelClasses));
        $this->assertTrue(sizeof($parcelClasses) > 0);
        $expectedParcelClasses = $this->expected('DEU')->getProductClasses();
        $this->assertEquals(sizeof($expectedParcelClasses), sizeof($parcelClasses));
        // special case: all in case of germany 
        foreach (array_keys($parcelClasses) as $parcelClass) {
            $this->assertTrue(in_array($parcelClass, $expectedParcelClasses));
        }
    }

    /**
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     * @loadExpectation productClasses
     * 
     */
    public function testGetParcelClassesForCountryGBR()
    {
        $this->loadAndRegisterShipment(5);
        $mock = $this->getMock('Netresearch_Hermes_Model_Config');
        $mock->expects($this->any())
            ->method('getListOfProductsProducts')
            ->will($this->returnValue(array(
                                        'GBR' => array(
                                            'product_classes' => array(
                                                'XS' => array('XS'), 
                                                'S' => array('S'), 
                                                'M' => array('M'), 
                                                'L' => array('L')
                                            )
                                        )
                                    )));
        
        $this->replaceByMock('model', 'hermes/config', $mock);
        $parcelClasses = $this->block->getParcelClassesForCountry();
        $this->assertTrue(is_array($parcelClasses));
        $expectedParcelClasses = $this->expected('GBR')->getProductClasses();
        $this->assertEquals(sizeof($expectedParcelClasses), sizeof($parcelClasses));
        foreach (array_keys($parcelClasses) as $parcelClass) {
            $this->assertTrue(in_array($parcelClass, $expectedParcelClasses));
        }
    }

    /**
     * special case: country is not supported by hermes -> possible products
     * should be empty
     * 
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     */
    public function testGetParcelClassesForCountryUSA()
    {
        $this->loadAndRegisterShipment(3);
        $mock = $this->getMock('Netresearch_Hermes_Model_Config');
        $mock->expects($this->any())
            ->method('isAllowedCountry')
            ->will($this->returnValue(false));
        $this->replaceByMock('model', 'hermes/config', $mock);
        $parcelClasses = $this->block->getParcelClassesForCountry();
        $this->assertTrue(is_array($parcelClasses));
        $this->assertTrue(sizeof($parcelClasses) === 0);
        $this->assertFalse(array_key_exists('', $parcelClasses));
    }

    /**
     * special case: if the cod is greater than > 2500€, the shipment is not
     * a valid hermes shipment -> product classes should be empty in this case 
     * 
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     */
    public function testGetParcelClassesForCountryIsEmptyDueToInvalidCod()
    {
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', 'checkmo');
        $mock = $this->getMock('Netresearch_Hermes_Model_Config');
        $mock->expects($this->any())
            ->method('getListOfProductsProducts')
            ->will($this->returnValue(array(
                                        'DEU' => array(
                                            'product_classes' => array(
                                                'all' => array('all')
                                            )
                                        )
                                     )));
        $this->replaceByMock('model', 'hermes/config', $mock);
        $mock->expects($this->any())
            ->method('isPaymentMethodForCod')
            ->will($this->returnValue(true));
        $this->loadAndRegisterShipment(4);
        $parcelClasses = $this->block->getParcelClassesForCountry();
        $this->assertTrue(is_array($parcelClasses));
        $this->assertTrue(sizeof($parcelClasses) === 0);
        $this->assertFalse(array_key_exists('', $parcelClasses));
    }

    /**
     * special case: country is not supported by hermes -> possible products
     * should be empty
     * 
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     */
    public function testGetParcelClassesThrowsExceptionIfDestinationNotFound()
    {
        $this->loadAndRegisterShipment(3);
        $mock = $this->getMock('Netresearch_Hermes_Model_Config');
        $mock->expects($this->any())
            ->method('getListOfProductsProducts')
            ->will($this->returnValue(array()));
        $this->replaceByMock('model', 'hermes/config', $mock);
        $this->setExpectedException('Netresearch_Hermes_Block_Adminhtml_Sales_Order_Shipment_Create_Exception');
        $parcelClasses = $this->block->getParcelClassesForCountry();
        
    }
    
    /**
     * special case: country is not supported by hermes -> possible products
     * should be empty
     * 
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     */
    public function testGetParcelClassesThrowsExceptionIfProductClassesNotFound()
    {
        $this->loadAndRegisterShipment(2);
        $mock = $this->getMock('Netresearch_Hermes_Model_Config');
        $mock->expects($this->any())
            ->method('getListOfProductsProducts')
            ->will($this->returnValue(array('DEU' => array())));
        $this->replaceByMock('model', 'hermes/config', $mock);
        $this->setExpectedException('Netresearch_Hermes_Block_Adminhtml_Sales_Order_Shipment_Create_Exception');
        $parcelClasses = $this->block->getParcelClassesForCountry();
        
    }

    /**
     * @loadFixture ../../../../../../../../var/fixtures/orderList1
     * @loadFixture ../../../../../../../../var/fixtures/parcels
     */
    public function testGetParcel()
    {
        $shipment_id = 2;
        $this->loadAndRegisterShipment($shipment_id);
        $parcel = $this->block->getParcel();
        
        $this->assertInstanceOf(
            'Netresearch_Hermes_Model_Parcel',
            $parcel
        );
        
        $this->assertEquals(
            $shipment_id,
            $parcel->getData('shipment_id')
        );
    }
    
    public function testIsPdfEnabled()
    {
        $this->assertEquals(Mage::getModel('hermes/config')
                ->isPdfEnabled(), $this->block->isPdfEnabled());
    }

    /**
     *
     * @return boolean
     */
    public function testIsJpegEnabled()
    {
        $this->assertEquals(Mage::getModel('hermes/config')
                ->isJpegEnabled(), $this->block->isJpegEnabled());
    }
    
    
    
    /**
     * load and register a shipment identified by it's id
     * @param integer $shipmentId 
     */
    protected function loadAndRegisterShipment($shipmentId)
    {
        $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
        Mage::unregister("current_shipment");
        Mage::register("current_shipment", $shipment);
    }
}
