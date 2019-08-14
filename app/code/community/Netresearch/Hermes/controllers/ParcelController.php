<?php

class Netresearch_Hermes_ParcelController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('sales/shipment/transmit_parcels');
    }

    protected function _checkSectionAllowed($section)
    {
        if (false == Mage::getSingleton('admin/session')->isAllowed('sales/shipment/' . $section)) {
            return $this->_redirect('denied');
        }
    }

    public function editAction()
    {
        return $this->loadLayout()->renderLayout();
    }

    public function transmitHermesParcelsAction()
    {
        $this->_checkSectionAllowed('transmit_parcels');

        if (true === Mage::getModel('hermes/config')->isEnabled()) {
            try {
                $result = Mage::getModel('hermes/observer')->transmitParcels(new Varien_Event_Observer());
                if (0 < $result['errors']) {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('hermes')->__(
                        'Tried to transfer %d parcels to Hermes, but %d of them raised an error.',
                        $result['parcels'],
                        $result['errors']
                    ));
                } else if(0 < $result['parcels']) {
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('hermes')->__(
                        'Successfully transfered %d parcels to Hermes.',
                        $result['parcels']
                    ));
                } else {
                   Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('hermes')->__(
                        'No parcels were transfered to Hermes.',
                        $result['parcels']
                    ));
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        } else {
            $message = Mage::helper('hermes')->__('Hermes is disabled in configuration. Please enable it before trying to submit parcels.');
            Mage::getSingleton('adminhtml/session')->addError($message);
        }
        $this->_redirectReferer();
    }

    public function saveAction()
    {
        $post = $this->getRequest()->getPost('receiver');
        $id = $this->getRequest()->getPost('parcel_id');
        $resumeParcel = (bool)$this->getRequest()->getPost('save_and_resume');

        try {
            $parcel = Mage::getModel('hermes/parcel')->load($id);

            /* @var $parcel Netresearch_Hermes_Model_Parcel */
            if ($resumeParcel && $parcel->canBeResumed()) {
                $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_QUEUED);
            }

            if (Mage::getModel('hermes/config')->isTieredPriceMerchant()) {
                $parcel->setParcelClass($this->getRequest()->getPost('parcel_class'));
            }
            $parcel
                ->setReceiverFirstname($post['firstname'])
                ->setReceiverLastname($post['lastname'])
                ->setReceiverStreet($post['street'])
                ->setReceiverHouseNumber($post['house_number'])
                ->setReceiverAddressAdd($post['address_add'])
                ->setReceiverPostcode($post['postcode'])
                ->setReceiverCity($post['city'])
                ->setReceiverDistrict(isset($post['district']) ? $post['district'] : null)
                ->setReceiverEmail($post['email'])
                ->setReceiverTelephoneNumber($post['telephone_number'])
                ->setReceiverTelephonePrefix($post['telephone_prefix'])
                ->save();

            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('hermes')->__('Receiver data successfully updated.')
            );
            if ($resumeParcel) {
                $this->addResumeMessages();
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('hermes')->__('Receiver data could not be updated.')
            );
        }

        return $this->_redirect('adminhtml/sales_shipment/view', array(
            'shipment_id' => $parcel->getShipmentId()
        ));
    }

    /**
     * add admin messages to inform user about delayed transmission
     *
     * @return void
     */
    protected function addResumeMessages()
    {
        $session = Mage::getSingleton('adminhtml/session');
        $session->addSuccess(
            Mage::helper('hermes')->__('Parcel transmission was resumed')
        );
        $session->addWarning(
            Mage::helper('hermes')->__(
                'Shipment will be transmitted to Hermes within a short time. If you are in a hurry, you could <a href="%s">trigger prompt transmission</a>.',
                Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/parcel/transmitHermesParcels')
            )
        );
    }

    public function getLabelAction()
    {
        $parcelId = $this->getRequest()->getParam('parcelId');
        $format = strtolower(trim($this->getRequest()->getParam('format')));
        $labelPosition = $this->getRequest()->getParam('labelPosition');
        if (!in_array($format, Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelFileFormatOptions::getLabelFileFormats())) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('hermes')->__('format is not supported'));
            $this->_redirectReferer();
        }
        $contentType = '';
        if ($format == Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelFileFormatOptions::PDF) {
            $contentType = 'application/pdf';
        }
        if ($format == Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelFileFormatOptions::JPEG) {
            $contentType = 'image/jpeg';
        }
        $parcel = $this->getParcel($parcelId);
        try {
            $this->getResponse()->setBody($parcel->getLabel($format, $labelPosition));
            $this->getResponse()->setHeader('Content-type', $contentType, true);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('hermes')->__($e->getMessage()));
            $this->_redirectReferer();
        }
        return $this->getResponse();
    }

    public function repeatTransmissionAction()
    {
        $parcelId = $this->getRequest()->getParam('parcelId');
        $parcel = $this->getParcel($parcelId);
        $parcel->repeatTransmission()->save();

        $this->addResumeMessages();

        $this->_redirect('adminhtml/sales_shipment/view/shipment_id/' . $parcel->getShipmentId());
    }

    public function cancelAction()
    {
        $parcelId = $this->getRequest()->getParam('parcelId');
        $parcel = $this->getParcel($parcelId);
        try {
            $parcel->cancel();
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('hermes')->__('Parcel cancellation succeeded'));
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('hermes')->__($e->getMessage()));
        }
        $this->_redirect('adminhtml/sales_shipment/view/shipment_id/' . $parcel->getShipmentId());
    }

    /**
     *
     * @param int $parcelId the id of the parcel
     * @return Netresearch_Hermes_Model_Parcel the parcel
     */
    protected function getParcel($parcelId)
    {
        if (false == is_numeric($parcelId)) {
            $this->_redirect('noroute');
        }
        $parcel = Mage::getModel('hermes/parcel');
        $parcel->load($parcelId);
        if (!$parcel->getId()) {
            $this->_redirect('noroute');
        }
        return $parcel;
    }

    /**
     * cancel Hermes shipments
     */
    public function massCancelAction()
    {
        /* collect parcels */
        $shipmentIds = $this->getRequest()->getParam('shipment_ids');
        if (!is_array($shipmentIds) || 0 == count($shipmentIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('hermes')->__('Please select at least one shipment')
            );
            return $this->_redirect('adminhtml/sales_shipment/index');
        }
        $parcels = Mage::getModel('hermes/parcel')->getCollection()
            ->addFieldToFilter('shipment_id', array('in' => $shipmentIds));
        $successCount = 0;

        /* cancel parcels */
        foreach ($parcels as $parcel) {
            try {
                $parcel->cancel();
                $successCount++;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('hermes')->__('Failed to cancel Hermes shipment %s', $parcel->getShipment()->getIncrementId())
                    . ': ' .
                    Mage::helper('hermes')->__($e->getMessage())
                );
            }
        }
        if (0 < $successCount) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('hermes')->__('Canceled %s Hermes shipments', $successCount)
            );
        }

        /* calculate error count based on the requested count of shipments */
        $errorCount = count($shipmentIds) - $successCount;
        if (0 < $errorCount) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('hermes')->__('Failed to cancel %s Hermes shipments', $errorCount)
            );
        }
        $this->_redirect('adminhtml/sales_shipment/index');
    }
}
