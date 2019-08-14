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
class Netresearch_Hermes_Test_Model_ClientTest extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * @var Mage_Core_Model_Store
     */
    protected $store;
    
    /**
     * @var Netresearch_Hermes_Model_Client
     */
    protected $client;
    protected $mockClient;

    public function setUp()
    {
        $this->store = Mage::app()->getStore(0)->load(0);
        $this->client = Mage::getModel('hermes/client');
        /* mock soap client */
        $this->mockClient = $this->getMock(
            'Netresearch_Hermes_Model_Client_Soap',
            array('propsCheckAvailability', 'propsUserLogin', 'getLastSoapOutputHeaderObjects', 'getLastResponse', 'propsProductlnformation', 'propsListOfProductsATG'),
            array(),
            'SoapClient' . md5(time().rand()),
            true
        );

        $client     = $this->getModelMock('hermes/client', array('isAvailable'));
        $client->expects($this->any())
            ->method('isAvailable')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'hermes/client', $client);
        $this->availableClient = Mage::getModel('hermes/client');
    }

    public function testGetSoapClient()
    {
        $this->store->setConfig('hermes/account/partner_id', 'EXT000159');
        $this->store->setConfig('hermes/account/api_pwd', '171a49c0d02d394a134b17f911332563');
        
        $client = Mage::getModel('hermes/client');
        $soapClient = $client->getSoapClient('userToken', 'partnerToken');
        /* @var $soapClient Netresearch_Hermes_Model_Client_Soap */
        
        // check if we get back a soap client
        $this->assertInstanceOf(
            'Netresearch_Hermes_Model_Client_Soap',
            $soapClient
        );

        // check if soap headers are set accordingly
        //  -> currently not possible as soap headers are, once set, not readable
    }
    
    public function testClientAlias()
    {
        $this->assertModelAlias('hermes/client', 'Netresearch_Hermes_Model_Client');
        $this->assertNotEmpty(Mage::getModel('hermes/client'), 'Client not available');
    }

    public function testWsdlLoading()
    {
        $this->store->setConfig('hermes/general/testmode', 0);
        $prodWsdl = Mage::getModel('hermes/config')->getWsdl();
        $this->assertFileExists(Mage::getModel('hermes/config')->getWsdl());

        $this->store->setConfig('hermes/general/testmode', 1);
        $testWsdl = Mage::getModel('hermes/config')->getWsdl();
        $this->assertFileExists(Mage::getModel('hermes/config')->getWsdl());

        $this->assertNotEquals($testWsdl, $prodWsdl, 'expected to find differing wsdl files for prod and test');
        return;
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
        return;

        $this->assertFileExists(Mage::getModel('hermes/config')->getTestWsdl());
    }

    public function testApiIsAvailable()
    {
        $soapClient = $this->mockClient;
        $soapClient->expects($this->any())
            ->method('addSoapInputHeader')
            ->will($this->returnValue($soapClient));

        /* simulate valid response */
        $response = new StdClass();
        $response->propsCheckAvailabilityReturn = '1.0.0.0001';

        $soapClient->expects($this->any())
            ->method('propsCheckAvailability')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        $this->assertTrue($this->client->isAvailable(), 'expected the web service to be available');
    }

    public function testApiIsNotAvailable()
    {
        $soapClient = $this->mockClient;
        $soapClient->expects($this->any())
            ->method('addSoapInputHeader')
            ->will($this->returnValue($soapClient));

        /* simulate wrong response */
        $response = new StdClass();
        $response->propsCheckAvailabilityReturn = '...';

        $soapClient->expects($this->any())
            ->method('propsCheckAvailability')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        $this->assertFalse($this->client->isAvailable(), 'expected the web service to be not available');

        $soapClient->expects($this->any())
            ->method('propsCheckAvailability')
            ->will($this->throwException(Netresearch_Hermes_Model_Client_Exception::createSoapFault('Dummy Fault', 303)));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        $this->assertFalse($this->client->isAvailable(), 'expected the web service to be not available');
    }

    public function testGetProductInformation()
    {
        $this->store->setConfig('hermes/account/partner_id', 'EXT000159');
        $this->store->setConfig('hermes/account/api_pwd', '171a49c0d02d394a134b17f911332563');

        // prepare propsListOfProductsATG mock response
        $response = new StdClass();
        $response->propsProductlnformationReturn = new StdClass();
        $response->propsProductlnformationReturn->numberOfProducts = 5;
        $response->propsProductlnformationReturn->products = new StdClass();
        $response->propsProductlnformationReturn->productInfoList = array();
        
        $soapClient = $this->mockClient;
        $soapClient->expects($this->any())
            ->method('propsProductlnformation')
            ->will($this->returnValue($response));
        
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);

        $this->assertObjectHasAttribute(
            'productInfoList',
            $this->availableClient->getProductInformation()
        );
    }

    public function testGetProductInformationException()
    {
        $this->store->setConfig('hermes/account/partner_id', 'EXT000159');
        $this->store->setConfig('hermes/account/api_pwd', '171a49c0d02d394a134b17f911332563');
        
        $soapClient = $this->mockClient;
        
        $soapFault = Netresearch_Hermes_Model_Client_Exception::createSoapFault('bar', 0);
        $soapClient->expects($this->any())
            ->method('propsProductlnformation')
            ->will($this->throwException($soapFault));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        
        $this->setExpectedException('Netresearch_Hermes_Model_Client_Exception');
        
        $this->availableClient->getProductInformation();
    }
    
    public function testUpdateListOfProducts()
    {
        $this->store->setConfig('hermes/account/partner_id', 'EXT000159');
        $this->store->setConfig('hermes/account/api_pwd', '171a49c0d02d394a134b17f911332563');

        $this->store->setConfig('hermes/account/username', 'ProPS_DP_120404112737');
        $this->store->setConfig('hermes/account/password', 'ProPS_DP_120404112737');

        // mock soap client
        $soapClient = $this->mockClient;
        
        // prepare propsUserLogin mock response
        $loginResponse = new StdClass();
        $loginResponse->propsUserLoginReturn = 'userToken';
        $soapClient->expects($this->any())
            ->method('propsUserLogin')
            ->will($this->returnValue($loginResponse));
        
        // prepare propsListOfProductsATG mock response

        $response = new StdClass();
        $response->propsListOfProductsATGReturn = new StdClass();
        $response->propsListOfProductsATGReturn->numberOfProducts = 5;
        $response->propsListOfProductsATGReturn->products = new StdClass();

        $productInfo = new StdClass();
        $productInfo->netPriceEurcent = 1102;
        $productInfo->productInfo = new StdClass();
        $productInfo->productInfo->parcelFormat = new stdClass();
        $productInfo->productInfo->parcelFormat->parcelClass = "";
        $productInfo->productInfo->parcelFormat->shortestPlusLongestEdgeCmMin = '0';
        $productInfo->productInfo->parcelFormat->shortestPlusLongestEdgeCmMax = '30';
        $productInfo->productInfo->parcelFormat->thridEdgeCmMax = null;
        $productInfo->productInfo->parcelFormat->weightMinKg = '0.000';
        $productInfo->productInfo->parcelFormat->weigthMaxKg = '25.000';
        
        $productInfo->productInfo->deliveryDestinations = new StdClass();
        $productInfo->productInfo->deliveryDestinations->DeliveryDestination = array(new StdClass());
        $productInfo->productInfo->deliveryDestinations->DeliveryDestination[0]->exclusions = '';
        $productInfo->productInfo->deliveryDestinations->DeliveryDestination[0]->countryCode = 'DEU';
        $response->propsListOfProductsATGReturn->products->ProductWithPrice = array($productInfo);

        $response->propsListOfProductsATGReturn->dated = '2012-04-13T08:34:53+00:00';
        $response->propsListOfProductsATGReturn->labelAcceptanceTermsAndConditions =
            Mage::getStoreConfig('default/hermes/api_data/props_list_of_products_atg/label_acceptance_terms_and_conditions');
        $response->propsListOfProductsATGReturn->labelAcceptanceLiabilityLimit =
            Mage::getStoreConfig('default/hermes/api_data/props_list_of_products_atg/label_acceptance_liability_limit');
        $response->propsListOfProductsATGReturn->urlTermsAndConditions =
            Mage::getStoreConfig('default/hermes/api_data/props_list_of_products_atg/url_terms_and_conditions');
        $response->propsListOfProductsATGReturn->netPriceCashOnDeliveryEurocent =
            Mage::getStoreConfig('default/hermes/api_data/props_list_of_products_atg/net_price_cash_on_delivery_eurocent');
        $response->propsListOfProductsATGReturn->settlementType =
            'Abrechnung zum Durchschnittspreis';
        $response->propsListOfProductsATGReturn->urlHermesLogogram =
            Mage::getStoreConfig('default/hermes/api_data/props_list_of_products_atg/url_hermes_logogram');
        $response->propsListOfProductsATGReturn->urlLiabilityInformations =
            Mage::getStoreConfig('default/hermes/api_data/props_list_of_products_atg/url_liability_informations');
        $response->propsListOfProductsATGReturn->urlPackagingGuidelines =
            Mage::getStoreConfig('default/hermes/api_data/props_list_of_products_atg/url_packaging_guidelines');
        $response->propsListOfProductsATGReturn->urlPortalB2C =
            Mage::getStoreConfig('default/hermes/api_data/props_list_of_products_atg/url_portal_b2c');
        $response->propsListOfProductsATGReturn->vatInfo =
            Mage::getStoreConfig('default/hermes/api_data/props_list_of_products_atg/vat_info');
        
        $soapClient->expects($this->any())
            ->method('propsListOfProductsATG')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);

        $mageConfig = $this->getMock(
            'Mage_Core_Model_Config',
            array('saveConfig'),
            array(),
            'Config' . md5(time().rand()),
            true
        );
        $this->replaceByMock('model', 'core/config', $mageConfig);

        // check for response in general
        $listOfProducts = $this->availableClient->updateListOfProducts();
        $this->assertObjectHasAttribute(
            'numberOfProducts',
            $listOfProducts
        );

        // check that product data have been stored correctly
        $config = Mage::getModel('hermes/config');
        /* @var $config Netresearch_Hermes_Model_Config */
        
        $products = $config->getListOfProductsProducts();
        $this->assertEquals(
            '1102',
            $products['DEU']['product_classes']['all']['netPriceEurcent']
        );

        // check that general data have been stored correctly (especially new timestamp)
        $this->assertEquals(
            $listOfProducts->dated,
            $config->getListOfProductsData('dated')
        );
        
        $this->assertEquals(
            'Abrechnung zum Durchschnittspreis',
            $listOfProducts->settlementType
        );
    }

    public function testFailingRequestUpdateListOfProducts()
    {
        $soapClient = $this->mockClient;
        
        $response = new stdClass();
        $response->propsUserLoginReturn = 'UserToken';
        $soapClient->expects($this->any())
            ->method('propsUserLogin')
            ->will($this->returnValue($response));
        
        $soapFault = Netresearch_Hermes_Model_Client_Exception::createSoapFault('bar', 0);
        $soapClient->expects($this->any())
            ->method('propsListOfProductsATG')
            ->will($this->throwException($soapFault));
        
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        
        $this->setExpectedException('Netresearch_Hermes_Model_Client_Exception', 'bar');
        
        $this->availableClient->updateListOfProducts();
    }

    public function testUpdateListOfProductsException()
    {
        // check for exception handling
        $soapClient = $this->mockClient;

        $response = new stdClass();
        $response->propsUserLoginReturn = 'UserToken';
        $soapClient->expects($this->any())
            ->method('propsUserLogin')
            ->will($this->returnValue($response));
        
        $soapFault = Netresearch_Hermes_Model_Client_Exception::createSoapFault('bar', 0);
        $soapClient->expects($this->any())
            ->method('propsListOfProductsATG')
            ->will($this->throwException($soapFault));
        
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        
        $this->setExpectedException('Netresearch_Hermes_Model_Client_Exception');
        
        $this->availableClient->updateListOfProducts();
    }
    
    public function testGetResponse()
    {
        $soapClient = $this->mockClient;
        $soapClient->expects($this->any())
            ->method('getLastResponse')
            ->will($this->returnValue(42));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        $this->assertEquals(42, $this->client->getLastResponse());
    }

    public function testLogin()
    {
        /* simulate success response header */
        $responseHeader = array('PartnerToken' => 'partnerToken');

        /* simulate success response */
        $response = new StdClass();
        $response->propsUserLoginReturn = 'userToken';

        try {
            $soapClient = $this->mockClient;
            $soapClient->expects($this->any())
                ->method('getLastSoapOutputHeaderObjects')
                ->will($this->returnValue($responseHeader));
            $soapClient->expects($this->any())
                ->method('propsUserLogin')
                ->will($this->returnValue($response));
            
            // all subsequent calls to Mage::getModel('hermes/client_soap')
            // will return the mockClient
            $this->replaceByMock('model', 'hermes/client_soap', $soapClient);

            // assert that login return user token
            $this->assertEquals(
                $response->propsUserLoginReturn,
                $this->availableClient->login(),
                'got wrong (or no) user token'
            );
            
            // assert that user token property was set in client
            $this->assertEquals(
                $response->propsUserLoginReturn,
                $this->availableClient->getUserToken(),
                'got wrong (or no) user token'
            );
            
            // assert that partner token property was set in client
            $this->assertSame(
                $responseHeader['PartnerToken'],
                $this->availableClient->getPartnerToken(),
                'got wrong (or no) partner token'
            );
        } catch (SoapFault $e) {
            $this->fail('failed to login');
        }

        // assert that an exception is thrown when propsUserLogin is called
        $this->setUp();

        $soapFault = Netresearch_Hermes_Model_Client_Exception::createSoapFault('bar', 0);
        $soapClient->expects($this->any())
            ->method('propsUserLogin')
            ->will($this->throwException($soapFault));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        $this->setExpectedException('Netresearch_Hermes_Model_Client_Exception', 'bar');
        $this->availableClient->login();
    }

    /**
     * test login when API is not available
     *
     * @test
     */
    public function testLoginWithApiNotAvailable()
    {
        $soapClient = $this->mockClient;
        
        $soapFault = Netresearch_Hermes_Model_Client_Exception::createSoapFault('bar', 0);
        $soapClient->expects($this->any())
            ->method('propsCheckAvailability')
            ->will($this->throwException($soapFault));
        $this->replaceByMock('model', 'hermes/client_soap', $soapClient);
        $this->setExpectedException('Netresearch_Hermes_Model_Client_Exception', 'Hermes API is currently not available. Please try again later.');
        $this->client->login();
    }
    
    /**
     * temporary test method for doing HRMA-20 QA, actually querying soap api.
     * to include in phpunit, use annotation "test" and run
     * $ phpunit --colors --filter qaApiIsAvailable UnitTests.php
     */
    public function qaApiIsAvailable()
    {
        $this->store->setConfig('hermes/general/testmode', 1);
        $this->store->setConfig('hermes/account/partner_id', 'EXT000159');
        $this->store->setConfig('hermes/account/api_pwd', '171a49c0d02d394a134b17f911332563');
        
        $this->assertTrue($this->client->isAvailable());
    }
    /**
     * temporary test method for doing HRMA-19 QA, actually querying soap api.
     * to include in phpunit, use annotation "test" and run
     * $ phpunit --colors --filter qaApiLogin UnitTests.php
     */
    public function qaApiLogin()
    {
        $this->store->setConfig('hermes/general/testmode', 1);
        $this->store->setConfig('hermes/account/partner_id', 'EXT000159');
        $this->store->setConfig('hermes/account/api_pwd', '171a49c0d02d394a134b17f911332563');
        
        $this->store->setConfig('hermes/account/username', 'ProPS_DP_120404112737');
        $this->store->setConfig('hermes/account/password', 'ProPS_DP_120404112737');
        
        $this->availableClient->login();
        $this->assertNotNull($this->availableClient->getUserToken());
    }
}

