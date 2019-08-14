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
class Netresearch_Hermes_Test_Model_ConfigTest extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * @var Mage_Core_Model_Store
     */
    protected $store;

    /**
     * @var Netresearch_Hermes_Model_Config
     */
    protected $config;

    public function setUp()
    {
        $this->store  = Mage::app()->getStore(0)->load(0);
        $this->config = Mage::getModel('hermes/config');
    }

    protected function setConfig($storeId = 0)
    {
        $this->store  = Mage::app()->getStore($storeId)->load($storeId);
    }

    public function testConfigNodesPresent()
    {
//         $this->assertConfigNodeHasChild('default/netresearch_hermes/general', 'active');
//         $this->assertConfigNodeHasChild('default/netresearch_hermes/general', 'testmode');
//         $this->assertConfigNodeHasChild('default/netresearch_hermes/general', 'logging_enabled');
//         $this->assertConfigNodeHasChild('default/netresearch_hermes/account', 'partner_id');
//         $this->assertConfigNodeHasChild('default/netresearch_hermes/account', 'api_pwd');
    }

    public function testIsEnabled()
    {
        $path = 'hermes/general/active';

        //Check if module is initially disabled
        $this->store->resetConfig();
        $this->assertFalse($this->config->isEnabled());

        $this->store->setConfig($path, 1);
        $this->assertTrue($this->config->isEnabled());

        $this->store->setConfig($path, 0);
        $this->assertFalse($this->config->isEnabled());
    }

    public function testIsLoggingEnabled()
    {
        $path = 'hermes/general/logging_enabled';

        //Check if logging is initially enabled
        $this->assertTrue($this->config->isLoggingEnabled());

        $this->store->setConfig($path, 1);
        $this->assertTrue($this->config->isLoggingEnabled());

        $this->store->setConfig($path, 0);
        $this->assertFalse($this->config->isLoggingEnabled());
    }

    public function testIsProductionMode()
    {
        $path = 'hermes/general/testmode';

        //Check if testmode is initially enabled
        $this->assertTrue($this->config->isTestMode());

        $this->store->setConfig($path, 0);
        $this->assertFalse($this->config->isTestMode());
        $this->assertTrue($this->config->isProductionMode());

        $this->store->setConfig($path, 1);
        $this->assertFalse($this->config->isProductionMode());
        $this->assertTrue($this->config->isTestMode());
    }

    public function testGetPartnerId()
    {
        $path = 'hermes/account/partner_id';
        $previousValue = Mage::getStoreConfig($path);
        $this->assertEquals($previousValue, $this->config->getPartnerId());
        $testValue = 'humbug_' . rand(0, 1000);
        $this->store->setConfig($path, $testValue);
        $this->assertEquals($testValue, $this->config->getPartnerId());
        $this->store->setConfig($path, $previousValue);
    }

    public function testGetPartnerPwd()
    {
        $path = 'hermes/account/api_pwd';
        $previousValue = Mage::getStoreConfig($path);
        $this->assertEquals($previousValue, $this->config->getPartnerPwd());
        $testValue = 'humbug_' . rand(0, 1000);
        $this->store->setConfig($path, $testValue);
        $this->assertEquals($testValue, $this->config->getPartnerPwd());
        $this->store->setConfig($path, $previousValue);
    }

    public function testGetUsername()
    {
        $path = 'hermes/account/username';
        $previousValue = Mage::getStoreConfig($path);
        $this->assertEquals($previousValue, $this->config->getUsername());
        $testValue = 'humbug_' . rand(0, 1000);
        $this->store->setConfig($path, $testValue);
        $this->assertEquals($testValue, $this->config->getUsername());
        $this->store->setConfig($path, $previousValue);
    }

    public function testGetPassword()
    {
        $path = 'hermes/account/password';
        $previousValue = Mage::getStoreConfig($path);
        $this->assertEquals($previousValue, $this->config->getPassword());
        $testValue = 'humbug_' . rand(0, 1000);
        $this->store->setConfig($path, $testValue);
        $this->assertEquals($testValue, $this->config->getPassword());
        $this->store->setConfig($path, $previousValue);
    }

    public function testGetDisabledShippingMethods()
    {
        $this->assertEquals('', Mage::getStoreConfig('hermes/shipment_options/disabled_shipping_methods'), "Disabled Shipping Methods are not empty by default");
    }

    public function testCodOrders()
    {
        $this->assertEquals('', Mage::getStoreConfig('hermes/shipment_options/cod_payment_methods'), "Payment Methods for COD Orders are not empty by default");
    }

    public function testIsAllowedShippingMethod()
    {
        $path = 'hermes/shipment_options/disabled_shipping_methods';

        //Test if unexisting "custom_shipping_method" is allowed
        $this->assertTrue($this->config->isAllowedShippingMethod('custom_shipping_method'));

        //Set "flatrate_flatrate" to now allowed and test if it is not allowed
        $this->store->setConfig($path, 'flatrate_flatrate');
        $this->assertFalse($this->config->isAllowedShippingMethod('flatrate_flatrate'),
            "Expected 'flatrate_flatrate' shipping method to be disabled"
        );

        //Reset not allowed shipping methods and test if  "flatrate_flatrate" is allowed now
        $this->store->setConfig($path, '');
        $this->assertTrue($this->config->isAllowedShippingMethod('flatrate_flatrate'),
            "Expected 'flatrate_flatrate' shipping method was not disabled"
        );

        //Check multistore compatibility
        $this->setConfig(1);
        $this->store->setConfig($path, 'flatrate_flatrate');
        $this->assertTrue($this->config->isAllowedShippingMethod('flatrate_flatrate', 0));
        $this->assertFalse($this->config->isAllowedShippingMethod('flatrate_flatrate', 1));
        $this->store->setConfig($path, '');
        $this->setConfig(0);
    }

    public function testIsPaymentMethodForCod()
    {
        $path = 'hermes/shipment_options/cod_payment_methods';

        //Test if unexisting "custom_payment_method" is allowed
        $this->assertFalse($this->config->isPaymentMethodForCod('custom_payment_method'));

        //Set "checkmo" to cod payment method
        $this->store->setConfig($path, 'checkmo');
        $this->assertTrue($this->config->isPaymentMethodForCod('checkmo'),
            "Expected 'checkmo' payment method is not detected as COD order"
        );

        //Reset cod payment methods and test if  "checkmo" is not detected as cod order anymore
        $this->store->setConfig($path, '');
        $this->assertFalse($this->config->isPaymentMethodForCod('checkmo'),
            "Expected 'checkmo' payment method is detected as COD order"
        );

        //Check multistore compatibility
        $this->setConfig(1);
        $this->store->setConfig($path, 'checkmo');
        $this->assertFalse($this->config->isPaymentMethodForCod('checkmo', 0));
        $this->assertTrue($this->config->isPaymentMethodForCod('checkmo', 1));
        $this->store->setConfig($path, '');
        $this->setConfig(0);
    }

    public function testGetAllProductClasses()
    {
        $this->assertTrue(true === is_array($this->config->getAllProductClasses()));
        $this->assertEquals(
            array(
                Netresearch_Hermes_Model_Config::PRODUCT_CLASS_EXTRA_SMALL,
                Netresearch_Hermes_Model_Config::PRODUCT_CLASS_SMALL,
                Netresearch_Hermes_Model_Config::PRODUCT_CLASS_MEDIUM,
                Netresearch_Hermes_Model_Config::PRODUCT_CLASS_LARGE,
                Netresearch_Hermes_Model_Config::PRODUCT_CLASS_EXTRA_LARGE
            ),
            $this->config->getAllProductClasses(),
            'Not all product classes were returned'
        );
    }

    public function testGetAllowedCountries()
    {
        $this->assertTrue(true === is_array($this->config->getAllowedCountries()));
        $this->assertEquals(
            explode(",", "BEL,DNK,DEU,EST,FIN,FRA,IRL,ITA,LVA,LIE,LTU,LUX,MCO,NLD,POL,AUT,PRT,SWE,CHE,SVK,SVN,ESP,CZE,HUN,GBR"),
            $this->config->getAllowedCountries(),
            'Array of Hermes countries doesn\'t correspond to exptected countries'
        );
    }

    /**
     * @loadExpectation config
     */
    public function testGetAllowedCodCountries()
    {
        $this->assertTrue(true === is_array($this->config->getAllowedCodCountries()));
        $this->assertEquals(
            $this->expected('allowed_cod_countries')->getCountryCodes(),
            $this->config->getAllowedCodCountries(),
            'Array of Hermes COD countries doesn\'t correspond to exptected countries'
        );
    }

    public function testGetListOfProductsDataException()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            '\'netresearch\' is not a known config key'
        );

        $this->config->getListOfProductsData('netresearch');
    }

    public function testSetListOfProductsDataException()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            '\'netresearch\' is not a known config key'
        );

        $this->config->setListOfProductsData('netresearch', 'netresearch');
    }

    public function testIsTieredPriceMerchant()
    {
        $path = 'hermes/api_data/props_list_of_products_atg/settlement_type';

        $this->store->setConfig($path, 'Abrechnung zum Staffelpreis');
        $this->assertTrue(true === $this->config->isTieredPriceMerchant());

        $this->store->setConfig($path, 'Abrechnung zum Durchschnittspreis');
        $this->assertFalse(true === $this->config->isTieredPriceMerchant());
    }

    public function testIsFlatrateMerchant()
    {
        $path = 'hermes/api_data/props_list_of_products_atg/settlement_type';

        $this->store->setConfig($path, 'Abrechnung zum Durchschnittspreis');
        $this->assertTrue(true === $this->config->isFlatrateMerchant());

        $this->store->setConfig($path, 'Abrechnung zum Staffelpreis');
        $this->assertFalse(true === $this->config->isFlatrateMerchant());
    }

    /**
     * @loadExpectation config
     */
    public function testIsAllowedCodCountry()
    {
        $path = 'hermes/general/allowed_cod_countries';
        $expectedAllowedCodCountries = $this->expected('allowed_cod_countries')->getCountryCodes();
        $expectedNotAllowedCodCountries = $this->expected('not_allowed_cod_countries')->getCountryCodes();

        foreach ($expectedAllowedCodCountries as $codCountryCode) {
            $this->assertTrue(true === $this->config->isAllowedCodCountry($codCountryCode));
        }

        foreach ($expectedNotAllowedCodCountries as $codCountryCode) {
            $this->assertFalse(true === $this->config->isAllowedCodCountry($codCountryCode));
        }
    }

    public function testIsPdfEnabled()
    {
        $path = 'hermes/shipment_label_options/shipment_label_option';

        $this->store->setConfig($path, 'pdf');
        $this->assertTrue(true === $this->config->isPdfEnabled());

//        $this->store->setConfig($path, "jpeg,pdf");
//        $this->assertTrue(true === $this->config->isPdfEnabled());
//
//        $this->store->setConfig($path, 'jpeg');
//        $this->assertFalse(false === $this->config->isPdfEnabled());
    }

    public function testIsJpegEnabled()
    {
        $path = 'hermes/shipment_label_options/shipment_label_option';

        $this->store->setConfig($path, 'jpeg');
        $this->assertTrue(true === $this->config->isJpegEnabled());

//        $this->store->setConfig($path, 'jpeg,pdf');
//        $this->assertTrue(true === $this->config->isJpegEnabled());
//
//        $this->store->setConfig($path, 'pdf');
//        $this->assertFalse(false === $this->config->isJpegEnabled());

    }

    public function testGetTrackingUrl()
    {
        $expected = 'https://www.myhermes.de/wps/portal/paket/SISYR?auftragsNummer=%orderNo%';
        $this->assertSame($expected, $this->config->getTrackingUrl());
        $parcel = Mage::getModel('hermes/parcel');
        $this->assertNull($this->config->getTrackingUrl($parcel));
        $this->assertNull($parcel->getTrackingUrl());
        $parcel->setHermesOrderNo('1234567890');
        $expected = 'https://www.myhermes.de/wps/portal/paket/SISYR?auftragsNummer=1234567890';
        $this->assertSame($expected, $this->config->getTrackingUrl($parcel));
        $this->assertSame($expected, $parcel->getTrackingUrl());
    }

    public function testIsTrackingLinkMailEnabled()
    {
        $path = 'hermes/email_options/send_email_tracking_link';

        $this->store->resetConfig();

        $this->store->setConfig($path, 1);
        $this->assertTrue($this->config->isTrackingLinkMailEnabled());

        $this->store->setConfig($path, 0);
        $this->assertFalse($this->config->isTrackingLinkMailEnabled());
    }

    public function testGetSupportMail()
    {
        $path = 'hermes/info/support_mail';

        $this->store->resetConfig();
        $previousValue = Mage::getStoreConfig($path);

        $this->store->setConfig($path, 'hermes@trash-mail.com');
        $this->assertSame('hermes@trash-mail.com', $this->config->getSupportMail());

        $this->store->setConfig($path, 'hermes1@trash-mail.com');
        $this->assertSame('hermes1@trash-mail.com', $this->config->getSupportMail());

        $this->store->setConfig($path, $previousValue);
        $this->assertEquals($previousValue, $this->config->getSupportMail());
    }

    /**
     * test isAutocreateEnabled
     *
     * @test
     */
    public function isAutocreateEnabled()
    {
        $path = 'hermes/autocreate/enabled';

        //Check if auto creation is initially disabled
        $this->store->resetConfig();
        $this->assertFalse($this->config->isAutocreateEnabled());

        $this->store->setConfig($path, 1);
        $this->assertTrue($this->config->isAutocreateEnabled());

        $this->store->setConfig($path, 0);
        $this->assertFalse($this->config->isAutocreateEnabled());
    }

    /**
     * test getAutocreateOrderStatuses
     *
     * @test
     */
    public function getAutocreateOrderStatuses()
    {
        $path = 'hermes/autocreate/order_status';

        $this->store->resetConfig();
        $previousValue = Mage::getStoreConfig($path);

        $this->store->setConfig($path, 'processing,pending');
        $this->assertSame(array('processing', 'pending'), $this->config->getAutocreateOrderStatuses());

        $this->store->setConfig($path, 'processing');
        $this->assertSame(array('processing'), $this->config->getAutocreateOrderStatuses());

        $this->store->setConfig($path, $previousValue);
    }

    /**
     * test getAutocreatePaymentMethods
     *
     * @test
     */
    public function getAutocreatePaymentMethods()
    {
        $path = 'hermes/autocreate/payment_methods';

        $this->store->resetConfig();
        $previousValue = Mage::getStoreConfig($path);

        $this->store->setConfig($path, 'checkmo,ccsave');
        $this->assertSame(array('checkmo', 'ccsave'), $this->config->getAutocreatePaymentMethods());

        $this->store->setConfig($path, 'checkmo');
        $this->assertSame(array('checkmo'), $this->config->getAutocreatePaymentMethods());

        $this->store->setConfig($path, $previousValue);
    }

    /**
     * @test
     */
    public function getInstallationDate()
    {
        $path = 'hermes/general/installation_date';

        $this->store->resetConfig();
        $previousValue = Mage::getStoreConfig($path);

        $this->store->setConfig($path, 'gestern');
        $this->assertSame('gestern', $this->config->getInstallationDate());

        $this->store->setConfig($path, 'vorgestern');
        $this->assertSame('vorgestern', $this->config->getInstallationDate());

        $this->store->setConfig($path, $previousValue);
    }

    /**
     *@test
     */
    public function getMaxDaysInPast()
    {
       $maxDaysInPast = $this->config->getMaxDaysInPast();
       $this->assertTrue(is_numeric($maxDaysInPast));
       $this->assertGreaterThan(0, $maxDaysInPast);

    }
}
