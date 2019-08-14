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
 * Hermes observer unittest
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Hermes_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case_Config
{
    protected $codPaymentMethods;

    /**
     * @var Mage_Core_Model_Store
     */
    protected $store;

    /**
     * @var Netresearch_Hermes_Model_Config
     */
    protected $config;

    protected $mockClient;

    protected $mockParcel;

    /**
     * Set shipment to event object
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @return Varien_Event_Observer
     */
    protected function prepareEvent(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $event = new Varien_Event_Observer();
        $event->setData('shipment', $shipment);

        return $event;
    }

    public function setUp()
    {
        $this->store = Mage::app()->getStore(0)->load(0);
        $this->store->setConfig('hermes/general/active', '1');

        $this->config = Mage::getModel('hermes/config');

        $this->codPaymentMethods = $this->store->getConfig('hermes/shipment_options/cod_payment_methods');
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', 'cod');

        /* mock soap client */
        $this->mockClient = $this->getMock(
            'Netresearch_Hermes_Model_Client_Soap',
            array('propsUserLogin', 'propsListOfProductsATG', 'getLastSoapOutputHeaderObjects'),
            array(),
            'SoapClient' . md5(time().rand()),
            true
        );

        $client     = $this->getModelMock('hermes/client', array('isAvailable'));
        $client->expects($this->any())
            ->method('isAvailable')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'hermes/client', $client);

        parent::setUp();
    }

    public function tearDown()
    {
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', $this->codPaymentMethods);
    }

    public function testUpdateProductList()
    {
        $observer = Mage::getModel('hermes/observer');

        $client = $this->getModelMock('hermes/client', array('updateListOfProducts'));

        $response = new stdClass();
        $response->dated = '1999-02-01';

        $client->expects($this->any())
            ->method('updateListOfProducts')
            ->will($this->returnValue($response));

        $this->replaceByMock('model', 'hermes/client', $client);

        // run observer method
        /* @var $observer Netresearch_Hermes_Model_Observer */
        $updatedList = $observer->updateListOfProducts(new Varien_Event_Observer());
        $this->assertEquals($response->dated, $updatedList->dated);
    }

    public function testSaveHermesShipmentDataObserverDefined()
    {
        $this->assertEventObserverDefined(
            'adminhtml',
            'sales_order_shipment_save_after',
            'hermes/observer',
            'saveHermesShipmentData'
        );
    }

    /**
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testNotSaveHermesShipmentData()
    {
        $observer = Mage::getModel('hermes/observer');
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $event = $this->prepareEvent($shipment);

        // simulate POST data
        $data = array(
            'form_key'         => '5PebJQbzONhCGGRI',
            'comment_text'     => '',
            'shipment'         => array(
                'items' => array('99' => '2') // item #99, qty 2
            )
        );
        Mage::app()->getRequest()->setPost($data);

        // (1) check behaviour when we don't know anything about Hermes shipping
        $this->assertFalse($observer->saveHermesShipmentData($event));

        // simulate POST data
        $data = array(
            'form_key'         => '5PebJQbzONhCGGRI',
            'ship_with_hermes' => '0',
            'parcel_class'     => 'standard',
            'comment_text'     => '',
            'shipment'         => array(
                'items' => array('99' => '2') // item #99, qty 2
            )
        );

        // (1) check behaviour when shipping should not be done with hermes
        Mage::app()->getRequest()->setPost($data);
        $this->assertFalse($observer->saveHermesShipmentData($event));

        // (2) check behaviour when shipping method is amongst disabled methods
        $this->store->setConfig('hermes/shipment_options/disabled_shipping_methods', 'flatrate_flatrate');
        $data['ship_with_hermes'] = '1';
        Mage::app()->getRequest()->setPost($data);
        $this->assertFalse($observer->saveHermesShipmentData($event));
        $this->store->setConfig('hermes/shipment_options/disabled_shipping_methods', '');

        // (3) check behaviour when extension is disabled
        $this->store->setConfig('hermes/general/active', '0');
        Mage::app()->getRequest()->setPost($data);
        $this->assertFalse($observer->saveHermesShipmentData($event));
        $this->store->setConfig('hermes/general/active', '1');
    }


    /**
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testSaveHermesShipmentData()
    {
        $observer = Mage::getModel('hermes/observer');
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $event = $this->prepareEvent($shipment);

        $this->assertNotEquals('cod', $event->getShipment()->getOrder()->getPayment()->getMethod());

        // mock parcel so it will not be saved
        $mockParcel = $this->getModelMock('hermes/parcel', array('save'));

        // define response of save method
        $mockParcel->expects($this->any())
            ->method('save')
            ->will($this->returnSelf());

        // all subsequent calls to Mage::getModel('hermes/parcel')
        // will return the mockParcel
        $this->replaceByMock('model', 'hermes/parcel', $mockParcel);

        // simulate POST data
        $data = array(
            'form_key' => '5PebJQbzONhCGGRI',
            'ship_with_hermes' => '1',
            'parcel_class' => 'S',
            'shipment' => array(
                'items' => array('99' => '2')
            ),
            'comment_text' => ''
        );

        // simulate normal save
        Mage::app()->getRequest()->setPost($data);
        $parcel = $observer->saveHermesShipmentData($event);
        $this->assertInstanceOf(
            'Netresearch_Hermes_Model_Parcel',
            $parcel
        );
        $this->assertEquals($data['parcel_class'], $parcel->getParcelClass());
        $this->assertNull($parcel->getIncludeCashOnDelivery());
        $this->assertNull($parcel->getAmountCashOnDeliveryEurocent());

        // simulate save with invalid parcel class
        //   -> parcel should be saved with empty parcel_class
        $data['parcel_class'] = 'humbug';

        Mage::app()->getRequest()->setPost($data);
        $parcel = $observer->saveHermesShipmentData($event);
        $this->assertInstanceOf(
            'Netresearch_Hermes_Model_Parcel',
            $parcel
        );
        $this->assertNull($parcel->getParcelClass());


        // simulate save by third party that does not provide hermes information
        //   -> parcel should not be generated
        unset($data['ship_with_hermes']);
        unset($data['parcel_class']);
        unset($data['shipment']);

        Mage::app()->getRequest()->setPost($data);
        $this->assertFalse($observer->saveHermesShipmentData($event));
    }

    /**
     * In auto mode, no post data are given.
     * TODO: expand test in future sprints
     *
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testSaveHermesShipmentDataAutoMode()
    {
        $observer = Mage::getModel('hermes/observer');
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $event = $this->prepareEvent($shipment);

        $this->assertNotEquals('cod', $event->getShipment()->getOrder()->getPayment()->getMethod());

        $parcel = $observer->saveHermesShipmentData($event);
        $this->assertNull($parcel);
    }

    /**
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function testSaveHermesShipmentDataCod()
    {
        $observer = Mage::getModel('hermes/observer');
        $shipment = Mage::getModel('sales/order_shipment')->load(6);
        $event = $this->prepareEvent($shipment);

        $this->assertEquals('cod', $event->getShipment()->getOrder()->getPayment()->getMethod());

        // mock parcel so it will not be saved
        $mockParcel = $this->getModelMock('hermes/parcel', array('save'));

        // define response of save method
        $mockParcel->expects($this->any())
            ->method('save')
            ->will($this->returnSelf());

        // all subsequent calls to Mage::getModel('hermes/parcel')
        // will return the mockParcel
        $this->replaceByMock('model', 'hermes/parcel', $mockParcel);

        // simulate POST data
        $data = array(
            'form_key' => '5PebJQbzONhCGGRI',
            'ship_with_hermes' => '1',
            'parcel_class' => 'XL',
            'shipment' => array(
                'items' => array('99' => '2')
            ),
            'comment_text' => ''
        );

        // simulate normal save
        Mage::app()->getRequest()->setPost($data);
        $parcel = $observer->saveHermesShipmentData($event);
        $this->assertInstanceOf(
            'Netresearch_Hermes_Model_Parcel',
            $parcel
        );
        $grandTotalEurocent = 100 * $event->getShipment()->getOrder()->getGrandTotal();
        $this->assertEquals('1', $parcel->getIncludeCashOnDelivery());
        $this->assertEquals((int)$grandTotalEurocent, $parcel->getAmountCashOnDeliveryEurocent());
    }

    /**
     * test getter for list of ids of parcels to submit
     *
     * @loadFixture ../../../var/fixtures/parcels
     */
    public function testGetParcelIdsToSubmit()
    {
        $this->assertEquals(
            array(1, 2),
            Mage::getModel('hermes/observer')->getParcelIdsToSubmit()
        );
    }

    /**
     * _mockClientForImportOrders
     *
     * @return void
     */
    protected function _mockClientForImportOrders($orders)
    {
        $result = new StdClass();
        $result->propsImportOrdersReturn = new StdClass();
        $result->propsImportOrdersReturn->orderResponses = new StdClass();
        $result->propsImportOrdersReturn->orderResponses->OrderResponse = $orders;

        $client = $this->getModelMock('hermes/client', array('sendParcels'));
        $client->expects($this->once())
            ->method('sendParcels')
            ->will($this->returnValue($result));
        $this->replaceByMock('model', 'hermes/client', $client);
    }

    /**
     * test parcel submission
     *
     * @test
     * @loadFixture ../../../var/fixtures/parcels
     */
    public function transmitParcelsSuccessfully()
    {
        $orders = array(new StdClass(), new StdClass());
        $orders[0]->orderNo = '22';
        $orders[1]->orderNo = '23';
        $this->_mockClientForImportOrders($orders);

        /* avoid saving shipment comments */
        $shipmentCommentsCollection = $this->getResourceModelMock('sales/order_shipment_comment_collection', array('save'));
        $this->replaceByMock('resource', 'sales/order_shipment_comment_collection', $shipmentCommentsCollection);


        $shipmentMock = $this->getModelMock('sales/order_shipment', array('getCommentsCollection', 'addComment'));
        $shipmentMock->expects($this->any())
            ->method('getCommentsCollection')
            ->will($this->returnValue($shipmentCommentsCollection));
        $this->replaceByMock('model', 'sales/order_shipment', $shipmentMock);

        /* execute test subject */
        $parcel = $this->getModelMock('hermes/parcel', array('getShipment', 'addTrack'));
        $parcel->expects($this->any())
            ->method('getShipment')
            ->will($this->returnValue($shipmentMock));

        $parcel->expects($this->any())
            ->method('addTrack')
            ->will($this->returnValue($parcel));
        $this->replaceByMock('model', 'hermes/parcel', $parcel);


        $this->assertEquals(
            array('parcels' => 2, 'errors' => 0),
            Mage::getModel('hermes/observer')->transmitParcels(new Varien_Event_Observer())
        );

        /* check results for the first parcel */
        $this->assertEquals('2', $parcel->getId());
        $this->assertEquals('23', $parcel->getHermesOrderNo());
        $this->assertEquals(Netresearch_Hermes_Model_Parcel::STATUS_PROCESSED, $parcel->getStatusCode());
        $this->assertEmpty($parcel->getErrorCode());
    }

    /**
     * test parcel submission
     *
     * @test
     * @loadFixture ../../../var/fixtures/parcels
     */
    public function transmitParcelsWithError()
    {
        $orders = array(new stdClass(), new stdClass());
        $orders[0]->orderNo = '24';

        $orders[1]->orderNo = '22';
        $orders[1]->exceptionItems = new StdClass();
        $orders[1]->exceptionItems->ExceptionItem = array(new StdClass(), new StdClass());
        $orders[1]->exceptionItems->ExceptionItem[0] = new StdClass();
        $orders[1]->exceptionItems->ExceptionItem[0]->errorCode = 123;
        $orders[1]->exceptionItems->ExceptionItem[0]->errorMessage = 'Foobar';
        $orders[1]->exceptionItems->ExceptionItem[1] = new StdClass();
        $orders[1]->exceptionItems->ExceptionItem[1]->errorCode = 256;
        $orders[1]->exceptionItems->ExceptionItem[1]->errorMessage = 'xyz';
        $this->_mockClientForImportOrders($orders);

        /* expect shipments comments to be set */
        /*
        $shipment = $this->getModelMock('sales/order_shipment', array('addComment'));
        $shipment->expects($this->any())
            ->method('addComment')
            ->with($this->equalTo(
                'HERMES::<span class="error">Some errors occured during transmission to Hermes:</span><br />Foobar<br />xyz'
            ));
        $this->replaceByMock('model', 'sales/order_shipment', $shipment);
        */

        /* avoid saving shipment comments */
        $shipmentCommentsCollection = $this->getResourceModelMock('sales/order_shipment_comment_collection', array('save'));
        $this->replaceByMock('resource', 'sales/order_shipment_comment_collection', $shipmentCommentsCollection);

        $shipmentMock = $this->getModelMock('sales/order_shipment', array('getCommentsCollection', 'addComment'));
        $shipmentMock->expects($this->any())
            ->method('getCommentsCollection')
            ->will($this->returnValue($shipmentCommentsCollection));
        $this->replaceByMock('model', 'sales/order_shipment', $shipmentMock);

        /* execute test subject */
        $parcel = $this->getModelMock('hermes/parcel', array('getShipment', 'addTrack'));
        $parcel->expects($this->any())
            ->method('getShipment')
            ->will($this->returnValue($shipmentMock));

        $parcel->expects($this->any())
            ->method('addTrack')
            ->will($this->returnValue($parcel));
        $this->replaceByMock('model', 'hermes/parcel', $parcel);

        /* execute test subject */
        $parcel = Mage::getModel('hermes/parcel')->load(2);
        $this->assertEquals(
            array('parcels' => 2, 'errors' => 1),
            Mage::getModel('hermes/observer')->transmitParcels(new Varien_Event_Observer())
        );

        /* check results for the second parcel */
        $this->assertEquals('2', $parcel->getId());
        $this->assertEquals('22', $parcel->getHermesOrderNo());
        $this->assertEquals(Netresearch_Hermes_Model_Parcel::STATUS_NEW_FAILED, $parcel->getStatusCode());
        $this->assertNotEmpty($parcel->getErrorCode());
        $this->assertEquals(array(123, 256), $parcel->getErrorCodes());

    }

    /**
     * @test
     * @loadFixture ../../../var/fixtures/parcels
     */
    public function testFailedRequest()
    {
        $client = $this->getModelMock('hermes/client', array('sendParcels'));
        $client->expects($this->once())
            ->method('sendParcels')
            ->will($this->returnValue(false));
        $this->replaceByMock('model', 'hermes/client', $client);

        $this->setExpectedException('Netresearch_Hermes_Model_Client_Exception');
        Mage::getModel('hermes/observer')->transmitParcels(new Varien_Event_Observer());
    }

    /**
     * test
     *
     * @test
     * @loadFixture ../../../var/fixtures/orderList1
     */
    public function createParcelsForHermes()
    {

        $config = $this->getModelMock('hermes/config',
            array('isAutocreateEnabled',
                  'getAutocreateOrderStatuses',
                  'getInstallationDate',
                  'isPaymentMethodForCod'
                ));
        $config->expects($this->any())
            ->method('isAutocreateEnabled')
            ->will($this->returnValue(true));
        $config->expects($this->any())
            ->method('getAutocreateOrderStatuses')
            ->will($this->returnValue(array('processed', 'pending')));
        $config->expects($this->any())
            ->method('getInstallationDate')
            ->will($this->returnValue(strtotime('2011-01-01')));
        $config->expects($this->any())
            ->method('isPaymentMethodForCod')
            ->will($this->returnValue(strtotime(false)));
        $this->replaceByMock('model', 'hermes/config', $config);

        $parcel = Mage::getModel('hermes/parcel');
        $parcel->setId(777);

        $shipment = Mage::getModel('sales/order_shipment');
        $shipment->setId(7777);

        $helper = $this->getHelperMock('hermes/order', array('createShipment', 'createParcel'));
        $helper->expects($this->any())
            ->method('createShipment')
            ->with($this->isInstanceOf('Mage_Sales_Model_Order'))
            ->will($this->returnValue($shipment));
        $helper->expects($this->any())
            ->method('createParcel')
            ->will($this->returnValue($parcel));

        $this->replaceByMock('helper', 'hermes/order', $helper);
        $store = Mage::app()->getStore(0)->load(0);
        //Check if auto creation is initially disabled
        $store->resetConfig();


        $path = 'hermes/autocreate/order_status';
        $store->setConfig($path, 'processing,pending');

        $store->setConfig($path, 1);
        $result = Mage::getModel('hermes/observer')->createParcelsForHermes(new Varien_Event_Observer());
        $this->assertTrue(is_array($result));
        $this->assertGreaterThan(0, count($result));

    }

    public function testCreateHermesParcelsIsEmptyIfAutocreateIsDisabled()
    {
        $config = $this->getModelMock('hermes/config',
            array('isAutocreateEnabled'));
        $config->expects($this->any())
            ->method('isAutocreateEnabled')
            ->will($this->returnValue(false));
        $this->replaceByMock('model', 'hermes/config', $config);
        $result = Mage::getModel('hermes/observer')->createParcelsForHermes(new Varien_Event_Observer());
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

}
