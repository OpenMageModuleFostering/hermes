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
 * Hermes API client unittest
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Hermes_Test_Model_OrderTest extends EcomDev_PHPUnit_Test_Case
{
    protected $store;
    protected $client;

    public function setUp()
    {
        parent::setUp();
        $this->store      = Mage::app()->getStore(0)->load(0);
        $client     = $this->getModelMock('hermes/client', array('isAvailable'));
        $client->expects($this->any())
            ->method('isAvailable')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'hermes/client', $client);
        $this->client = Mage::getModel('hermes/client');
    }

    /**
     * Retrieves list of order ids for some purpose
     *
     * this test case is just a test implementation to show how fixtures and expectations work
     *
     * @test
     * @loadFixture orderList
     * @loadExpectation
     */
    public function orderList()
    {
        $collection = Mage::getModel('sales/order')->getCollection();
        // Check that number of items the same as expected value
        $this->assertEquals(
            $this->_getExpectations()->getCount(),
            $collection->count()
        );
        $order = Mage::getModel('sales/order')->load(1);
        $this->assertEquals('1000000001', $order->getIncrementId());
        $this->assertEquals('1', $order->getBillingAddressId());
        $this->assertTrue(is_object($order->getBillingAddress()), 'Adresse sollte ein Objekt sein');
        $this->assertEquals(42, $order->getBillingAddress()->getId());
        $this->assertEquals('Hubertus', $order->getBillingAddress()->getFirstname());
        $this->assertEquals('Hubertus ', $order->getBillingAddress()->getName());
    }

    /**
     * test limits of parcel count (should be 500 max.)
     * 
     * @test
     */
    public function testImportOrdersBelowCountLimit()
    {
        $parcelIds = range(101, Netresearch_Hermes_Model_Client::IMPORT_ORDERS_MAX_COUNT+100);
        $client = Mage::getModel('hermes/client');
        $this->setExpectedException(
            'Netresearch_Hermes_Model_Client_Exception',
            'None of the given parcels could be sent to Hermes'
        );
        $client->sendParcels($parcelIds);
    }

    /**
     * test limits of parcel count (should be 500 max.)
     * 
     * @test
     */
    public function testImportOrdersOverCountLimit()
    {
        $parcelIds = range(1, Netresearch_Hermes_Model_Client::IMPORT_ORDERS_MAX_COUNT + 1);
        $client = Mage::getModel('hermes/client');
        $this->setExpectedException(
            'Netresearch_Hermes_Model_Client_Exception',
            sprintf(
                'Exceeded maximum order limit: SOAP method propsImportOrders does accept up to %d orders per call (you tried to send %d)',
                Netresearch_Hermes_Model_Client::IMPORT_ORDERS_MAX_COUNT,
                count($parcelIds)
            )
        );
        $client->sendParcels($parcelIds);
    }

    /**
     * test transformation of parcels into Hermes orders
     * 
     * @test
     * @loadFixture parcels
     */
    public function testParcelDataTransformation()
    {
        $this->markTestSkipped('skipped due to bug');
        $expected = array(
            array(
                'orderNo'                      => null,
                'receiver'                     => array(
                    'firstname'   => 'Hubertus',
                    'lastname'    => 'von Fürstenberg',
                    'street'      => 'An der Tabaksmühle 3a',
                    'houseNumber' => '',
                    'postcode'    => '04229',
                    'addressAdd'  => '21. Etage',
                    'city'        => 'Leipzig',
                    'countryCode' => 'DEU',
                    'email'       => 'hubertus.von.fuerstenberg@trash-mail.com'
                ),
                'clientReferenceNumber'        => '1000000001',
                'parcelClass'                  => 'M',
                'amountCashOnDeliveryEurocent' => '0',
                'includeCashOnDelivery'        => false,
                'withBulkGoods'                => false
            )
        );
        $this->assertEquals($expected, Mage::getModel('hermes/client')->getConvertedParcelData(array(1)));
    }

    /**
     * propsImportOrders LIVE mode
     *
     * this test calls the real Hermes API
     * therefor it should not be executed regularly
     * 
     * @loadFixture parcels
     */
    public function propsImportOrdersLIVE()
    {
        $this->store->setConfig('hermes/account/partner_id', 'EXT000159');
        $this->store->setConfig('hermes/account/api_pwd', '171a49c0d02d394a134b17f911332563');

        $this->store->setConfig('hermes/account/username', 'ProPS_DP_120404112737');
        $this->store->setConfig('hermes/account/password', 'ProPS_DP_120404112737');

        $this->store->setConfig('hermes/general/testmode', 1);
        $response = Mage::getModel('hermes/client')->sendParcels(array(1));
        die(var_dump(__FILE__ . ' on line ' . __LINE__ . ':', $response));
    }

    /**
     * test import orders
     * 
     * @test
     * @loadFixture parcels
     */
    public function propsImportOrders()
    {
        /* mock client */
        $client = $this->getMock(
            'Netresearch_Hermes_Model_Client',
            array('login', 'getConvertedParcelData'),
            array(),
            'Client' . md5(time().rand()),
            true
        );
        $client->expects($this->once())
            ->method('login')
            ->will($this->returnValue(true));
        $client->expects($this->once())
            ->method('getConvertedParcelData')
            ->will($this->returnValue(array('foo' => 'bar')));
        $this->replaceByMock('model', 'hermes/client', $client);

        /* mock soap client */
        $soapClient = $this->getMock(
            'Netresearch_Hermes_Model_Client_Soap',
            array('propsImportOrders'),
            array(),
            'SoapClient' . md5(time().rand()),
            true
        );
        $soapClient->expects($this->once())
            ->method('propsImportOrders')
            ->with($this->equalTo(array('requestedOrders' => array('propsOrders' => array('foo' => 'bar')))));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);

        $client = Mage::getModel('hermes/client');
        try {
            $client->sendParcels(array(1));
        } catch (SoapFault $e) {
            $this->fail();
        }
    }

    /**
     * test failing order import
     * 
     * @test
     * @loadFixture parcels
     */
    public function testFailingOrderImport()
    {
        $soapClient = $this->getModelMock('hermes/client_soap', array('propsImportOrders', 'propsUserLogin', 'getLastSoapOutputHeaderObjects'));

        // mock propsImportOrders method
        $soapFault = Netresearch_Hermes_Model_Client_Exception::createSoapFault('foo', 0);
        $soapClient->expects($this->any())
            ->method('propsImportOrders')
            ->will($this->throwException($soapFault));

        // mock propsUserLogin method
        $response = new stdClass();
        $response->propsUserLoginReturn = 'UserToken';

        $soapClient->expects($this->any())
            ->method('propsUserLogin')
            ->will($this->returnValue($response));

        $response = array('PartnerToken' => 'PartnerToken');
        $soapClient->expects($this->any())
            ->method('getLastSoapOutputHeaderObjects')
            ->will($this->returnValue($response));

        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        $this->setExpectedException('Netresearch_Hermes_Model_Client_Exception', ' receiver_lastname');
        Mage::getModel('hermes/client')->sendParcels(array(1));
    }

    public function testGetSingleJpeg()
    {
        $soapClient = $this->getModelMock('hermes/client_soap', array('propsOrderPrintLabelJpeg', 'propsUserLogin', 'getLastSoapOutputHeaderObjects'));

        /* simulate response (propsOrderPrintLabelJpeg) */
        $expectedJpeg = 'ThisShouldBeAJpegLabel';
        $expected = new StdClass();

        $expected->propsOrderPrintLabelJpegReturn = new StdClass();
        $expected->propsOrderPrintLabelJpegReturn->jpegData = $expectedJpeg;
        $soapClient->expects($this->any())
            ->method('propsOrderPrintLabelJpeg')
            ->will($this->returnValue($expected));

        // mock login
        $response = new stdClass();
        $response->propsUserLoginReturn = 'UserToken';

        $soapClient->expects($this->any())
            ->method('propsUserLogin')
            ->will($this->returnValue($response));

        $response = array('PartnerToken' => 'PartnerToken');
        $soapClient->expects($this->any())
            ->method('getLastSoapOutputHeaderObjects')
            ->will($this->returnValue($response));

        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);

        /* expect parcel to be closed */
        $parcel = $this->getModelMock('hermes/parcel', array('load', 'setStatusCode', 'save'));
//        $parcel->expects($this->once())
//            ->method('setStatusCode')
//            ->with(Netresearch_Hermes_Model_Parcel::STATUS_CLOSED);
//        $this->replaceByMock('model', 'hermes/parcel', $parcel);
//        $parcel->setHermesOrderNo(1)->setData('status_code', Netresearch_Hermes_Model_Parcel::STATUS_IN_TRANSMISSION);

        /* execute test subject */
        $response = $this->client->getLabelJpeg($parcel);
        $this->assertInstanceOf('Netresearch_Hermes_Model_Client_Response', $response);
        $this->assertEquals($expectedJpeg, $response->getResult());

        /**
         * test exception handling
         */
        $soapFault = Netresearch_Hermes_Model_Client_Exception::createSoapFault('foo', 0);
        $soapClient->expects($this->any())
            ->method('propsOrderPrintLabelJpeg')
            ->will($this->throwException($soapFault));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        $this->setExpectedException('Netresearch_Hermes_Model_Client_Exception', 'foo');
        $this->client->getLabelJpeg($parcel);
    }

    public function testGetSinglePdf()
    {
        $soapClient = $this->getModelMock('hermes/client_soap', array(
            'propsOrderPrintLabelPdf',
            'propsUserLogin',
            'getLastSoapOutputHeaderObjects'
        ));

        /* simulate response */
        $expectedPdf = 'ThisShouldBeAPdfLabel';
        $expected = new StdClass();
        $expected->propsOrderPrintLabelPdfReturn = new StdClass();
        $expected->propsOrderPrintLabelPdfReturn->pdfData = $expectedPdf;
        $soapClient->expects($this->any())
            ->method('propsOrderPrintLabelPdf')
            ->will($this->returnValue($expected));

        // mock login
        $response = new stdClass();
        $response->propsUserLoginReturn = 'UserToken';
        $soapClient->expects($this->any())
            ->method('propsUserLogin')
            ->will($this->returnValue($response));

        $response = array('PartnerToken' => 'PartnerToken');
        $soapClient->expects($this->any())
            ->method('getLastSoapOutputHeaderObjects')
            ->will($this->returnValue($response));

        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);

        /* expect parcel to be closed */
        $parcel = $this->getModelMock('hermes/parcel', array('load', 'setStatusCode', 'save'));
//        $parcel->expects($this->once())
//            ->method('setStatusCode')
//            ->with(Netresearch_Hermes_Model_Parcel::STATUS_CLOSED);
//        $this->replaceByMock('model', 'hermes/parcel', $parcel);
//        $parcel->setHermesOrderNo(1)->setData('status_code', Netresearch_Hermes_Model_Parcel::STATUS_IN_TRANSMISSION);

        /* execute test subject */
        $response = $this->client->getLabelPdf(
            $parcel,
            Netresearch_Hermes_Model_Client::PRINT_POSITION_UPPER_LEFT
        );
        $this->assertInstanceOf('Netresearch_Hermes_Model_Client_Response', $response);
        $this->assertEquals($expectedPdf, $response->getResult());

        /**
         * test exception handling
         */
        $soapFault = Netresearch_Hermes_Model_Client_Exception::createSoapFault('foo', 0);
        $soapClient->expects($this->any())
            ->method('propsOrderPrintLabelPdf')
            ->will($this->throwException($soapFault));

        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);

        $this->setExpectedException('Netresearch_Hermes_Model_Client_Exception', 'foo');

        $client = Mage::getModel('hermes/client');
        $client->getLabelPdf($parcel, Netresearch_Hermes_Model_Client::PRINT_POSITION_UPPER_LEFT);
    }

    public function testGetMergedPdf()
    {
        $soapClient = $this->getModelMock('hermes/client_soap', array(
            'propsOrdersPrintLabelsPdf',
            'propsUserLogin',
            'getLastSoapOutputHeaderObjects'
        ));

        /* simulate response */
        $expectedPdf = 'ThisShouldBeAPdfLabel';
        $expected = new StdClass();
        $expected->propsOrdersPrintLabelsPdfResponse = new StdClass();
        $expected->propsOrdersPrintLabelsPdfResponse->propsOrdersPrintLabelsPdfReturn = new StdClass();
        $expected->propsOrdersPrintLabelsPdfResponse->propsOrdersPrintLabelsPdfReturn->pdfData = $expectedPdf;
        $expectedOrderResponses = array(new StdClass(), new StdClass());
        $expectedOrderResponses[0]->orderNo = '1';
        $expectedOrderResponses[1]->orderNo = '2';
        $expectedOrderResponses[1]->exceptionItems = array(new StdClass());
        $expectedOrderResponses[1]->exceptionItems[0]->errorCode    = 123;
        $expectedOrderResponses[1]->exceptionItems[0]->errorMessage = 'Some error message';
        $expectedOrderResponses[1]->exceptionItems[0]->errorType    = 'U';
        $expected->propsOrdersPrintLabelsPdfResponse->propsOrdersPrintLabelsPdfReturn->orderRes = $expectedOrderResponses;
        $soapClient->expects($this->any())
            ->method('propsOrdersPrintLabelsPdf')
            ->will($this->returnValue($expected));

        // mock login
        $response = new stdClass();
        $response->propsUserLoginReturn = 'UserToken';

        $soapClient->expects($this->any())
            ->method('propsUserLogin')
            ->will($this->returnValue($response));

        $response = array('PartnerToken' => 'PartnerToken');
        $soapClient->expects($this->any())
            ->method('getLastSoapOutputHeaderObjects')
            ->will($this->returnValue($response));

        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);

        $response = $this->client->getLabelsPdf(array(1, 2));
        $this->assertInstanceOf('Netresearch_Hermes_Model_Client_Response', $response);
        $this->assertEquals(1, $response->getSuccessCount());
        $this->assertEquals(array(1), $response->getSuccessItems());
        $this->assertEquals(1, $response->getErrorCount());
        $this->assertEquals(array(2), $response->getFailedItems());
        $this->assertEquals($expectedPdf, $response->getResult());

        $soapFault = Netresearch_Hermes_Model_Client_Exception::createSoapFault('foo', 0);
        $soapClient->expects($this->any())
            ->method('propsOrdersPrintLabelsPdf')
            ->will($this->throwException($soapFault));

        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        $this->setExpectedException('Netresearch_Hermes_Model_Client_Exception');
        $this->client->getLabelsPdf(1);
    }

    /**
     * should cancel transmitted parcel 
     * 
     * @test
     */
    public function shouldCancelParcel()
    {
        /* fake response */
        $response = new stdClass();
        $response->propsOrderDeleteReturn = true;
        $soapClient = $this->getModelMock('hermes/client_soap', array(
            'propsOrderDelete',
        ));
        $soapClient->expects($this->once())
            ->method('propsOrderDelete')
            ->with(array('orderNo' => '123'))
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);

        /* fake availability and login */
        $client = $this->getModelMock('hermes/client', array('isAvailable', 'login'));
        $client->expects($this->once())
            ->method('login');
        $this->replaceByMock('model', 'hermes/client', $client);

        /* expect parcel to be canceled */
        $parcel = Mage::getModel('hermes/parcel');
        $parcel->setHermesOrderNo(123);
        $parcel->setData('status_code', Netresearch_Hermes_Model_Parcel::STATUS_PROCESSED);

        /* execute test subject */
        $this->assertTrue($client->cancel($parcel));
    }

    /**
     * should fail to cancel transmitted parcel 
     * 
     * @test
     */
    public function shouldFailCancelParcel()
    {
        /* fake response */
        $response = new stdClass();
        $response->propsOrderDeleteReturn = false;
        $soapClient = $this->getModelMock('hermes/client_soap', array(
            'propsOrderDelete',
        ));
        $soapClient->expects($this->once())
            ->method('propsOrderDelete')
            ->with(array('orderNo' => '123'))
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);

        /* fake availability and login */
        $client = $this->getModelMock('hermes/client', array('isAvailable', 'login'));
        $client->expects($this->once())
            ->method('login');
        $this->replaceByMock('model', 'hermes/client', $client);

        /* expect parcel to be canceled */
        $parcel = Mage::getModel('hermes/parcel');
        $parcel->setHermesOrderNo(123);
        $parcel->setData('status_code', Netresearch_Hermes_Model_Parcel::STATUS_PROCESSED);

        /* execute test subject */
        $this->assertFalse($client->cancel($parcel));
    }

    /**
     * cancellation should throw Netresearch_Hermes_Model_Client_Exception
     * 
     * @test
     */
    public function shouldThrowCancellationException()
    {
        /* fake response */
        $response = new stdClass();
        $response->propsOrderDeleteReturn = false;
        $soapClient = $this->getModelMock('hermes/client_soap', array(
            'propsOrderDelete',
        ));
        $soapClient->expects($this->any())
            ->method('propsOrderDelete')
            ->will($this->throwException(
                Netresearch_Hermes_Model_Client_Exception::createSoapFault('foo', 0)
            ));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);

        /* fake availability and login */
        $client = $this->getModelMock('hermes/client', array('isAvailable', 'login'));
        $client->expects($this->once())
            ->method('login');
        $this->replaceByMock('model', 'hermes/client', $client);

        /* expect parcel to be canceled */
        $parcel = Mage::getModel('hermes/parcel');
        $parcel->setHermesOrderNo(123);
        $parcel->setData('status_code', Netresearch_Hermes_Model_Parcel::STATUS_PROCESSED);

        $this->setExpectedException(
            'Netresearch_Hermes_Model_Client_Exception',
            'foo'
        );

        /* execute test subject */
        $client->cancel($parcel);
    }
}
