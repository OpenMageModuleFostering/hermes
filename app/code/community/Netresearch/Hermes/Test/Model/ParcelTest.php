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
 * Hermes parcel unittest
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Hermes_Test_Model_ParcelTest extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * test if shipment address is set in parcel.
     * street, district and email are excluded as they have their own test methods
     * @see testStreetConcatenation()
     * @see testIrelandDistrict()
     * @see testEmail()
     * @loadFixture shipments
     */
    public function testTransformation()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shippingAddress = $shipment->getShippingAddress();
        $parcel = Mage::getModel('hermes/parcel')->setShipment($shipment);
        
        $lastname = $shippingAddress->getMiddlename() ?
            $shippingAddress->getMiddlename() . ' ' . $shippingAddress->getLastname() :
            $shippingAddress->getLastname();

        $this->assertEquals($shipment->getId(), $parcel->getShipmentId());
        $this->assertEquals($shippingAddress->getFirstname(), $parcel->getReceiverFirstname());
        $this->assertEquals($lastname, $parcel->getReceiverLastname());
        // $parcel->getReceiverStreet(); $parcel->getReceiverAddressAdd();
        $this->assertEquals($shippingAddress->getPostcode(), $parcel->getReceiverPostcode());
        $this->assertEquals($shippingAddress->getCity(), $parcel->getReceiverCity());
        // $parcel->getDistrict();
        $this->assertEquals($shippingAddress->getCountryModel()->getIso3Code(), $parcel->getReceiverCountryCode());
        // $parcel->getReceiverEmail();
        $this->assertEquals($shippingAddress->getTelephone(), $parcel->getReceiverTelephoneNumber());
        $this->assertNull($parcel->getReceiverTelephonePrefix());
    }

    /**
     * test if shipment address is set in parcel if address contains a company name.
     *
     * @loadFixture shipments
     */
    public function testTransformationWithCompanyName()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shippingAddress = $shipment->getShippingAddress();
        $shippingAddress->setFirstname('Apu')
            ->setMiddlename(null)
            ->setLastname('Nahasapeemapetilon')
            ->setCompany('Kwik-E-Mart');

        $shipment->getShippingAddress()->setStreet(array(
            'line1',
            'line2',
            'line3'
        ));
        $parcel = Mage::getModel('hermes/parcel')->setShipment($shipment);
        $this->assertEquals('', $parcel->getReceiverFirstname());
        $this->assertEquals($shippingAddress->getCompany(), $parcel->getReceiverLastname());
        $this->assertEquals('line1', $parcel->getReceiverStreet());
        $this->assertEquals('line2 line3', $parcel->getReceiverAddressAdd());

        $shipment->getShippingAddress()->setStreet(array('street'));
        $parcel = Mage::getModel('hermes/parcel')->setShipment($shipment);
        $this->assertEquals('', $parcel->getReceiverFirstname());
        $this->assertEquals($shippingAddress->getCompany(), $parcel->getReceiverLastname());
        $this->assertEquals('street', $parcel->getReceiverStreet());
        $this->assertEquals('Apu Nahasapeemapetilon', $parcel->getReceiverAddressAdd());
    }

    /**
     * test if shipments of parcels are loaded correctly
     *
     * @loadFixture shipments
     */
    public function testShipmentLoading()
    {
        $parcel = Mage::getModel('hermes/parcel')->setShipmentId(2);
        $this->assertEquals('Fürstenberg', $parcel->getShipment()->getShippingAddress()->getLastname());
    }

    /**
     * test if exception is thrown for lastnames with more than 25 characters
     *
     * @loadFixture shipments
     */
    public function testLastnameOverflow()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $this->assertEquals('Fürstenberg', $shipment->getShippingAddress()->getLastname());
        $parcel = Mage::getModel('hermes/parcel')->setShipment($shipment);
        $this->assertEquals('von Fürstenberg', $parcel->getReceiverLastname());

        $shipment->getShippingAddress()->setMiddlename('Leipzig');
        $mediumLengthLastName = 'an der Weißen Elster';
        $shipment->getShippingAddress()->setLastname($mediumLengthLastName);
        $this->assertEquals($mediumLengthLastName, $shipment->getShippingAddress()->getLastname());
        $this->setExpectedException(
            'Netresearch_Hermes_Model_Client_Exception',
            'Field receiver_lastname must not be longer than 25 characters'
        );
        $parcel = Mage::getModel('hermes/parcel')->setShipment($shipment);
    }

    /**
     * test if exception is thrown for middle and lastnames with more than 25 characters
     *
     * @loadFixture shipments
     */
    public function testMiddleAndLastnameOverflow()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $overlongName = 'Leipzig an der Weißen Elster';
        $shipment->getShippingAddress()->setLastname($overlongName);
        $this->assertEquals($overlongName, $shipment->getShippingAddress()->getLastname());
        $this->setExpectedException(
            'Netresearch_Hermes_Model_Client_Exception',
            'Field receiver_lastname must not be longer than 25 characters'
        );
        $parcel = Mage::getModel('hermes/parcel')->setShipment($shipment);
    }

    /**
     * test if street is concatenated correctly
     *
     * @loadFixture shipments
     */
    public function testStreetConcatenation()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $shipment->getShippingAddress()->setStreet(array(
            'line1',
            'line2',
            'line3'
        ));
        $parcel = Mage::getModel('hermes/parcel')->setShipment($shipment);
        $this->assertEquals('line1', $parcel->getReceiverStreet());
        $this->assertEquals('line2 line3', $parcel->getReceiverAddressAdd());

        $shipment->getShippingAddress()->setStreet(array(
            'line1',
            'line2',
        ));
        $parcel->setShipment($shipment);
        $this->assertEquals('line1', $parcel->getReceiverStreet());
        $this->assertEquals('line2', $parcel->getReceiverAddressAdd());

        $shipment->getShippingAddress()->setStreet(array(
            'line1'
        ));
        $parcel->setShipment($shipment);
        $this->assertEquals('line1', $parcel->getReceiverStreet());
        $this->assertNull($parcel->getReceiverAddressAdd());
    }

    /**
     * test if district is set for ireland shipments
     *
     * @loadFixture shipments
     */
    public function testIrelandDistrict()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $parcel = Mage::getModel('hermes/parcel')->setShipment($shipment);

        $shipment->getShippingAddress()->setRegion('Kilkenny');
        $shipment->getShippingAddress()->setCountryId('DE');
        $parcel->setShipment($shipment);
        $this->assertNull($parcel->getReceiverDistrict());

        $shipment->getShippingAddress()->setCountryId('IE');
        $parcel->setShipment($shipment);
        $this->assertEquals('Kilkenny', $parcel->getReceiverDistrict());
    }

    /**
     * test if country codes are converted correctly
     *
     * @loadFixture shipments
     */
    public function testCountryCodes()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $parcel = Mage::getModel('hermes/parcel')->setShipment($shipment);

        $shipment->getShippingAddress()->setCountryId('AT');
        $parcel->setShipment($shipment);
        $this->assertEquals('AUT', $parcel->getReceiverCountryCode());

        $shipment->getShippingAddress()->setCountryId('BE');
        $parcel->setShipment($shipment);
        $this->assertEquals('BEL', $parcel->getReceiverCountryCode());

        $shipment->getShippingAddress()->setCountryId('DE');
        $parcel->setShipment($shipment);
        $this->assertEquals('DEU', $parcel->getReceiverCountryCode());

        $shipment->getShippingAddress()->setCountryId('DK');
        $parcel->setShipment($shipment);
        $this->assertEquals('DNK', $parcel->getReceiverCountryCode());

        $shipment->getShippingAddress()->setCountryId('IE');
        $parcel->setShipment($shipment);
        $this->assertEquals('IRL', $parcel->getReceiverCountryCode());
    }

    /**
     * test if email is set according to mail sending option
     *
     * @loadFixture shipments
     */
    public function testEmail()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $parcel   = Mage::getModel('hermes/parcel')->setShipment($shipment);
        $store    = Mage::app()->getStore(0)->load(0);

        $path = 'hermes/shipment_options/hermes_mail';
        $previousValue = Mage::getStoreConfig($path);
        $store->setConfig($path, 0);
        $parcel->setShipment($shipment);
        $this->assertNull($parcel->getReceiverEmail());

        $store->setConfig($path, 1);
        $parcel->setShipment($shipment);
        $this->assertEquals('hubertus.von.fuerstenberg@trash-mail.com', $parcel->getReceiverEmail());

        $store->setConfig($path, $previousValue);
    }

    public function testStatusGetter()
    {
        $parcel = Mage::getModel('hermes/parcel');
        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_QUEUED);
        $this->assertTrue($parcel->isQueued(), 'parcel should be queued');
        $this->assertFalse($parcel->isClosed(), 'parcel should not be closed');
        $this->assertFalse($parcel->isInTransmission(), 'parcel should not be in transmission');

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_PROCESSED);
        $this->assertTrue($parcel->isProcessed(), 'parcel should be processed');
        $this->assertFalse($parcel->isClosed(), 'parcel should not be closed');
        $this->assertFalse($parcel->isInTransmission(), 'parcel should not be in transmission');

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CANCEL_QUEUED);
        $this->assertTrue($parcel->isQueuedToCancel(), 'parcel should be queued to be canceled');
        $this->assertTrue($parcel->canBeResumed(), 'parcel should be resumable');
        $this->assertFalse($parcel->isInTransmission(), 'parcel should not be in transmission');

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CANCELED);
        $this->assertTrue($parcel->isCanceled(), 'parcel should be canceled');
        $this->assertTrue($parcel->canBeResumed(), 'parcel should be resumable');
        $this->assertFalse($parcel->isInTransmission(), 'parcel should not be in transmission');

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CANCEL_FAILED);
        $this->assertTrue($parcel->isCancelFailed(), 'parcel cancellation should have failed');
        $this->assertFalse($parcel->isInTransmission(), 'parcel should not be in transmission');

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_NEW_FAILED);
        $this->assertTrue($parcel->isFailed(), 'parcel transmission should have failed');
        $this->assertTrue($parcel->canBeResumed(), 'parcel should be resumable');
        $this->assertFalse($parcel->isInTransmission(), 'parcel should not be in transmission');

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_IN_TRANSMISSION);
        $this->assertTrue($parcel->isInTransmission(), 'parcel should be in transmission');
        $this->assertFalse($parcel->canBeResumed(), 'parcel should NOT be resumable');

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CLOSED);
        $this->assertTrue($parcel->isClosed(), 'parcel should be closed');
        $this->assertFalse($parcel->isInTransmission(), 'parcel should not be in transmission');
        $this->assertFalse($parcel->canBeResumed(), 'parcel should NOT be resumable');

        $this->assertEquals(8, count($parcel->getStatusCodes()));
    }

    public function testErrors()
    {
        $parcel = Mage::getModel('hermes/parcel');
        $parcel->setErrorCode(499);
        $this->assertEquals(499, $parcel->getErrorCode());

        $parcel->setErrorCodes(array(499, 501));
        $this->assertEquals(array(499, 501), $parcel->getErrorCodes());

        $parcel->setErrorMessages(array('foo', 'bar'));
        $this->assertEquals(array('foo', 'bar'), $parcel->getErrorMessages());

        $parcel->setErrorCode(json_encode(array('1', '2')));
        $this->assertEquals(array('1', '2'), $parcel->getErrorCodes());

        $parcel->setErrorMessage(json_encode(array('foo', 'bar')));
        $this->assertEquals(array('foo', 'bar'), $parcel->getErrorMessages());
    }

    public function testGetStatusCodes()
    {
        $parcel = Mage::getModel('hermes/parcel');
        $this->assertTrue(is_array($parcel->getStatusCodes()));
        $this->assertTrue(count($parcel->getStatusCodes()) > 0);
    }

    public function testGetStatusText()
    {
        $parcel = Mage::getModel('hermes/parcel');

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_QUEUED);
        $this->assertEquals("new (queued)", $parcel->getStatusText());

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_PROCESSED);
        $this->assertEquals("processed", $parcel->getStatusText());

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CANCEL_QUEUED);
        $this->assertEquals("cancel (queued)", $parcel->getStatusText());

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CANCELED);
        $this->assertEquals("canceled", $parcel->getStatusText());

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CANCEL_FAILED);
        $this->assertEquals("cancel (failed)", $parcel->getStatusText());

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_NEW_FAILED);
        $this->assertEquals("new (failed)", $parcel->getStatusText());

        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_IN_TRANSMISSION);
        $this->assertEquals("in transmission", $parcel->getStatusText());
        
        $parcel->setStatusCode('foo');
        $this->assertEquals('foo', $parcel->getStatusText());
    }

    public function testRepeatTransmission()
    {
        $parcel = Mage::getModel('hermes/parcel');
        foreach (array(
            Netresearch_Hermes_Model_Parcel::STATUS_CANCEL_QUEUED,
            Netresearch_Hermes_Model_Parcel::STATUS_CANCELED,
            Netresearch_Hermes_Model_Parcel::STATUS_NEW_FAILED,
        ) as $sourceState) {
            $parcel->setStatusCode($sourceState);
            $parcel->repeatTransmission();
            $this->assertEquals(
                Netresearch_Hermes_Model_Parcel::STATUS_QUEUED,
                $parcel->getStatusCode()
            );
        }
        
        /* if state is in_transmission or closed the state shouldn't be reverted
         * to new_queued 
         * 
         */ 
        foreach (array(
            Netresearch_Hermes_Model_Parcel::STATUS_IN_TRANSMISSION,
            Netresearch_Hermes_Model_Parcel::STATUS_CLOSED
        ) as $sourceState) {
            $parcel->setStatusCode($sourceState);
            $parcel->repeatTransmission();
            $this->assertEquals(
                $sourceState,
                $parcel->getStatusCode()
            );
        }
        
        
    }
    
    /**
     * 
     * @loadFixture ../../../var/fixtures//parcels.yaml
     */
    public function testGetHermesOrderNo()
    {
        $parcel   = Mage::getModel('hermes/parcel')->load(7);
        $this->assertEquals(8, $parcel->getHermesOrderNo());
        $parcel   = Mage::getModel('hermes/parcel')->load(8);
        $this->assertNull($parcel->getHermesOrderNo());
    }
    
    /**
     * 
     * @loadFixture ../../../var/fixtures/parcels.yaml
     */
    public function testGetTrackingUrl()
    {
        $parcel   = Mage::getModel('hermes/parcel')->load(7);
        $this->assertEquals(Mage::getModel('hermes/config')->getTrackingUrl($parcel),
            $parcel->getTrackingUrl()
            );
    }
    
    /**
     * 
     * @loadFixture ../../../var/fixtures/parcels.yaml
     */
    public function testGetLabel()
    {
        
        /* simulate response */
        $expectedPdf = 'ThisShouldBeALabel';
        $expected = new Netresearch_Hermes_Model_Client_Response();
        
        $expected->setResult($expectedPdf);
        
        $client     = $this->getModelMock('hermes/client', array('getLabelJpeg', 'getLabelPdf'));
        $client->expects($this->any())
            ->method('getLabelJpeg')
            ->will($this->returnValue($expected));
        $client->expects($this->any())
            ->method('getLabelPdf')
            ->will($this->returnValue($expected));
        $this->replaceByMock('model', 'hermes/client', $client);
        $parcel   = Mage::getModel('hermes/parcel')->load(7);

        $this->assertTrue(is_string($parcel->getLabel('jpeg')));
        $this->assertEquals(Mage::getModel('hermes/client')->getLabelJpeg($parcel)->getResult(), $parcel->getLabel('jpeg'));
        $path = 'hermes/shipment_label_options/hermes/shipment_label_options/shipment_label_option_pdf';
        Mage::app()->getStore(0)->load(0)->setConfig($path, Netresearch_Hermes_Model_Client::PRINT_POSITION_UPPER_LEFT);
        $this->assertEquals(Mage::getModel('hermes/client')
                   ->getLabelPdf($parcel, Netresearch_Hermes_Model_Client::PRINT_POSITION_UPPER_LEFT)->getResult(), $parcel->getLabel('pdf'));
        $this->assertTrue($parcel->isClosed());
        
        
        $client->expects($this->any())
            ->method('getLabelJpeg')
            ->will($this->throwException(Netresearch_Hermes_Model_Client_Exception::createSoapFault('Dummy Fault', 303)));
        $client->expects($this->any())
            ->method('getLabelPdf')
            ->will($this->throwException(Netresearch_Hermes_Model_Client_Exception::createSoapFault('Dummy Fault', 303)));
        $this->replaceByMock('model', 'hermes/client_soap', $client);
        $this->setExpectedException('NetresearchSoapFault');
        $parcel->getLabel('jpeg');
        $parcel->getLabel('pdf', Netresearch_Hermes_Model_Client::PRINT_POSITION_UPPER_LEFT);
    }
    
    public function testAddTrack()
    {
        $hermesOrderNo = '987234';

        $config = $this->getModelMock('hermes/config', array('isTrackingLinkMailEnabled'));
        $config->expects($this->any())
            ->method('isTrackingLinkMailEnabled')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'hermes/config', $config);

        $parcel = $this->getModelMock('hermes/parcel', array('getShipment', 'notifyCustomer'));
        $parcel->setHermesOrderNo($hermesOrderNo);

        $shipment = $this->getModelMock('sales/order_shipment', array('addTrack', 'save'));

        $parcel->expects($this->once())
            ->method('notifyCustomer')
            ->will($this->returnSelf());
        $parcel->expects($this->once())
            ->method('getShipment')
            ->will($this->returnValue($shipment));

        $track = $this->getModelMock('sales/order_shipment_track', array('setNumber', 'setCarrierCode', 'setTitle'));
        $track->expects($this->once())
            ->method('setNumber')
            ->with($this->equalTo($hermesOrderNo))
            ->will($this->returnSelf());
        $track->expects($this->once())
            ->method('setCarrierCode')
            ->with($this->equalTo('hermes'))
            ->will($this->returnSelf());
        $track->expects($this->once())
            ->method('setTitle')
            ->with($this->equalTo('Hermes'))
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'sales/order_shipment_track', $track);

        $shipment->expects($this->once())
            ->method('addTrack')
            ->with($track)
            ->will($this->returnSelf());

        $parcel->addTrack();
    }
    
    
    /**
     * 
     * @loadFixture shipments
     */
    public function testRemoveTrack()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shippingAddress = $shipment->getShippingAddress();
        $shippingAddress->setFirstname('Apu')
            ->setMiddlename(null)
            ->setLastname('Nahasapeemapetilon')
            ->setCompany('Kwik-E-Mart');
        $shipment->getShippingAddress()->setStreet(array(
            'line1',
            'line2',
            'line3'
        ));
        $parcel = Mage::getModel('hermes/parcel')->setShipment($shipment);
        $parcel->setHermesOrderNo('4711');
        $tracks = $shipment->getAllTracks();
        $parcel->addTrack();
        $this->assertTrue((sizeof($parcel->getShipment()->getAllTracks()) > sizeof($tracks)));
        $parcel->removeTrack();
        $shipment = Mage::getModel('sales/order_shipment')->load(2);
        $this->assertEquals(sizeof($tracks), sizeof($shipment->getAllTracks()));
    }

    public function testNotAddTrack()
    {
        $parcel = $this->getModelMock('hermes/parcel', array('getShipment', 'notifyCustomer'));
        $parcel->setHermesOrderNo(null);
        $shipment = $this->getModelMock('sales/order_shipment', array('addTrack'));
        $shipment->expects($this->never())
            ->method('addTrack')
            ->will($this->returnSelf());

        $parcel->addTrack();
    }

    public function testNotNotifyCustomer()
    {
        /**
         * nothing should happen if tracking mail is disabled
         */
        $config = $this->getModelMock('hermes/config', array('isTrackingLinkMailEnabled'));
        $config->expects($this->any())
            ->method('isTrackingLinkMailEnabled')
            ->will($this->returnValue(false));
        $this->replaceByMock('model', 'hermes/config', $config);

        Mage::getModel('hermes/parcel')->notifyCustomer();
    }

    public function testNotifyCustomer()
    {
        $parcel = $this->getModelMock('hermes/parcel', array('getShipment'));
        $parcel->setHermesOrderNo('1234567890');

        $config = $this->getModelMock('hermes/config', array('isTrackingLinkMailEnabled'));

        /**
         * customer should be notified if tracking mail is enabled
         */
        $config->expects($this->any())
            ->method('isTrackingLinkMailEnabled')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'hermes/config', $config);

        $shipment = $this->getModelMock('sales/order_shipment', array('sendEmail', 'setEmailSent', 'save'));
        $shipment->expects($this->once())
            ->method('sendEmail')
            ->with($this->equalTo(true))
            ->will($this->returnSelf());
        $shipment->expects($this->once())
            ->method('setEmailSent')
            ->with($this->equalTo(true))
            ->will($this->returnSelf());

        $parcel->expects($this->once())
            ->method('getShipment')
            ->will($this->returnValue($shipment));

        $parcel->notifyCustomer();
    }
}

