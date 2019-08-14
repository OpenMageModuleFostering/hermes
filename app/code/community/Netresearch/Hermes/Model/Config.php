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
 * Configuration for Hermes module
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Hermes_Model_Config
{
    /*
     * Product classes
     */
    const PRODUCT_CLASS_EXTRA_SMALL = 'XS';
    const PRODUCT_CLASS_SMALL = 'S';
    const PRODUCT_CLASS_MEDIUM = 'M';
    const PRODUCT_CLASS_LARGE = 'L';
    const PRODUCT_CLASS_EXTRA_LARGE = 'XL';

    const ORDER_MAX_DAYS_IN_PAST = 90;

    protected $store = null;

    /**
     * @return Mage_Core_Model_Store
     */
    protected function getDefaultStore()
    {
        if (null === $this->store) {
            $this->store = Mage::app()->getStore(0)->load(0);
        }

        return $this->store;
    }


    /**
     * if Hermes is enabled at all
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return (1 == Mage::getStoreConfig('hermes/general/active'));
    }

    /**
     * if logging of requests and responses is enabled
     *
     * @return boolean
     */
    public function isLoggingEnabled()
    {
        return (1 == Mage::getStoreConfig('hermes/general/logging_enabled'));
    }

    /**
     * if we are in production mode
     *
     * @return boolean
     */
    public function isProductionMode()
    {
        return (false === $this->isTestMode());
    }

    /**
     * if we are in sandbox mode
     *
     * @return boolean
     */
    public function isTestMode()
    {
        return (1 == Mage::getStoreConfig('hermes/general/testmode'));
    }

    /**
     * get Hermes partner id
     *
     * @return string
     */
    public function getPartnerId()
    {
        return Mage::getStoreConfig('hermes/account/partner_id');
    }

    /**
     * get Hermes partner password
     *
     * @return string
     */
    public function getPartnerPwd()
    {
        return Mage::getStoreConfig('hermes/account/api_pwd');
    }

    /**
     * get Hermes user name
     *
     * @return string
     */
    public function getUsername()
    {
        return Mage::getStoreConfig('hermes/account/username');
    }

    /**
     * get Hermes user password
     *
     * @return string
     */
    public function getPassword()
    {
        return Mage::getStoreConfig('hermes/account/password');
    }


    /**
     * get wsdl for API requests
     *
     * @return string wsdl path
     */
    public function getWsdl()
    {
        return Mage::getBaseDir('base') . '/' . Mage::getStoreConfig(
            $this->isTestMode() ? 'hermes/test/wsdl' : 'hermes/prod/wsdl'
        );
    }

    /**
     * get disabled shipping methods
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getDisabledShippingMethods($storeId = 0)
    {
        return Mage::getStoreConfig('hermes/shipment_options/disabled_shipping_methods', $storeId);
    }

    /**
     * get payment methods for COD
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getPaymentMethodsForCod($storeId = 0)
    {
        return Mage::getStoreConfig('hermes/shipment_options/cod_payment_methods', $storeId);
    }

    /**
     * Get the user's products as stored in config
     * @return array
     */
    public function getListOfProductsProducts()
    {
        return Zend_Json::decode(Mage::getStoreConfig('hermes/api_data/props_list_of_products_atg/products'));
    }

    /**
     * Get additional data (other than products) as stored in config
     * @param string $key
     */
    public function getListOfProductsData($key)
    {
        $known_keys = array(
            'number_of_products','dated','label_acceptance_terms_and_conditions',
            'label_acceptance_liability_limit','url_terms_and_conditions',
            'net_price_cash_on_delivery_eurocent','settlement_type',
            'url_hermes_logogram','url_liability_informations',
            'url_packaging_guidelines','url_portal_b2c','vat_info'
        );

        if (!in_array($key, $known_keys)) {
            throw new InvalidArgumentException("'$key' is not a known config key");
        }

        return Mage::getStoreConfig("hermes/api_data/props_list_of_products_atg/$key");
    }

    /**
     * Store the user products as queried from api
     * @param array $userProducts
     */
    public function setListOfProductsProducts(array $userProducts)
    {
        $path = 'hermes/api_data/props_list_of_products_atg/products';
        $value = Zend_Json::encode($userProducts);
        $this->getDefaultStore()->setConfig($path, $value);
        Mage::getConfig()->saveConfig($path, $value);
        return $this;
    }

    /**
     * Set additional data (other than products) as queried from api
     * @param string $key
     * @param string $value
     */
    public function setListOfProductsData($key, $value)
    {
        $known_keys = array(
            'number_of_products','dated','label_acceptance_terms_and_conditions',
            'label_acceptance_liability_limit','url_terms_and_conditions',
            'net_price_cash_on_delivery_eurocent','settlement_type',
            'url_hermes_logogram','url_liability_informations',
            'url_packaging_guidelines','url_portal_b2c','vat_info'
        );

        if (!in_array($key, $known_keys)) {
            throw new InvalidArgumentException("'$key' is not a known config key");
        }

        if (false == is_null($value)) {
            $path = 'hermes/api_data/props_list_of_products_atg/' . $key;
            $this->getDefaultStore()->setConfig($path, $value);
            Mage::getConfig()->saveConfig($path, $value);
        }
        return $this;
    }

    /**
     * Check if shipping method is disabled
     *
     * @param string $shippingCode
     * @return boolean
     */
    public function isAllowedShippingMethod($shippingCode, $storeId = 0)
    {
        $disabledShippingMethods = explode(",", $this->getDisabledShippingMethods($storeId));
        return !in_array(
            $shippingCode,
            $disabledShippingMethods);
    }

    /**
     * Check if it a payment method for COD
     *
     * @param  string $paymentMethodCode
     * @return boolean
     */
    public function isPaymentMethodForCod($paymentMethodCode, $storeId = 0)
    {
        $codPaymentMethods = explode(",", $this->getPaymentMethodsForCod($storeId));
        return in_array(
            $paymentMethodCode,
            $codPaymentMethods);
    }

    /**
     * get all available product classes
     *
     * @return array
     */
    public function getAllProductClasses()
    {
        return array(
            Netresearch_Hermes_Model_Config::PRODUCT_CLASS_EXTRA_SMALL,
            Netresearch_Hermes_Model_Config::PRODUCT_CLASS_SMALL,
            Netresearch_Hermes_Model_Config::PRODUCT_CLASS_MEDIUM,
            Netresearch_Hermes_Model_Config::PRODUCT_CLASS_LARGE,
            Netresearch_Hermes_Model_Config::PRODUCT_CLASS_EXTRA_LARGE
        );
    }


    /**
     * return key => $value array of product classes
     * @return array
     */
    public function getAllProductClassesasKeyValue()
    {
        $productClasses = array();
        foreach ($this->getAllProductClasses() as $productClass) {
            $productClasses[$productClass] = $productClass;
        }
        return $productClasses;
    }

    /**
     * get all allowed shipping countries
     *
     * @return array
     */
    public function getAllowedCountries()
    {
        return explode(",", Mage::getStoreConfig("hermes/general/allowed_countries"));
    }

    /**
     * get all allowed shipping countries for COD
     *
     * @return array
     */
    public function getAllowedCodCountries()
    {
        return explode(",", Mage::getStoreConfig("hermes/general/allowed_cod_countries"));
    }

    /**
     * Check if it is an allowed country
     *
     * @param  string $paymentMethodCode
     * @return boolean
     */
    public function isAllowedCountry($iso3CountryCode)
    {
        return in_array(
            $iso3CountryCode,
            $this->getAllowedCountries());
    }

    /**
     * Check if it is an allowed country for COD
     *
     * @param  string $paymentMethodCode
     * @return boolean
     */
    public function isAllowedCodCountry($iso3CountryCode)
    {
        return in_array(
            $iso3CountryCode,
            $this->getAllowedCodCountries());
    }

    /**
     * Check if merchant is accounted by "Staffelpreis" / "Tiered prices"
     *
     * @return boolean
     */
    public function isTieredPriceMerchant()
    {
         return ("Abrechnung zum Staffelpreis"
                 == $this->getListOfProductsData("settlement_type"));
    }

    /**
     * Check if merchant is accounted by "Durchschnittspreis" / "Flatrate"
     *
     * @return boolean
     */
    public function isFlatrateMerchant()
    {
         return (false === $this->isTieredPriceMerchant());
    }

    /**
     *
     * @return boolean
     */
    public function isHermesMailEnabled()
    {
        return (1 == Mage::getStoreConfig('hermes/shipment_options/hermes_mail'));
    }

    /**
     *
     * @return boolean
     */
    public function isPdfEnabled()
    {
        $labelOptions = explode(',',Mage::getStoreConfig('hermes/shipment_label_options/shipment_label_option'));
        return (!in_array('', $labelOptions) && in_array('pdf', $labelOptions));
    }

    /**
     *
     * @return boolean
     */
    public function isJpegEnabled()
    {
        $labelOptions = explode(',',Mage::getStoreConfig('hermes/shipment_label_options/shipment_label_option'));
        return (!in_array('', $labelOptions) && in_array('jpeg', $labelOptions));
    }

    /**
     *
     * @return boolean
     */
    public function getPdfLabelPosition()
    {
        return Mage::getStoreConfig('hermes/shipment_label_options/shipment_label_option_pdf');
    }

    /**
     * get tracking link (pattern or for given parcel
     *
     * @param Netresearch_Hermes_Model_Parcel|string  $parcel_or_orderNo  Parcel or hermesOrderNo
     * @return string|null Tracking Url, NULL if hermesOrderNo of given parcel is empty
     */
    public function getTrackingUrl($parcel_or_orderNo=null)
    {
        $link = Mage::getStoreConfig('hermes/tracking/url');
        if ($parcel_or_orderNo instanceof Netresearch_Hermes_Model_Parcel) {
            if (0 < strlen($parcel_or_orderNo->getHermesOrderNo())) {
                $parcel_or_orderNo = $parcel_or_orderNo->getHermesOrderNo();
            } else {
                return null;
            }
        }
        if (is_string($parcel_or_orderNo) && 0 < strlen($parcel_or_orderNo)) {
            $link = str_replace('%orderNo%', $parcel_or_orderNo, $link);
        }
        return $link;
    }

    /**
     * if an email should be sent after tracking link creation
     *
     * @return boolean
     */
    public function isTrackingLinkMailEnabled()
    {
        return 1 == Mage::getStoreConfig('hermes/email_options/send_email_tracking_link');
    }

    /**
     * get support mail address
     *
     * @return string
     */
    public function getSupportMail()
    {
        return Mage::getStoreConfig('hermes/info/support_mail');
    }

    /**
     * if autocreation mode is active
     *
     * @return array
     */
    public function isAutocreateEnabled()
    {
        return 1 == Mage::getStoreConfig('hermes/autocreate/enabled');
    }

    /**
     * get order statuses being enabled for automatic shipment creation and Hermes parcel transmission
     *
     * @return array
     */
    public function getAutocreateOrderStatuses()
    {
        return explode(',', Mage::getStoreConfig('hermes/autocreate/order_status'));
    }

    /**
     * get payment methods being enabled for automatic shipment creation and Hermes parcel transmission
     *
     * @return array
     */
    public function getAutocreatePaymentMethods()
    {
        return explode(',', Mage::getStoreConfig('hermes/autocreate/payment_methods'));
    }

    public function getInstallationDate()
    {
        return Mage::getStoreConfig('hermes/general/installation_date');
    }

    /**
     * get the maximum allowed days in past for created at in the past
     *
     * @return int
     */
    public function getMaxDaysInPast()
    {
        return self::ORDER_MAX_DAYS_IN_PAST;
    }
}
