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
 * Hermes Shipment Controller Test
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Netresearch_Hermes_Test_Controller_ShipmentControllerTest
    extends EcomDev_PHPUnit_Test_Case_Controller
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockAdminUserSession();
    }

    /**
     * @test
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function createShipmentActionFailsDueToWrongParameters()
    {
        // mock parcel so it will not be saved
        $mockOrderHelper = $this->getMock(
            'Netresearch_Hermes_Helper_Order',
            array('shipOrders')
        );
        $mockOrderHelper->expects($this->any())
            ->method('shipOrders')
            ->will($this->returnValue(array('success' => array(),
                                            'errors'  => array('foo')
                )));

        $this->replaceByMock('helper', 'hermes/order', $mockOrderHelper);
        Mage::getSingleton('adminhtml/session')->getMessages(true);

        // test for errors if no order_ids passed to massAction
        $this->dispatch('adminhtml/shipment/createShipments');
        $errorMessages = Mage::getSingleton('adminhtml/session')->getMessages(true)->getItemsByType('error');
        $this->assertGreaterThan(0, count($errorMessages));
        $this->assertEquals('error: ' . Mage::helper('hermes')->__('No order was selected!'), current($errorMessages)->toString());
        $this->getRequest()->setPost('order_ids', array());
        $this->dispatch('adminhtml/shipment/createShipments');
        $errorMessages = Mage::getSingleton('adminhtml/session')->getMessages()->getItemsByType('error');
        $successMessages = Mage::getSingleton('adminhtml/session')->getMessages(true)->getItemsByType('success');
        $this->assertGreaterThan(0, count($errorMessages));
        $this->assertEquals(0, count($successMessages));
        $this->assertEquals('error: ' . Mage::helper('hermes')->__('No order was selected!'), current($errorMessages)->toString());

        // test for errors if invalid order_ids were passed to mass action
        $invalidOrderIds = array(1,2,3,4);
        $this->getRequest()->setPost('order_ids', $invalidOrderIds);
        $this->dispatch('adminhtml/shipment/createShipments');
        $this->replaceByMock('helper', 'hermes/order', $mockOrderHelper);
        $errorMessages = Mage::getSingleton('adminhtml/session')->getMessages()->getItemsByType('error');
        $successMessages = Mage::getSingleton('adminhtml/session')->getMessages(true)->getItemsByType('success');
        $this->assertEquals(0, count($successMessages));
        $this->assertEquals('error: ' . Mage::helper('hermes')->__('%d Hermes shipment(s) could not be created', sizeof($invalidOrderIds)),
            current($errorMessages)->toString());

        $this->dispatch('adminhtml/shipment/createShipments');
        $messages = Mage::getSingleton('adminhtml/session')->getMessages(true)->getItemsByType('error');
        $this->assertGreaterThan(0, count($messages));
    }


    /**
     *
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testCreateShipmentActionSucceeded()
    {

        // mock parcel so it will not be saved
        $mockOrderHelper = $this->getMock(
            'Netresearch_Hermes_Helper_Order',
            array('shipOrders')
        );

        // test for success messages if valid order_id (and order) is passed to mass action
        $mockOrderHelper->expects($this->any())
            ->method('shipOrders')
            ->will($this->returnValue(array(
                'success' => array('foo'),
                'errors' => array()
            )));

        $this->replaceByMock('helper', 'hermes/order', $mockOrderHelper);

        $this->getRequest()->setPost('order_ids', array(36));
        $this->dispatch('adminhtml/shipment/createShipments');
        $successMessages = Mage::getSingleton('adminhtml/session')->getMessages()->getItemsByType('success');
        $errorMessages = Mage::getSingleton('adminhtml/session')->getMessages()->getItemsByType('error');
        $this->assertEquals(0, count($errorMessages));
        $this->assertGreaterThan(0, count($successMessages));
        $this->assertEquals('success: ' . Mage::helper('hermes')->__('%d Hermes shipment(s) created', 1),
            current($successMessages)->toString());

        $warningMessages = Mage::getSingleton('adminhtml/session')->getMessages(true)->getItemsByType('warning');
        $this->assertEquals(
                'warning: ' . Mage::helper('hermes')->__(
                'Shipment(s) will be transmitted to Hermes within a short time. If you are in a hurry, you could <a href="%s">trigger prompt transmission</a>.',
                Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/parcel/transmitHermesParcels')
            ), current($warningMessages)->toString()
            );

        $this->assertRedirect();
    }


    /**
     *
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testCreateShipmentActionFailedDueToInvalidParcel()
    {
        $this->markTestIncomplete('Incomplete - acl mock missing');
        Mage::getSingleton('adminhtml/session')->getMessages(true);

        $mockOrderHelper = $this->getMock(
            'Netresearch_Hermes_Helper_Order',
            array('shipOrders')
        );

        $mockOrderHelper->expects($this->any())
            ->method('shipOrders')
            ->will($this->returnValue(array('success' => array(),
                                            'errors'  => array('foo')
                )));

        $this->replaceByMock('helper', 'hermes/order', $mockOrderHelper);

        $this->getRequest()->setPost('order_ids', array(37));
        Mage::app()->getStore(0)->load(0)->setConfig('hermes/shipment_options/cod_payment_methods', 'cod');

        $this->dispatch('adminhtml/shipment/createShipments');

        $errorMessages = Mage::getSingleton('adminhtml/session')->getMessages()->getItemsByType('error');
        $successMessages = Mage::getSingleton('adminhtml/session')->getMessages()->getItemsByType('success');
        $this->assertEquals(2, count($errorMessages));
        $this->assertEquals(0, count($successMessages));
        reset($errorMessages);
        $this->assertEquals('error: ' . Mage::helper('hermes')->__('Order 0 threw error:', 1) . ' foo',
            current($errorMessages)->toString());
        $this->assertEquals('error: ' . Mage::helper('hermes')->__('%d Hermes shipment(s) could not be created', 1),
            next($errorMessages)->toString());

    }

    /**
     *
     * tests with one valid order and one invalid order
     *
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testMassActionPartiallySucceeded()
    {
        $this->markTestIncomplete('Incomplete - acl mock missing');
        $mockOrderHelper = $this->getMock(
            'Netresearch_Hermes_Helper_Order',
            array('shipOrders')
        );

        $mockOrderHelper->expects($this->any())
            ->method('shipOrders')
            ->will($this->returnValue(array('success' => array('foo'),
                                            'errors'  => array('foo')
                )));

        $this->replaceByMock('helper', 'hermes/order', $mockOrderHelper);

        $this->getRequest()->setPost('order_ids', array(36, 37));
        Mage::app()->getStore(0)->load(0)->setConfig('hermes/shipment_options/cod_payment_methods', 'cod');

        $this->dispatch('adminhtml/shipment/createShipments');
        $messages = Mage::getSingleton('adminhtml/session')->getMessages(true)->getItemsByType('success');

        $this->assertGreaterThan(0, count($messages));

        $this->getRequest()->setPost('order_ids', array(37));
        Mage::app()->getStore(0)->load(0)->setConfig('hermes/shipment_options/cod_payment_methods', 'cod');
        $this->dispatch('adminhtml/shipment/createShipments');

        $errorMessages = Mage::getSingleton('adminhtml/session')->getMessages()->getItemsByType('error');
        $this->assertEquals(2, count($errorMessages));
        reset($errorMessages);
        $this->assertEquals('error: ' . Mage::helper('hermes')->__('Order 0 threw error:', 1) . ' foo',
            current($errorMessages)->toString());
        $this->assertEquals('error: ' . Mage::helper('hermes')->__('%d Hermes shipment(s) could not be created', 1),
            next($errorMessages)->toString());

        $successMessages = Mage::getSingleton('adminhtml/session')->getMessages()->getItemsByType('success');
        $this->assertEquals(1, count($successMessages));
        $this->assertEquals('success: ' . Mage::helper('hermes')->__('%d Hermes shipment(s) created', 1),
            current($successMessages)->toString());
    }

}
