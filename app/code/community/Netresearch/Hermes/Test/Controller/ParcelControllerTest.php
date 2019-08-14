<?php

class Netresearch_Hermes_Test_Controller_ParcelControllerTest
    extends EcomDev_PHPUnit_Test_Case_Controller
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockAdminUserSession();
    }

    /**
     * @loadFixture ../../../var/fixtures/parcels
     */
    public function testEditAction()
    {
        $parcel_id = '6';
        $this->getRequest()->setQuery('id', $parcel_id);
        $this->dispatch('adminhtml/parcel/edit');
        $this->assertRequestRoute('adminhtml/parcel/edit');
        $this->assertLayoutBlockCreated('parcel_edit');
    }

    public function testSaveActionException()
    {
        // mock parcel so it will not be saved
        $mockParcel = $this->getMock('Netresearch_Hermes_Model_Parcel', array('save'));
        // define response of save method
        $mockParcel->expects($this->any())
            ->method('save')
            ->will($this->throwException(new Zend_Db_Adapter_Exception()));
        $this->replaceByMock('model', 'hermes/parcel', $mockParcel);

        // dispatch request
        $this->dispatch('adminhtml/parcel/save');
        $this->assertRequestRoute('adminhtml/parcel/save');

        // assert that error message gets displayed on failure
        $error = Mage::getSingleton('adminhtml/session')->getMessages(true)->toString();
        $this->assertEquals('error: ' . Mage::helper('hermes')->__('Receiver data could not be updated.'), $error);
    }

    public function testTransmitHermesParcelsActionWhenDisabled()
    {
        $this->markTestIncomplete('Incomplete - acl mock missing');
        Mage::app()->getStore(0)->load(0)->setConfig('hermes/general/active', 0);

        $uri = 'adminhtml/parcel/transmitHermesParcels';
        $this->dispatch($uri);
        $this->assertRequestRoute($uri);
        $this->assertRequestControllerName('parcel');
        $this->assertRequestActionName('transmitHermesParcels');
        $this->assertNotEmpty(Mage::getSingleton('adminhtml/session')->getMessages());
        $error = Mage::getSingleton('adminhtml/session')->getMessages(true)->toString();
        $this->assertEquals(
            'error: '. Mage::helper('hermes')->__('Hermes is disabled in configuration. Please enable it before trying to submit parcels.'),
            $error
        );
    }

    public function testTransmitHermesParcelsActionWhenEnabled()
    {
        $this->markTestIncomplete('Incomplete - acl mock missing');
        Mage::app()->getStore(0)->load(0)->setConfig('hermes/general/active', 1);

        $this->assertEquals(0, Mage::getSingleton('adminhtml/session')->getMessages()->count());

        $observer = $this->getModelMock('hermes/observer', array('transmitParcels'));
        $observer->expects($this->any())
            ->method('transmitParcels')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'hermes/observer', $observer);

        $uri = 'adminhtml/parcel/transmitHermesParcels';
        $url = Mage::getSingleton('adminhtml/url')->getUrl($uri);
        $this->dispatch($uri);
        $this->assertRequestRoute($uri);
        $this->assertRequestControllerName('parcel');
        $this->assertRequestActionName('transmitHermesParcels');
        $this->assertEquals(1, Mage::getSingleton('adminhtml/session')->getMessages()->count());
        $warning = Mage::getSingleton('adminhtml/session')->getMessages(true)->toString();
        $this->assertEquals('warning: ' . sprintf(Mage::helper('hermes')->__('No parcels were transfered to Hermes.'),0), $warning);
        $this->reset();

        $observer->expects($this->any())
            ->method('transmitParcels')
            ->will($this->throwException(new Netresearch_Hermes_Model_Client_Exception('foo')));
        $this->replaceByMock('model', 'hermes/observer', $observer);

        $this->dispatch($uri);
        $this->assertNotEmpty(Mage::getSingleton('adminhtml/session')->getMessages());
        $error = Mage::getSingleton('adminhtml/session')->getMessages(true)->toString();
        $this->assertEquals('error: foo', $error);
        $this->reset();

        $observer = $this->getModelMock('hermes/observer', array('transmitParcels'));
        $observer->expects($this->once())
            ->method('transmitParcels')
            ->will($this->returnValue(array('parcels' => 10, 'errors' => 2)));
        $this->replaceByMock('model', 'hermes/observer', $observer);

        $this->dispatch($uri);
        $this->assertNotEmpty(Mage::getSingleton('adminhtml/session')->getMessages());
        $error = Mage::getSingleton('adminhtml/session')->getMessages(true)->toString();
        $this->assertEquals('error: '. sprintf(Mage::helper('hermes')->__('Tried to transfer %d parcels to Hermes, but %d of them raised an error.'), 10, 2), $error);
        $this->reset();
    }

    public function testGetLabelIsRedirectedIfParcelIdIsNotPresent()
    {
        $uri = 'adminhtml/parcel/getLabel/parcelId/fvbhb';
        $this->dispatch($uri);
        $this->assertResponseHttpCode(302);
    }

    /**
     * @loadFixture ../../../var/fixtures/parcels
     */
    public function testGetLabel()
    {

        $uri = 'adminhtml/parcel/getLabel/parcelId/7/format/pdf';
        $expected = base64_encode('Should be a pdf');
        $parcelMock = $this->getModelMock('hermes/parcel', array('getLabel'));
        $parcelMock->expects($this->any())
            ->method('getLabel')
            ->will($this->returnValue($expected));

        $this->replaceByMock('model', 'hermes/parcel', $parcelMock);
        $this->replaceByMock('model', 'hermes/parcel', $parcelMock);
        $this->dispatch($uri);

        $this->assertRequestRoute('adminhtml/parcel/getLabel');
        $this->assertRequestControllerName('parcel');
        $this->assertRequestActionName('getLabel');
        $this->assertEquals(7, $this->getRequest()->getParam('parcelId'));
        $this->assertNotRedirect();
        $this->getResponse()->getOutputBody();
        $this->assertEquals($this->getResponse()->getOutputBody(), $expected);

        $uri = 'adminhtml/parcel/getLabel/parcelId/7/format/jpeg';
        $expected = base64_encode('Should be a pdf');
        $parcelMock = $this->getModelMock('hermes/parcel', array('getLabel'));
        $parcelMock->expects($this->any())
            ->method('getLabel')
            ->will($this->returnValue($expected));

        $this->replaceByMock('model', 'hermes/parcel', $parcelMock);
        $this->dispatch($uri);

        $this->assertRequestRoute('adminhtml/parcel/getLabel');
        $this->assertRequestControllerName('parcel');
        $this->assertRequestActionName('getLabel');
        $this->assertEquals(7, $this->getRequest()->getParam('parcelId'));
        $this->assertNotRedirect();
        $this->getResponse()->getOutputBody();
        $this->assertEquals($this->getResponse()->getOutputBody(), $expected);


        $parcelMock->expects($this->any())
            ->method('getLabel')
            ->will($this->throwException(new Exception('dummy exception')));
        $this->replaceByMock('model', 'hermes/parcel', $parcelMock);
        $this->dispatch($uri);
        $this->assertGreaterThan(0, Mage::getSingleton('adminhtml/session')->getMessages()->count());
    }





    /**
     * @loadFixture ../../../var/fixtures/parcels
     */
    public function testRepeatParcelTransmission()
    {
        /* cleanup messages */
        Mage::getSingleton('adminhtml/session')->getMessages(true);

        $uri = 'adminhtml/parcel/repeatTransmission/parcelId/6';

        $parcel = $this->getModelMock('hermes/parcel', array('getShipmentId', 'save'));
        $parcel->expects($this->once())
            ->method('save');
        $parcel->expects($this->any())
            ->method('getShipmentId')
            ->will($this->returnValue(3));
        $this->replaceByMock('model', 'hermes/parcel', $parcel);

        $this->dispatch($uri);

        $this->assertRequestRoute('adminhtml/parcel/repeatTransmission');
        $this->assertRequestControllerName('parcel');
        $this->assertRequestActionName('repeatTransmission');
        $this->assertEquals(6, $this->getRequest()->getParam('parcelId'));
        $this->assertRedirectTo('adminhtml/sales_shipment/view/shipment_id/3');

        $messages = Mage::getSingleton('adminhtml/session')->getMessages(true);

        $success  = $messages->getItemsByType(Mage_Core_Model_Message::SUCCESS);
        $expectedSuccess = Mage::helper('hermes')->__('Parcel transmission was resumed');
        $this->assertEquals(1, count($success));
        $this->assertEquals($expectedSuccess, $success[0]->getText());

        $warnings = $messages->getItemsByType(Mage_Core_Model_Message::WARNING);
        $this->assertEquals(1, count($warnings));
        $expectedWarning = Mage::helper('hermes')->__(
            'Shipment will be transmitted to Hermes within a short time. If you are in a hurry, you could <a href="%s">trigger prompt transmission</a>.',
            Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/parcel/transmitHermesParcels')
        );
        $this->assertEquals($expectedWarning, $warnings[0]->getText());
    }

    /**
     * @loadFixture parcels
     */
    public function testCancel()
    {
        $uri = 'adminhtml/parcel/cancel/parcelId/8';

        $client = $this->getModelMock('hermes/client', array('cancel'));
        $client->expects($this->once())
            ->method('cancel')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'hermes/client', $client);

        $this->dispatch($uri);

        $this->assertRequestRoute('adminhtml/parcel/cancel');
        $this->assertRequestControllerName('parcel');
        $this->assertRequestActionName('cancel');
        $this->assertEquals(8, $this->getRequest()->getParam('parcelId'));
        $this->assertRedirectTo('adminhtml/sales_shipment/view/shipment_id/3');
    }

    /**
     * @loadFixture parcels
     */
    public function testCancelCatchException()
    {
        $uri = 'adminhtml/parcel/cancel/parcelId/7';

        $client = $this->getModelMock('hermes/client', array('cancel'));
        $client->expects($this->any())
            ->method('cancel')
            ->will($this->throwException(new Exception('dummy exception')));
        $this->replaceByMock('model', 'hermes/client', $client);
        $this->dispatch($uri);
        $this->assertGreaterThan(0, Mage::getSingleton('adminhtml/session')->getMessages()->count());
    }


    /**
     * @loadFixture ../../../var/fixtures/parcels
     */
    public function testCancelNotSucceeded()
    {
        $uri = 'adminhtml/parcel/cancel/parcelId/7';

        $client = $this->getModelMock('hermes/client', array('cancel'));
        $client->expects($this->once())
            ->method('cancel')
            ->will($this->returnValue(false));
        $this->replaceByMock('model', 'hermes/client', $client);

        $this->dispatch($uri);

        $this->assertRequestRoute('adminhtml/parcel/cancel');
        $this->assertRequestControllerName('parcel');
        $this->assertRequestActionName('cancel');
        $this->assertEquals(7, $this->getRequest()->getParam('parcelId'));
        $this->assertRedirectTo('adminhtml/sales_shipment/view/shipment_id/14');
    }


    /**
     * @loadFixture parcels
     */
    public function testCancelOfParcelWithoutHermesOrderNo()
    {
        $uri = 'adminhtml/parcel/cancel/parcelId/8';
        $this->dispatch($uri);

        $this->assertRequestRoute('adminhtml/parcel/cancel');
        $this->assertRequestControllerName('parcel');
        $this->assertRequestActionName('cancel');
        $this->assertEquals(8, $this->getRequest()->getParam('parcelId'));
        $this->assertRedirectTo('adminhtml/sales_shipment/view/shipment_id/3');
    }

    /**
     * @loadFixture ../../../var/fixtures/parcels
     * @loadFixture ../../../var/fixtures/shipments
     */
    public function testMassCancelActionFails()
    {
        $uri = 'adminhtml/parcel/massCancel';

        /* try to cancel not existing shipments */
        $invalidParams = array(
            array(
                'shipmentIds'      => array(),
                'expectedMessages' => array(Mage::helper('hermes')->__('Please select at least one shipment')),
            ),
            array(
                'shipmentIds'      => 22,
                'expectedMessages' => array(Mage::helper('hermes')->__('Please select at least one shipment')),
            ),
            array(
                'shipmentIds'      => array(22),
                'expectedMessages' => array(Mage::helper('hermes')->__('Failed to cancel %s Hermes shipments', 1)),
            ),
            array(
                'shipmentIds'      => array(10),
                'expectedMessages' => array(
                    Mage::helper('hermes')->__('Failed to cancel %s Hermes shipments', 1),
                )
            ),
        );
        foreach ($invalidParams as $invalidCall) {
            $shipmentIds      = $invalidCall['shipmentIds'];
            $expectedMessages = $invalidCall['expectedMessages'];
            Mage::getSingleton('adminhtml/session')->getMessages(true);
            $this->getRequest()->setPost('shipment_ids', $shipmentIds);
            $this->dispatch($uri);

            $this->assertRequestRoute('adminhtml/parcel/massCancel');
            $this->assertRequestControllerName('parcel');
            $this->assertRequestActionName('massCancel');
            $this->assertEquals($shipmentIds, $this->getRequest()->getParam('shipment_ids'));
            $this->assertRedirectTo('adminhtml/sales_shipment/index');

            $messages = Mage::getSingleton('adminhtml/session')->getMessages(true);
            $success  = $messages->getItemsByType(Mage_Core_Model_Message::SUCCESS);
            $error    = $messages->getItemsByType(Mage_Core_Model_Message::ERROR);
            $this->assertEquals(count($expectedMessages), count($error), var_export($shipmentIds, true));
            foreach ($expectedMessages as $expectedMessage) {
                $this->assertEquals(
                    'error: ' . $expectedMessage,
                    current($error)->toString()
                );
                next($error);
            }
            $this->assertEquals(array(), $success);
        }
    }

    /**
     * @loadFixture parcels
     */
    public function testMassCancelActionSuccess()
    {
        $this->markTestSkipped('skipped due to errors');
        $uri = 'adminhtml/parcel/massCancel';

        $commentsCollection = $this->getResourceModelMock('sales/order_shipment_comment_collection', array('save'));
        $commentsCollection->expects($this->any())
            ->method('save')
            ->will($this->returnSelf());
        $this->replaceByMock('resource_model', 'sales/order_shipment_comment_collection', $commentsCollection);

        /* cancel shipment */
        Mage::getSingleton('adminhtml/session')->getMessages(true);
        $this->getRequest()->setPost('shipment_ids', array(3));
        $this->dispatch($uri);

        $this->assertRequestRoute('adminhtml/parcel/massCancel');
        $this->assertRequestControllerName('parcel');
        $this->assertRequestActionName('massCancel');
        $this->assertEquals(array(3), $this->getRequest()->getParam('shipment_ids'));
        $this->assertRedirectTo('adminhtml/sales_shipment/index');

        $messages = Mage::getSingleton('adminhtml/session')->getMessages(true);
        $success  = $messages->getItemsByType(Mage_Core_Model_Message::SUCCESS);
        $error    = $messages->getItemsByType(Mage_Core_Model_Message::ERROR);

        $this->assertEquals(0, count($error));
        $this->assertEquals(1, count($success));
        $this->assertEquals(
            'success: ' . Mage::helper('hermes')->__('Canceled %s Hermes shipments', 1),
            current($success)->toString()
        );
    }
}
