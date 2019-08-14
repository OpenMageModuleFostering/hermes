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
 * Hermes API client exception
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Hermes_Model_Client_Exception extends Mage_Core_Exception
{
    public static function soapFault(SoapFault $e)
    {
        if (isset($e->detail)
            && isset($e->detail->ServiceException)
            && isset($e->detail->ServiceException->exceptionItems)
            && isset($e->detail->ServiceException->exceptionItems->ExceptionItem)
            && isset($e->detail->ServiceException->exceptionItems->ExceptionItem->errorCode)
            && isset($e->detail->ServiceException->exceptionItems->ExceptionItem->errorMessage)
        ) {
            return new self(
                $e->detail->ServiceException->exceptionItems->ExceptionItem->errorMessage,
                $e->detail->ServiceException->exceptionItems->ExceptionItem->errorCode
            );
        }
        return new self('Unexpected SoapFault (maybe an empty response?)');
    }
    
    public static function createSoapFault($errorMessage, $errorCode)
    {
        return new NetresearchSoapFault($errorMessage, $errorCode);
    }
}

class NetresearchSoapFault extends SoapFault
{
    public $detail;
    
    public function __construct($errorMessage, $errorCode, $faultcode = 'dummycode', $faultstring = 'dummystring')
    {
        parent::__construct($faultcode, $faultstring);
        $this->detail = new stdClass();
        $this->detail->ServiceException = new stdClass();
        $this->detail->ServiceException->exceptionItems = new stdClass();
        $this->detail->ServiceException->exceptionItems->ExceptionItem = new stdClass();
        $this->detail->ServiceException->exceptionItems->ExceptionItem->errorMessage = $errorMessage;
        $this->detail->ServiceException->exceptionItems->ExceptionItem->errorCode = $errorCode;
    }
}
