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
 * @category  Netresearch
 * @package   Netresearch_Hermes
 * @author    Thomas Birke <thomas.birke@netresearch.de>
 * @copyright 2012 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.netresearch.de/magento/
 */


/**
 * Hermes API client
 *
 * @category  Netresearch
 * @package   Netresearch_Hermes
 * @author    Thomas Birke <thomas.birke@netresearch.de>
 * @copyright 2012 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.netresearch.de/magento/
 */
class Netresearch_Hermes_Model_Client
{
    /**
     *
     * @var Netresearch_Hermes_Model_Client_Soap
     */
    protected $soapClient;

    /**
     * @var string
     */
    protected $userToken;

    /**
     * @var string
     */
    protected $partnerToken;

    /* property namespace */
    const PROPERTY_NS             = 'http://props.hermes_api.service.hlg.de';

    /* maximum count of orders being accepted by soap method propsImportOrders */
    const IMPORT_ORDERS_MAX_COUNT = 500;

    /* label positioning */
    const PRINT_POSITION_UPPER_LEFT  = 1;
    const PRINT_POSITION_UPPER_RIGHT = 2;
    const PRINT_POSITION_LOWER_LEFT  = 3;
    const PRINT_POSITION_LOWER_RIGHT = 4;

    /**
     * Restructure single items of the soap response
     *
     * @param array $config The array to hold the restructured data
     * @param stdClass $parcelFormat Information regarding package size and weight
     * @param stdClass $destination Information regarding allowed shipping destinations
     * @param string $price The shipping costs for the current format/destination combination
     */
    protected function setProductConfig(array &$config, stdClass $parcelFormat, stdClass $destination, $price)
    {
        if (!isset($config[$destination->countryCode])) {
            $config[$destination->countryCode] = array('product_classes' => array());
        }

        $parcelClass = $parcelFormat->parcelClass;
        if (!$parcelClass) {
            $parcelClass = 'all';
        }

       if (!isset($config[$destination->countryCode]['product_classes'][$parcelClass])) {
           $config[$destination->countryCode]['product_classes'][$parcelClass] = array(
               'weightMinKg' => null,
               'weigthMaxKg' => null,
               'shortestPlusLongestEdgeCmMin' => null,
               'shortestPlusLongestEdgeCmMax' => null,
               'thridEdgeCmMax' => null,
               'exclusions' => null,
               'netPriceEurcent' => $price
           );
       }

       $config[$destination->countryCode]['product_classes'][$parcelClass]['weightMinKg'] =
           $parcelFormat->weightMinKg;
       $config[$destination->countryCode]['product_classes'][$parcelClass]['weigthMaxKg'] =
           $parcelFormat->weigthMaxKg;
       $config[$destination->countryCode]['product_classes'][$parcelClass]['shortestPlusLongestEdgeCmMin'] =
           $parcelFormat->shortestPlusLongestEdgeCmMin;
       $config[$destination->countryCode]['product_classes'][$parcelClass]['shortestPlusLongestEdgeCmMax'] =
           $parcelFormat->shortestPlusLongestEdgeCmMax;
       $config[$destination->countryCode]['product_classes'][$parcelClass]['thridEdgeCmMax'] =
           $parcelFormat->thridEdgeCmMax;
       $config[$destination->countryCode]['product_classes'][$parcelClass]['exclusions'] =
           $destination->exclusions;
    }

    /**
     * Iterate through soap response in order to prepare data for storage
     *
     * @param array $productInformation The soap response
     * @return array The restructured product information
     */
    protected function iterateUserProducts(array $productInformation)
    {
        $userProducts = array();

        foreach ($productInformation as $product) {
//             $destinations = $product->productInfo->deliveryDestinations->DeliveryDestination;
//             if (!is_array($destinations)) {
//                 $this->setProductConfig($userProducts, $product->productInfo->parcelFormat, $destinations, $product->netPriceEurcent);
//                 continue;
//             }

            foreach ($product->productInfo->deliveryDestinations->DeliveryDestination as $destination) {
                $this->setProductConfig($userProducts, $product->productInfo->parcelFormat, $destination, $product->netPriceEurcent);
            }
        }

        return $userProducts;
    }

    /**
     * Store the user's available products to config
     *
     * @param array $productInformation The products as given in soap response
     */
    protected function storeListOfProducts(stdClass $productInformation)
    {
        // restructure products
        $userProducts = $this->iterateUserProducts(
            $productInformation->products->ProductWithPrice
        );
        $this->getConfig()->setListOfProductsProducts($userProducts);

        // store other information
        $this->getConfig()->setListOfProductsData(
            'number_of_products',
            $productInformation->numberOfProducts
        );
        $this->getConfig()->setListOfProductsData(
            'dated',
            $productInformation->dated
        );
        $this->getConfig()->setListOfProductsData(
            'label_acceptance_terms_and_conditions',
            $productInformation->labelAcceptanceTermsAndConditions
        );
        $this->getConfig()->setListOfProductsData(
            'label_acceptance_liability_limit',
            $productInformation->labelAcceptanceLiabilityLimit
        );
        $this->getConfig()->setListOfProductsData(
            'url_terms_and_conditions',
            $productInformation->urlTermsAndConditions
        );
        $this->getConfig()->setListOfProductsData(
            'net_price_cash_on_delivery_eurocent',
            $productInformation->netPriceCashOnDeliveryEurocent
        );
        $this->getConfig()->setListOfProductsData(
            'settlement_type',
            $productInformation->settlementType
        );
        $this->getConfig()->setListOfProductsData(
            'url_hermes_logogram',
            $productInformation->urlHermesLogogram
        );
        $this->getConfig()->setListOfProductsData(
            'url_liability_informations',
            $productInformation->urlLiabilityInformations
        );
        $this->getConfig()->setListOfProductsData(
            'url_packaging_guidelines',
            $productInformation->urlPackagingGuidelines
        );
        $this->getConfig()->setListOfProductsData(
            'url_portal_b2c',
            $productInformation->urlPortalB2C
        );
        $this->getConfig()->setListOfProductsData(
            'vat_info',
            $productInformation->vatInfo
        );
        Mage::getModel('core/config')->removeCache();
    }

    /**
     * get module configuration object
     *
     * @return Netresearch_Hermes_Model_Config
     */
    public function getConfig()
    {
        return Mage::getModel('hermes/config');
    }

    /**
     * get Hermes api
     *
     * @param string $userToken
     * @param string $partnerToken
     * @return Netresearch_Hermes_Model_Client_Soap
     */
    public function getSoapClient($userToken = null, $partnerToken = null)
    {
        if (is_null($this->soapClient)) {
            $this->soapClient = Mage::getModel('hermes/client_soap');
            $this->soapClient->addSoapInputHeader(
                new SoapHeader(
                    self::PROPERTY_NS, 'PartnerId', $this->getConfig()->getPartnerId()
                ), $permanent = true
            );
            $this->soapClient->addSoapInputHeader(
                new SoapHeader(
                    self::PROPERTY_NS, 'PartnerPwd', $this->getConfig()->getPartnerPwd()
                ), $permanent = true
            );
        }

        if (null !== $userToken) {
            $this->soapClient->addSoapInputHeader(
                new SoapHeader(
                    self::PROPERTY_NS, 'UserToken', $userToken
                ), $permanent = true
            );
        }

        if (null !== $partnerToken) {
            $this->soapClient->addSoapInputHeader(
                new SoapHeader(
                    self::PROPERTY_NS, 'PartnerToken', $partnerToken
                ), $permanent = true
            );
        }

        return $this->soapClient;
    }

    public function getLastResponse()
    {
        return $this->getSoapClient()->getLastResponse();
    }

    /**
     * Service propsCheckAvailability
     *
     * checks availability of all required API components
     * and returns the api version in case of success and an error message otherwise
     *
     * @throws Netresearch_Hermes_Model_Client_Exception if api is not available
     * @return string API version
     */
    public function getApiVersion()
    {
        try {
            $soapClient = $this->getSoapClient();
            return $soapClient
                ->propsCheckAvailability()
                ->propsCheckAvailabilityReturn;
        } catch (SoapFault $e) {
            $this->logCommunication('GET API VERSION', Zend_Log::ERR);
            throw Netresearch_Hermes_Model_Client_Exception::soapFault($e);
        }
    }

    /**
     * check if web service is available
     *
     * @return boolean
     */
    public function isAvailable()
    {
        try {
            /**
             * the service returns the current api version, otherwise it is not available
             */
            return 0 < preg_match('/\d+(\.\d+)*/', $this->getApiVersion());
        } catch (Netresearch_Hermes_Model_Client_Exception $e) {
            return false;
        }
    }

    /**
     * Query all Hermes products
     *
     * @throws Netresearch_Hermes_Model_Client_Exception if api is not available
     * @return stdClass
     */
    public function getProductInformation()
    {
        try {
            $soapClient = $this->getSoapClient();
            return $soapClient
                ->propsProductlnformation()
                ->propsProductlnformationReturn;
        } catch (SoapFault $e) {
            $this->logCommunication('LOAD PRODUCTS', Zend_Log::ERR, true);
            throw Netresearch_Hermes_Model_Client_Exception::soapFault($e);
        }
        $this->logCommunication('LOAD PRODUCTS');
    }

    /**
     * Query the Hermes user's available products and update config
     *
     * @throws Netresearch_Hermes_Model_Client_Exception if api is not available
     * @return stdClass
     */
    public function updateListOfProducts()
    {
        try {
            $this->login();

            $soapClient = $this->getSoapClient($this->userToken);
            $userProducts = $soapClient
                ->propsListOfProductsATG()
                ->propsListOfProductsATGReturn;

            $this->logCommunication('UPDATE CUSTOMERS PRODUCT LIST');
            $this->storeListOfProducts($userProducts);

            return $userProducts;
        } catch (SoapFault $e) {
            $this->logCommunication('UPDATE CUSTOMERS PRODUCT LIST', Zend_Log::ERR, true);
            throw Netresearch_Hermes_Model_Client_Exception::soapFault($e);
        }
    }

    /**
     * get user token of Hermes web service
     *
     * @return string
     */
    public function getUserToken()
    {
        return $this->userToken;
    }

    /**
     * get partner token of Hermes web service
     *
     * @return string
     */
    public function getPartnerToken()
    {
        return $this->partnerToken;
    }

    /**
     * Authenticate Hermes user if not authenticated yet and set the
     * properties userToken and partnerToken
     *
     * @throws Netresearch_Hermes_Model_Client_Exception if credentials are missing or wrong
     * @return string User Token
     */
    public function login()
    {
        if (false == $this->isAvailable()) {
            throw new Netresearch_Hermes_Model_Client_Exception('Hermes API is currently not available. Please try again later.');
        }
        if (!$this->userToken) {
            $soapClient = $this->getSoapClient();
            try {
                $response = $soapClient->propsUserLogin(array(
                    'login' => array(
                        'benutzername' => $this->getConfig()->getUsername(),
                        'kennwort'     => $this->getConfig()->getPassword()
                    )
                ));
            } catch (SoapFault $e) {
                $this->logCommunication('LOGIN', Zend_Log::ERR, true);
                throw Netresearch_Hermes_Model_Client_Exception::soapFault($e);
            }
            $this->logCommunication('LOGIN');

            $this->userToken = $response->propsUserLoginReturn;

            $headers = $soapClient->getLastSoapOutputHeaderObjects();
            if (is_array($headers) && array_key_exists('PartnerToken', $headers)) {
                $this->partnerToken = $headers['PartnerToken'];
            }
        }

        return $this->userToken;
    }

    public function logCommunication($prefix, $level=3, $force=false)
    {
        Mage::helper('hermes')->log(
            $prefix . ':' . PHP_EOL . '== REQUEST ==' . PHP_EOL
            . $this->getSoapClient()->getLastRequest()
            . '== RESPONSE ==' . PHP_EOL
            . $this->getSoapClient()->getLastResponse()
            . PHP_EOL,
            $level, $force
        );
    }

    /**
     * set parcels status "in transmission" to avoid duplicate transmission if sendParcels is called by different
     * processes in parallel
     *
     * @param array $parcelIds Ids of parcels to be transmitted
     * @return void
     */
    protected function _setParcelsStatusInTransmission($parcelCollection)
    {
        $resource = Mage::getModel('hermes/parcel')->getResource();
        $resource->beginTransaction();
        $parcelCollection->setDataToAll('status_code', Netresearch_Hermes_Model_Parcel::STATUS_IN_TRANSMISSION)->save();
        $resource->commit();
    }

    /**
     * send up to 500 parcels to Hermes
     *
     * @param array $parcels
     * @return stdClass SOAP response
     */
    public function sendParcels(array $parcelIds)
    {
        if (self::IMPORT_ORDERS_MAX_COUNT < count($parcelIds)) {
            throw new Netresearch_Hermes_Model_Client_Exception(sprintf(
                'Exceeded maximum order limit: SOAP method propsImportOrders does accept up to %d orders per call (you tried to send %d)',
                self::IMPORT_ORDERS_MAX_COUNT,
                count($parcelIds)
            ));
        }
        $parcelCollection = Mage::getModel('hermes/parcel')->getCollection()
            ->addFieldToFilter('id', array('in' => $parcelIds))
            ->addFieldToFilter('status_code', Netresearch_Hermes_Model_Parcel::STATUS_QUEUED);

        $parcelData = $this->getConvertedParcelData($parcelIds);
        if (0 == count($parcelData)) {
            $parcelCollection->setDataToAll('status_code', Netresearch_Hermes_Model_Parcel::STATUS_NEW_FAILED)->save();
            throw new Netresearch_Hermes_Model_Client_Exception(
                'None of the given parcels could be sent to Hermes'
            );
        }
        try {
            $this->login();
            $this->_setParcelsStatusInTransmission($parcelCollection);
            $response = $this->getSoapClient($this->userToken, $this->partnerToken)->propsImportOrders(
                array(
                    'requestedOrders' => array(
                        'propsOrders' => $parcelData
                    )
                )
            );

        } catch (SoapFault $e) {
            $parcelCollection->setDataToAll('status_code', Netresearch_Hermes_Model_Parcel::STATUS_QUEUED)->save();
            $this->logCommunication('IMPORT ORDERS', 3, true);
            throw Netresearch_Hermes_Model_Client_Exception::soapFault($e);
        }
        $this->logCommunication('IMPORT ORDERS');
        return $response;
    }

    /**
     * convert parcels into Hermes data structure and return that
     *
     * @param array $parcelIds
     * @return array
     */
    public function getConvertedParcelData($parcelIds)
    {
        $parcel = Mage::getModel('hermes/parcel');
        $parcelData = array();
        foreach ($parcelIds as $parcelId) {
            $parcel->load($parcelId);
            if (!$parcel->getId()) {
                /* skip non-persistent parcels */
                continue;
            }
            $parcelData[] = array(
                'orderNo'                      => $parcel->getHermesOrderNo(),
                'receiver'                     => $this->getReceiverData($parcel),
                'clientReferenceNumber'        => $parcel->getShipment()->getIncrementId(),
                'parcelClass'                  => $parcel->getParcelClass(),
                'amountCashOnDeliveryEurocent' => $parcel->getAmountCashOnDeliveryEurocent(),
                'includeCashOnDelivery'        => 1 == $parcel->getIncludeCashOnDelivery(),
                'withBulkGoods'                => false
            );
        }
        return $parcelData;
    }

    /**
     * get parcel receiver data to be sent to Hermes in order to save a new parcel
     *
     * @param Netresearch_Hermes_Model_Parcel $parcel
     * @return array
     */
    protected function getReceiverData($parcel)
    {
        $address = array(
            'firstname'   => $parcel->getReceiverFirstname(),
            'lastname'    => $parcel->getReceiverLastname(),
            'street'      => $parcel->getReceiverStreet(),
            'houseNumber' => $parcel->getReceiverHouseNumber(),
            'postcode'    => $parcel->getReceiverPostcode(),
            'city'        => $parcel->getReceiverCity(),
            'countryCode' => $parcel->getReceiverCountryCode(),
        );
        if (false == is_null($parcel->getReceiverAddressAdd())){
            $address['addressAdd'] = $parcel->getReceiverAddressAdd();
        }
        if (false == is_null($parcel->getReceiverEmail())){
            $address['email'] = $parcel->getReceiverEmail();
        }
        // houseNumber is not set explicitly, use empty string for transmission
        if (is_null($address['houseNumber'])) {
            $address['houseNumber'] = '';
        }
        return $address;
    }

    /**
     * get jpeg label for given parcel
     *
     * @param Netresearch_Hermes_Model_Parcel $parcel
     *
     * @throws Netresearch_Hermes_Model_Client_Exception
     * @return Netresearch_Hermes_Model_Client_Response
     */
    public function getLabelJpeg(Netresearch_Hermes_Model_Parcel $parcel)
    {
        $this->login();
        try {
            $result = $this->getSoapClient($this->userToken, $this->partnerToken)
                ->propsOrderPrintLabelJpeg(array('orderNo' => $parcel->getHermesOrderNo()));
            $this->logCommunication('PRINT JPEG');
//            if (false === $parcel->isClosed()) {
//                $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CLOSED);
//                $parcel->save();
//            }
            return Mage::getModel('hermes/client_response')
                ->setResult($result->propsOrderPrintLabelJpegReturn->jpegData);
        } catch (SoapFault $e) {
            $this->logCommunication('PRINT JPEG', 3, true);
            throw Netresearch_Hermes_Model_Client_Exception::soapFault($e);
        }
    }

    /**
     * get pdf label for given parcel
     *
     * @param Netresearch_Hermes_Model_Parcel $parcel
     * @param int                             $position
     *
     * @throws Netresearch_Hermes_Model_Client_Exception
     * @return Netresearch_Hermes_Model_Client_Response
     */
    public function getLabelPdf(Netresearch_Hermes_Model_Parcel $parcel, $position)
    {
        $this->login();
        try {
            $result = $this->getSoapClient($this->userToken, $this->partnerToken)
                ->propsOrderPrintLabelPdf(array(
                    'orderNo'       => $parcel->getHermesOrderNo(),
                    'printPosition' => $position
                ));
            $this->logCommunication('PRINT SINGLE PDF');
//            if (false === $parcel->isClosed()) {
//                $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CLOSED);
//                $parcel->save();
//            }
            return Mage::getModel('hermes/client_response')
                ->setResult($result->propsOrderPrintLabelPdfReturn->pdfData);

        } catch (SoapFault $e) {
            $this->logCommunication('PRINT SINGLE PDF', 3, true);
            throw Netresearch_Hermes_Model_Client_Exception::soapFault($e);
        }
    }

    /**
     * get pdf labels for given parcel
     *
     * @param string $hermesParcelId
     * @return Netresearch_Hermes_Model_Client_Response
     */
    public function getLabelsPdf($hermesParcelId)
    {
        $this->login();
        try {
            $result = $this->getSoapClient($this->userToken, $this->partnerToken)
                ->propsOrdersPrintLabelsPdf(array('orderNo' => $hermesParcelId));
            $this->logCommunication('PRINT SINGLE PDF');
            $failedItems  = array();
            $successItems = array();
            $exceptions   = array();
            foreach ($result->propsOrdersPrintLabelsPdfResponse->propsOrdersPrintLabelsPdfReturn->orderRes as $orderRes) {
                if (property_exists($orderRes, 'exceptionItems') && count($orderRes->exceptionItems)) {
                    $failedItems[] = $orderRes->orderNo;
                    $exceptions[$orderRes->orderNo] = $orderRes->exceptionItems;
                } else {
                    $successItems[] = $orderRes->orderNo;
                }
            }
            return Mage::getModel('hermes/client_response')
                ->setResult($result->propsOrdersPrintLabelsPdfResponse->propsOrdersPrintLabelsPdfReturn->pdfData)
                ->setSuccessCount(count($successItems))
                ->setSuccessItems($successItems)
                ->setErrorCount(count($failedItems))
                ->setFailedItems($failedItems)
                ->setExceptions($exceptions);
        } catch (SoapFault $e) {
            $this->logCommunication('PRINT SINGLE PDF', 3, true);
            throw Netresearch_Hermes_Model_Client_Exception::soapFault($e);
        }
    }

    /**
     * cancel parcel
     *
     * @param Netresearch_Hermes_Model_Parcel $parcel
     *
     * @throws Netresearch_Hermes_Model_Client_Exception
     * @return boolean Cancellation success
     */
    public function cancel(Netresearch_Hermes_Model_Parcel $parcel)
    {
        $this->login();
        try {
            $result = $this->getSoapClient($this->userToken, $this->partnerToken)
                ->propsOrderDelete(array('orderNo' => $parcel->getHermesOrderNo()));
            $this->logCommunication('CANCEL');
            return $result->propsOrderDeleteReturn;
        } catch (SoapFault $e) {
            $this->logCommunication('CANCEL', 3, true);
            throw Netresearch_Hermes_Model_Client_Exception::soapFault($e);
        }
    }
}
