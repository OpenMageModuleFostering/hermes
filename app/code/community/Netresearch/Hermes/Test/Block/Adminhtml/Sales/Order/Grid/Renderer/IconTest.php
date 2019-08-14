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
 * Test for Netresearch_Hermes_Block_Adminhtml_Sales_Order_Grid_Renderer_Icon
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      André Herrn <andre.herrn@netresearch.de>
 */
class Netresearch_Hermes_Test_Block_Adminhtml_Sales_Order_Grid_Renderer_IconTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Netresearch_Hermes_Block_Adminhtml_Sales_Order_Shipment_Create_Hermes
     */
    protected $block;

    public function setUp()
    {
        $this->block = Mage::getSingleton('core/layout')
            ->createBlock('hermes/adminhtml_sales_order_grid_renderer_icon');
        parent::setUp();
    }

    /**
     * @test
     * @loadFixture ../../../../../../../../var/fixtures/parcels
     */
    public function testRender()
    {
        $this->assertInstanceOf('Netresearch_Hermes_Block_Adminhtml_Sales_Order_Grid_Renderer_Icon', $this->block);
        $row = new Varien_Object();
        $row->setShipmentsCollection(array());
        $this->assertEmpty($this->block->getHermesStatusOutput($row));

        $row->setShipmentsCollection(array(
            new Varien_Object(array('id' => 1)), // no parcel
            new Varien_Object(array('id' => 2)), // empty (STATUS_QUEUED)
            new Varien_Object(array('id' => 3)), // STATUS_QUEUED
            new Varien_Object(array('id' => 4)), // STATUS_PROCESSED
        ));

        $pattern   = ' <div class="hermes_status"><img src="%s" alt="Hermes" title="%s" /></div>';
        $imagePath = $this->block->getSkinUrl('images/hermes/logo_small.png');
        $message   = Mage::helper('hermes')->__('%d parcels were transmitted to Hermes', 1);

        $this->assertEquals(
            sprintf($pattern, $imagePath, $message),
            $this->block->getHermesStatusOutput($row)
        );

        $row->setShipmentsCollection(array(
            new Varien_Object(array('id' => 1)), // no parcel
            new Varien_Object(array('id' => 2)), // empty (STATUS_QUEUED)
            new Varien_Object(array('id' => 3)), // STATUS_QUEUED
            new Varien_Object(array('id' => 4)), // STATUS_PROCESSED
            new Varien_Object(array('id' => 7)), // STATUS_NEW_FAILED
            new Varien_Object(array('id' => 16)), // STATUS_CANCELED
        ));

        $message = Mage::helper('hermes')->__('%d parcels could not be transmitted to Hermes', 1);
        $imagePath = $this->block->getSkinUrl('images/hermes/logo_small_failed.png');
        $this->assertEquals(
            sprintf($pattern, $imagePath, $message),
            $this->block->getHermesStatusOutput($row)
        );
    }
}
