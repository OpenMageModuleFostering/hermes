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
 * Netresearch_Hermes_Model_Parcel
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Netresearch_Hermes_Model_Parcel extends Mage_Core_Model_Abstract
{
    /** @var integer Parcel in queue. The code means marked as new. */
    const STATUS_QUEUED          =  1;

    /** @var integer Parcel successfully created */
    const STATUS_PROCESSED       =  2;

    /** @var integer Parcel in queue to be canceled */
    const STATUS_CANCEL_QUEUED   =  3;

    /** @var integer Parcel canceled */
    const STATUS_CANCELED        =  4;

    /** @var integer Parcel creation failed on Hermes web service */
    const STATUS_NEW_FAILED      = -2;

    /** @var integer Parcel cancellation failed */
    const STATUS_CANCEL_FAILED   = -4;

    /** @var integer Parcel transmission is done and can't be changed anymore (because label was printed) */
    const STATUS_CLOSED = 5;

    /** @var integer Parcel currenctly transmitted */
    const STATUS_IN_TRANSMISSION = 20;

    protected $shipment;

    /**
     * Constructor
     *
     * @see    lib/Varien/Varien_Object#_construct()
     * @return Netresearch_Hermes_Model_Parcel
     */
    protected function _construct()
    {
        $this->_init('hermes/parcel');
    }

    /**
     * get associated shipment
     *
     * @return Mage_Sales_Model_Shipment
     */
    public function getShipment()
    {   


        if (is_null($this->shipment) || $this->shipment->getId() != $this->getShipmentId()) {
            $this->setShipment(Mage::getModel('sales/order_shipment')->load($this->getShipmentId()));
        }
        return $this->shipment;
    }

    /**
     * set shipment this parcel is based on
     *
     * @throw Netresearch_Hermes_Model_Client_Exception if transformation is not possible due to some reason
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @return Netresearch_Hermes_Model_Parcel
     */
    public function setShipment(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $this->shipment = $shipment;
        $this->setShipmentId($shipment->getId());
        $this->setShipmentIncrementId($shipment->getIncrementId());
        $shippingAddress = $shipment->getShippingAddress();
        $this->convertReceiverLastname()
            ->convertReceiverStreet()
            ->convertDistrict()
            ->convertCountryCode()
            ->convertEmail()
            ->setReceiverCity($shippingAddress->getCity())
            ->setReceiverFirstname($shippingAddress->getFirstname())
            ->setReceiverTelephoneNumber($shippingAddress->getTelephone())
            ->setReceiverTelephonePrefix()
            ->setReceiverPostcode($shippingAddress->getPostcode())
            ->setClientReferenceNumber($shipment->getIncrementId())
            ->convertCompany($shipment->getCompany());
        $this->validate();

        return $this;
    }

    /**
     * set concatenation of middlename and lastname as receiver lastname
     *
     * @return Netresearch_Hermes_Model_Parcel
     */
    public function convertReceiverLastname()
    {
        $this->setReceiverLastname(trim($this->getShipment()->getShippingAddress()->getMiddlename()
            . ' ' . $this->getShipment()->getShippingAddress()->getLastname()
        ));
        return $this;
    }

    /**
     * set shipping address street (including house number) and address_add
     *
     * @return Netresearch_Hermes_Model_Parcel
     */
    public function convertReceiverStreet()
    {
        $this->setReceiverStreet(
            $this->getShipment()->getShippingAddress()->getStreet1()
        );

        /**
         * Magento returns street lines as follows:
         * -1        = concatenated by \n
         * 0 or null = as array
         * > 0       = single line with given number
         */
        $street = $this->getShipment()->getShippingAddress()->getStreet(0);
        /* strip street itself */
        unset($street[0]);
        $addressAdd = trim(implode(' ', $street));
        $this->setReceiverAddressAdd(strlen($addressAdd) ? $addressAdd : null);

        return $this;
    }

    /**
     * set district for parcels to Ireland
     *
     * @return Netresearch_Hermes_Model_Parcel
     */
    public function convertDistrict()
    {
        $address = $this->getShipment()->getShippingAddress();
        if ('IE' == $address->getCountryId()) {
            $this->setReceiverDistrict($address->getRegion());
        }
        return $this;
    }

    /**
     * set 3-letter country code
     *
     * @return Netresearch_Hermes_Model_Parcel
     */
    public function convertCountryCode()
    {
        $countryId = $this->getShipment()->getShippingAddress()->getCountryId();
        $this->setReceiverCountryCode(
            Mage::helper('hermes')->getIso3CodeByIso2Code($countryId)
        );
        return $this;
    }

    /**
     * if hermes should send a mail containing tracking information, we have to submit customer's email
     *
     * @return Netresearch_Hermes_Model_Parcel
     */
    public function convertEmail()
    {
        if (Mage::getModel('hermes/config')->isHermesMailEnabled()) {
            $this->setReceiverEmail(
                $this->getShipment()->getOrder()->getCustomerEmail()
            );
        }
        return $this;
    }

    public function convertCompany()
    {
        $company = $this->getShipment()->getShippingAddress()->getCompany();
        if (strlen($company)) {
            if (0 == strlen(trim($this->getReceiverAddressAdd()))) {
                $this->setReceiverAddressAdd(
                    trim($this->getReceiverFirstname() . ' ' . $this->getReceiverLastname())
                );
            }
            $this->setReceiverFirstname(null);
            $this->setReceiverLastname($company);
        }
    }

    /**
     * validate according to Hermes API requirements
     *
     * @return void
     */
    public function validate()
    {
        if (25 < strlen(trim($this->getReceiverLastname()))) {
            throw new Netresearch_Hermes_Model_Client_Exception('Field receiver_lastname must not be longer than 25 characters');
        }
    }

    /**
     * parcel transmission is queued
     *
     * @return boolean
     */
    public function isQueued()
    {
        return self::STATUS_QUEUED == $this->getStatusCode();
    }

    /**
     * parcel transmission is processed
     *
     * @return boolean
     */
    public function isProcessed()
    {
        return self::STATUS_PROCESSED == $this->getStatusCode();
    }

    /**
     * parcel is queued to be canceled
     *
     * @return boolean
     */
    public function isQueuedToCancel()
    {
        return self::STATUS_CANCEL_QUEUED == $this->getStatusCode();
    }

    /**
     * parcel is queued to be canceled
     *
     * @return boolean
     */
    public function isCanceled()
    {
        return self::STATUS_CANCELED == $this->getStatusCode();
    }

    /**
     * Returns all statuses an there translated labels
     *
     * @return array
     */
    public function getStatusCodes()
    {
        return array(
            self::STATUS_QUEUED          => Mage::helper('hermes')->__('new (queued)'),
            self::STATUS_NEW_FAILED      => Mage::helper('hermes')->__('new (failed)'),
            self::STATUS_PROCESSED       => Mage::helper('hermes')->__('processed'),
            self::STATUS_CANCEL_QUEUED   => Mage::helper('hermes')->__('cancel (queued)'),
            self::STATUS_CANCEL_FAILED   => Mage::helper('hermes')->__('cancel (failed)'),
            self::STATUS_CANCELED        => Mage::helper('hermes')->__('canceled'),
            self::STATUS_IN_TRANSMISSION => Mage::helper('hermes')->__('in transmission'),
            self::STATUS_CLOSED          => Mage::helper('hermes')->__('closed'),
        );
    }

    /**
     * Returns status caption.
     *
     * @return string
     */
    public function getStatusText()
    {
        $statuses = $this->getStatusCodes();
        if (!array_key_exists($this->getStatusCode(), $statuses)) {
            return $this->getStatusCode();
        }
        return $statuses[$this->getStatusCode()];
    }

    /**
     * parcel transmission is failed
     *
     * @return boolean
     */
    public function isFailed()
    {
        return self::STATUS_NEW_FAILED == $this->getStatusCode();
    }

    /**
     * parcel transmission is failed
     *
     * @return boolean
     */
    public function isCancelFailed()
    {
        return self::STATUS_CANCEL_FAILED == $this->getStatusCode();
    }

    /**
     * if parcel transmission can be resumed or repeated
     *
     * @return boolean
     */
    public function canBeResumed()
    {
        return in_array(
            $this->getStatusCode(),
            array(
                self::STATUS_PROCESSED,
                self::STATUS_CANCEL_QUEUED,
                self::STATUS_CANCELED,
                self::STATUS_NEW_FAILED
            )
        );
    }

    /**
     * repeat parcel transmission
     *
     * @return Netresearch_Hermes_Model_Parcel
     */
    public function repeatTransmission()
    {
        if (!in_array($this->getStatusCode(), array(self::STATUS_CLOSED, self::STATUS_IN_TRANSMISSION))) {
            $this->setStatusCode(self::STATUS_QUEUED);
        }
        return $this;
    }

    /**
     * parcel in transmission
     *
     * @return boolean
     */
    public function isInTransmission()
    {
        return self::STATUS_IN_TRANSMISSION == $this->getStatusCode();
    }

    /**
     * is parcel is closed (when label was printed
     *
     * @return boolean
     */
    public function isClosed()
    {
        return self::STATUS_CLOSED == $this->getStatusCode();
    }

    public function setErrorCodes($codes)
    {
        $this->setErrorCode(json_encode($codes));
        return $this;
    }

    public function getErrorCodes()
    {
        return json_decode($this->getErrorCode());
    }

    public function setErrorMessages($messages)
    {
        $this->setErrorMessage(json_encode($messages));
        return $this;
    }

    public function getErrorMessages()
    {
        return json_decode($this->getErrorMessage());
    }

    public function getTrackingUrl()
    {
        return Mage::getModel('hermes/config')->getTrackingUrl($this);
    }

    /**
     * gets the label of parcel and sets the state to closed (if neccessary)
     *
     * @param type $format the fileformat of the label
     * @return string the label
     * @throws Exception
     */
    public function getLabel($format, $labelPosition = null)
    {
        $label = null;
        $format = strtolower(trim($format));
        try {
            switch ($format) {
                case Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelFileFormatOptions::PDF: {
                    if (is_null($labelPosition)) {
                        $labelPosition = Mage::getModel('hermes/config')->getPdfLabelPosition();
                    }
                    $label = Mage::getModel('hermes/client')->getLabelPdf($this, $labelPosition)->getResult();
                    break;
                }
                case Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelFileFormatOptions::JPEG: {
                    $label = Mage::getModel('hermes/client')->getLabelJpeg($this)->getResult();
                    break;
                }
            }
            if (!is_null($label) && !$this->isClosed()) {
                $this->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CLOSED);
                $this->save();
            }
        }
        catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
        return $label;
    }

    /**
     * add track (to provide tracking url)
     *
     * @return Netresearch_Hermes_Model_Parcel
     */
    public function addTrack()
    {
        if (0 < strlen($this->getHermesOrderNo())) {
            $shipment = $this->getShipment();
            $recentTrack = $shipment->getTracksCollection()->getLastItem();
            if (!$recentTrack || $recentTrack->getNumber() != $this->getHermesOrderNo()) {
                $carrier  = Mage::getModel('hermes/shipping_carrier_hermes');
                $track    = Mage::getModel('sales/order_shipment_track')
                    ->setNumber($this->getHermesOrderNo())
                    ->setCarrierCode(Netresearch_Hermes_Model_Shipping_Carrier_Hermes::CODE)
                    ->setTitle($carrier->getConfigData('title'));
                $shipment->addTrack($track)
                    ->save();
                $this->notifyCustomer();
            }
        }
        return $this;
    }

    /**
     *
     * removes tracks
     *
     * @return \Netresearch_Hermes_Model_Parcel
     */
    public function removeTrack()
    {
        if (0 < strlen($this->getHermesOrderNo())) {
            $tracks = Mage::getModel('sales/order_shipment_track')
                ->getCollection()
                ->addFieldToFilter('carrier_code', Netresearch_Hermes_Model_Shipping_Carrier_Hermes::CODE)
                ->addFieldToFilter('order_id', $this->getShipment()->getOrderId())
                ->load();
            foreach ($tracks as $track) {
                if ($track->getNumber() == $this->getHermesOrderNo()) {
                    $track->delete();
                }
            }
        }
        return $this;
    }

    /**
     * send tracking information to the customer
     *
     * @return Netresearch_Hermes_Model_Parcel
     */
    public function notifyCustomer()
    {
        if (Mage::getModel('hermes/config')->isTrackingLinkMailEnabled()) {
            $this->getShipment()
                ->sendEmail(true)
                ->setEmailSent(true)
                ->save();
        }
        return $this;
    }

    public function cancel()
    {
        $cancellationComment = 'HERMES::' .  Mage::helper('hermes')->__('Hermes parcel cancellation succeeded');

        if (is_null($this->getHermesOrderNo())) {
            /* simply set parcel to state canceled, if it was not transmitted to Hermes, yet */

            $this->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CANCELED);
            $this->save();

            /* add shipment comment */
            $this->getShipment()->addComment($cancellationComment);
            $this->getShipment()->getCommentsCollection()->save();
        } else {
            /* send cancellation request to Hermes */

            $this->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CANCEL_QUEUED);
            if (Mage::getModel('hermes/client')->cancel($this)) {
                $cancellationComment .= ' [' . $this->getHermesOrderNo() . ']';
                $this->removeTrack();
                $this->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_CANCELED);
                $this->setHermesOrderNo(null);
                $this->save();

                /* add shipment comment */
                $this->getShipment()->addComment($cancellationComment);
                $this->getShipment()->getCommentsCollection()->save();
            } else {
                throw new Exception('Parcel cancellation failed');
            }
        }
    }
}
