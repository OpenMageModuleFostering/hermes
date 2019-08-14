<?php
class Netresearch_Hermes_Test_Helper_OrderTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function shipOrders()
    {
        $parcel = Mage::getModel('hermes/parcel');
        $parcel->setId(777);

        $shipment = Mage::getModel('sales/order_shipment');
        $shipment->setId(7777);

        $orders   = Mage::getModel('sales/order')->getCollection();


        $helperMock = $this->getHelperMock('hermes/order', array('createShipment', 'createParcel', 'orderCanBeShipped'));
        $helperMock->expects($this->atLeastOnce())
            ->method('createShipment')
            ->with($this->isInstanceOf('Mage_Sales_Model_Order'))
            ->will($this->returnValue($shipment));
        $helperMock->expects($this->atLeastOnce())
            ->method('createParcel')
            ->will($this->returnValue($parcel));
        $helperMock->expects($this->any())
            ->method('orderCanBeShipped')
            ->will($this->returnValue(true));

        $this->replaceByMock('helper', 'hermes/order', $helperMock);

        $shipmentMock = $this->getModelMock('sales/order_shipment', array('sendEmail'));
        $shipmentMock->expects($this->any())
            ->method('sendEmail')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'sales/order_shipment', $shipmentMock);

        $helper = Mage::helper('hermes/order');
        $result = $helper->shipOrders($orders);
        $this->assertEquals(array(
            'success' => array(
                100000055 => null,
                100000057 => null,
                100000058 => null,
                100000059 => null,
                100000060 => null,
                100000061 => null,
                100000062 => null,
                100000063 => null
            ),
            'errors' => array(100000056 => Mage::helper('hermes')->__('This order is not automatically shippable by Hermes.'))
        ), $result);
    }


    /**
     * @test
     */
    public function createParcel()
    {
        $this->markTestIncomplete("Not a single assertion here.");

        /** @var Netresearch_Hermes_Helper_Validate_Order */
        $validationHelper = $this->getHelperMock('hermes/validate_order', array(
            'setOrder',
            'isShippedAsCod',
            'isValidHermesShipment'
        ));

        $validationHelper->expects($this->any())
            ->method('isShippedAsCod')
            ->will($this->returnValue(true));


        /** @var Mage_Sales_Model_Order */
        $order = $this->getModelMock('sales/order', array('getGrandTotal'));
        $order->expects($this->any())->method('getGrandTotal')->will($this->returnValue(33));
        $this->replaceByMock('model', 'sales/order', $order);

        /** @var Mage_Sales_Model_Order_Shipment */
        $shipment = $this->getModelMock('sales/order_shipment', array('getOrder'));
        $shipment->expects($this->any())->method('getOrder')->will($this->returnValue($order));
        $this->replaceByMock('model', 'sales/order_shipment', $shipment);

        $validationHelper->expects($this->any())
            ->method('setOrder')
            ->with($this->equalTo($order))
            ->will($this->returnSelf());

        $this->replaceByMock('helper', 'hermes/validate_order', $validationHelper);

        /** @var Netresearch_Hermes_Model_Parcel */
        $parcel = $this->getModelMock('hermes/parcel', array(
            'setShipment',
            'setParcelClass',
            'setStatusCode',
            'setIncludeCashOnDelivery',
            'setAmountCashOnDeliveryEurocent',
            'save'
        ));
        $parcel->expects($this->any())
            ->method('setShipment')
            ->will($this->returnSelf());
        $parcel->expects($this->any())
            ->method('setParcelClass')
            ->with('XXXXL')
            ->will($this->returnSelf());
        $parcel->expects($this->any())
            ->method('setStatusCode')
            ->will($this->returnSelf());
        $parcel->expects($this->any())
            ->method('setIncludeCashOnDelivery')
            ->will($this->returnSelf());
        $parcel->expects($this->any())
            ->method('setAmountCashOnDeliveryEurocent')
            ->will($this->returnSelf());
        $parcel->expects($this->any())
            ->method('save')
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'hermes/parcel', $parcel);

        Mage::helper('hermes/order')->createParcel($shipment, 'XXXXL');

        /** @var Netresearch_Hermes_Helper_Validate_Order */
        $validationHelper = $this->getHelperMock('hermes/validate_order', array(
            'setOrder',
            'isShippedAsCod'
        ));
        $validationHelper->expects($this->any())
            ->method('setOrder')
            ->with($this->equalTo($order))
            ->will($this->returnSelf());
        $validationHelper->expects($this->any())
            ->method('isShippedAsCod')
            ->will($this->returnValue(false));
        $this->replaceByMock('helper', 'hermes/validate_order', $validationHelper);

        /** @var Netresearch_Hermes_Model_Parcel */
        $parcel = $this->getModelMock('hermes/parcel', array(
            'setShipment',
            'setParcelClass',
            'setStatusCode',
            'setIncludeCashOnDelivery',
            'setAmountCashOnDeliveryEurocent',
            'save'
        ));
        $parcel->expects($this->any())
            ->method('setShipment')
            ->will($this->returnSelf());
        $parcel->expects($this->any())
            ->method('setParcelClass')
            ->with(null)
            ->will($this->returnSelf());
        $parcel->expects($this->any())
            ->method('setStatusCode')
            ->will($this->returnSelf());
        $parcel->expects($this->any())
            ->method('save')
            ->will($this->returnSelf());
        $parcel->expects($this->never())
            ->method('setIncludeCashOnDelivery');
        $this->replaceByMock('model', 'hermes/parcel', $parcel);

        $parcel = Mage::helper('hermes/order')->createParcel($shipment);
    }

    /**
     * @test
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testGetOrderCollection()
    {
        $config = $this->getModelMock('hermes/config', array(
            'getAutocreateOrderStatuses',
            'getInstallationDate',
            'getMaxDaysInPast'
        ));
        $config->expects($this->any())
            ->method('getAutocreateOrderStatuses')
            ->will($this->returnValue(array('processed', 'pending')));
        $config->expects($this->any())
            ->method('getInstallationDate')
            ->will($this->returnValue('2011-01-01'));

        // calculation of difference from current date to one to the fixtures (order_id: 38)
        $oldDate = strtotime('2011-01-02') ;
        $currentDate = strtotime(date('Y-m-d')) ;

        $daysDiff = ($currentDate - $oldDate) / 86400;
        $config->expects($this->any())
            ->method('getMaxDaysInPast')
            ->will($this->returnValue($daysDiff));

        $this->replaceByMock('model', 'hermes/config', $config);

        $orderHelper = Mage::helper('hermes/order');
        $collection = $orderHelper->getOrderCollection();
        $this->assertEquals(1, $collection->count());
    }

    /**
     * @test
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testCollectionIsEmptyIfAutocreateStatesAreMisconfigured()
    {
        $config = $this->getModelMock('hermes/config', array(
            'getAutocreateOrderStatuses',
            'getInstallationDate',
        ));

        $config->expects($this->any())
            ->method('getAutocreateOrderStatuses')
            ->will($this->returnValue(array()));

        $config->expects($this->any())
            ->method('getInstallationDate')
            ->will($this->returnValue('2011-01-01'));

        $this->replaceByMock('model', 'hermes/config', $config);

        $orderHelper = Mage::helper('hermes/order');
        $collection = $orderHelper->getOrderCollection();
        $this->assertEquals(0, $collection->count());
    }

    /**
     * @test
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testCollectionIsEmptyIfOrdersWereBeforeInstallationDate()
    {
        $config = $this->getModelMock('hermes/config', array(
            'getAutocreateOrderStatuses',
            'getInstallationDate',
        ));

        $config->expects($this->any())
            ->method('getAutocreateOrderStatuses')
            ->will($this->returnValue(array('processed', 'pending')));

        $config->expects($this->any())
            ->method('getInstallationDate')
            ->will($this->returnValue('2020-01-01'));

        $this->replaceByMock('model', 'hermes/config', $config);

        $orderHelper = Mage::helper('hermes/order');
        $collection = $orderHelper->getOrderCollection();
        $this->assertEquals(0, $collection->count());
    }

    /**
     * @test
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testGetQtys()
    {
        $orderHelper = Mage::helper('hermes/order');
        $order = Mage::getModel('sales/order')->load(38);
        $this->assertEquals(0, count($orderHelper->getQtys($order)));
    }

    /**
     * @test
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testIsPartialShipment()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $helper = Mage::helper('hermes/order');
        $this->assertFalse($helper->isPartialShipment($shipment));


        $validationHelper = $this->getHelperMock('hermes/validate_order', array('isShippedAsCod'));
        $validationHelper->expects($this->any())
            ->method('isShippedAsCod')
            ->will($this->returnValue(true));
        $this->replaceByMock('helper', 'hermes/validate_order', $validationHelper);

        $shipment = Mage::getModel('sales/order_shipment')->load(9);
        $this->assertFalse($helper->isPartialShipment($shipment));


        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $order = $shipment->getOrder();

        $item1 = new Mage_Sales_Model_Order_Item();
        $item1->setSku('0815');
        $item1->setParentItemId('');
        $item1->setPrice(100);
        $item1->setQtyOrdered(1);
        $item1->setIsVirtual(0);
        $item2 = new Mage_Sales_Model_Order_Item();
        $item2->setSku('4711');
        $item2->setParentItemId('');
        $item2->setPrice(200);
        $item2->setQtyOrdered(10);
        $item2->setIsVirtual(0);

        $order
            ->addItem($item1)
            ->addItem($item2);

        $shipmentItem1 = new Mage_Sales_Model_Order_Shipment_Item();
        $shipmentItem1->setSku('0815');
        $shipmentItem1->setPrice(100);
        $shipmentItem1->setOrderItem($item1);
        $shipmentItem1->getOrderItem()->setQtyShipped(1);
        $shipmentItem2 = new Mage_Sales_Model_Order_Shipment_Item();
        $shipmentItem2->setSku('4711');
        $shipmentItem2->setPrice(100);
        $shipmentItem2->setOrderItem($item2);

        $shipment
            ->addItem($shipmentItem1)
            ->addItem($shipmentItem2);

        $shipment->setOrder($order);

        $this->assertTrue($helper->isPartialShipment($shipment));
    }

}

