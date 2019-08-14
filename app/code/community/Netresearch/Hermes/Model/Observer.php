<?php

class Netresearch_Hermes_Model_Observer
{
    /**
     * @var Netresearch_Hermes_Model_Config
     */
    protected $config;

    public function __construct()
    {
        $this->config = Mage::getModel('hermes/config');
    }

    /**
     * Perform automatic mode
     *
     * @param Varien_Event_Observer $observer
     *
     * @return null
     */
    protected function saveHermesShipmentDataAutoMode($observer)
    {
        return null;
    }

    /**
     * Perform manual mode
     *
     * @param Varien_Event_Observer $observer
     * @param array $data request data
     *
     * @return Netresearch_Hermes_Model_Parcel The parcel object or false if hermes shipping is not applicable
     */
    protected function saveHermesShipmentDataManualMode($observer, array $data)
    {
        if (false === array_key_exists('ship_with_hermes', $data) || '0' === $data['ship_with_hermes']) {
            return false;
        }

        $shipment = $observer->getShipment();
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $order = $shipment->getOrder();

        $parcelClass = null;
        if (isset($data['parcel_class']) &&
            in_array($data['parcel_class'], $this->config->getAllProductClasses())
        ) {
            $parcelClass = $data['parcel_class'];
        }

        /** @var Netresearch_Hermes_Model_Parcel $parcel */
        $parcel = Mage::getModel('hermes/parcel')->load($shipment->getId(), 'shipment_id');

        if (!$parcel || !$parcel->getId()) {
            return Mage::helper('hermes/order')->createParcel($shipment, $parcelClass);
        }
    }

    /**
     * Update list of products as scheduled in cron
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Netresearch_Hermes_Model_Client
     */
    public function updateListOfProducts($observer)
    {
        $client = Mage::getModel('hermes/client');
        /* @var $client Netresearch_Hermes_Model_Client */

        return $client->updateListOfProducts();
    }

    /**
     * Read data stored with shipment and persist hermes specific data
     *
     * @event sales_order_shipment_save_after
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Netresearch_Hermes_Model_Parcel if save is successful, false otherwise
     */
    public function saveHermesShipmentData($observer)
    {
        if (!$this->config->isEnabled()) {
            return false;
        }

        if (!$this->config->isAllowedShippingMethod($observer->getShipment()->getOrder()->getShippingMethod())) {
            return false;
        }

        $data = Mage::app()->getRequest()->getPost();
        if (empty($data)) {
            return $this->saveHermesShipmentDataAutoMode($observer);
        } else {
            return $this->saveHermesShipmentDataManualMode($observer, $data);
        }
    }

    /**
     * get ids of parcels to be submitted
     *
     * @return array
     */
    public function getParcelIdsToSubmit()
    {
        return array_merge(
            Mage::getModel('hermes/parcel')->getCollection()
                ->addFieldToFilter('status_code', 0)
                ->getAllIds(),
            Mage::getModel('hermes/parcel')->getCollection()
                ->addFieldToFilter('status_code', Netresearch_Hermes_Model_Parcel::STATUS_QUEUED)
                ->getAllIds(),
            Mage::getModel('hermes/parcel')->getCollection()
                ->addFieldToFilter('status_code', array('null' => true))
                ->getAllIds()
        );
    }

    /**
     * transmit parcels to Hermes
     *
     * @param Varien_Event_Observer $observer
     *
     * @return array Result of parcel transmission
     * @throws Netresearch_Hermes_Model_Client_Exception
     */
    public function transmitParcels($observer)
    {
        $parcelIds = $this->getParcelIdsToSubmit();
        $chunkedParcelIds = array_chunk($parcelIds, Netresearch_Hermes_Model_Client::IMPORT_ORDERS_MAX_COUNT);
        $errorCount = 0;

        foreach ($chunkedParcelIds as $currentParcelIds) {
            $results = Mage::getModel('hermes/client')->sendParcels($currentParcelIds);

            if (!$results
                || !$results->propsImportOrdersReturn
                || !$results->propsImportOrdersReturn->orderResponses
                || !$results->propsImportOrdersReturn->orderResponses->OrderResponse
                || count($currentParcelIds) != count($results->propsImportOrdersReturn->orderResponses->OrderResponse)
            ) {
                throw new Netresearch_Hermes_Model_Client_Exception('Got invalid response from Hermes web service');
            }
            $results = $results->propsImportOrdersReturn->orderResponses->OrderResponse;
            foreach ($currentParcelIds as $offset => $parcelId) {
                /** @var Netresearch_Hermes_Model_Parcel $parcel */
                $parcel = Mage::getModel('hermes/parcel')->load($parcelId);
                $result = $results[$offset];
                $parcel->setHermesOrderNo($result->orderNo);
                $parcel->setErrorCode(null);
                $parcel->setErrorMessage(null);
                $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_PROCESSED);
                $comment = Mage::helper('hermes')->__('Successfully transmitted to Hermes') . ' [' . $result->orderNo . ']';
                if (property_exists($result, 'exceptionItems')
                    && is_object($result->exceptionItems)
                    && property_exists($result->exceptionItems, 'ExceptionItem')
                ) {
                    $errorCodes = array();
                    $errorMessages = array();
                    foreach ($result->exceptionItems->ExceptionItem as $exception) {
                        $errorCodes[] = $exception->errorCode;
                        $errorMessages[] = $exception->errorMessage;
                    }
                    if (count($errorCodes) || count($errorMessages)) {
                        $parcel->setErrorCodes($errorCodes);
                        $parcel->setErrorMessages($errorMessages);
                        $parcel->setStatusCode(Netresearch_Hermes_Model_Parcel::STATUS_NEW_FAILED);
                        $errorCount++;
                        $comment = Mage::helper('hermes')->__(
                            1 == count($errorMessages)
                                ? 'An error occured during transmission to Hermes' : 'Some errors occured during transmission to Hermes'
                        );
                        $comment = '<span class="error">' . $comment . ':</span><br />' . implode('<br />', $errorMessages);
                    }
                }
                $parcel->save();

                $parcel->addTrack();

                $parcel->getShipment()->addComment('HERMES::' . $comment);
                $parcel->getShipment()->getCommentsCollection()->save();
            }
        }
        return array('parcels' => count($parcelIds), 'errors' => $errorCount);
    }

    /**
     * fetching orders and creating shipments and hermes parcels
     *
     * @return array
     */
    public function createParcelsForHermes($observer)
    {
        $result = array();
        if (Mage::getModel('hermes/config')->isAutocreateEnabled()) {
            $orderHelper = Mage::helper('hermes/order');
            $orders = $orderHelper->getOrderCollection();
            $result = $orderHelper->shipOrders($orders);
        }
        return $result;
    }

    /**
     * Throws exception if enterted data was not valid
     *
     * @event sales_order_shipment_save_before
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkShipment($observer)
    {
        $data = Mage::app()->getRequest()->getPost();
        $shipment = $observer->getShipment();
        //Check if shipping method is disabled
        if (array_key_exists('ship_with_hermes', $data) && $data['ship_with_hermes'] == 1) {
            if (false === Mage::getModel('hermes/config')->isAllowedShippingMethod(
                    $shipment->getOrder()->getShippingMethod())
            ) {
                return;
            }
            // Return false if payment method is COD and partial shipment
            if (true === Mage::getModel('hermes/config')->isEnabled() &&
                true === Mage::helper('hermes/order')->isPartialShipment($shipment)
            ) {
                $this->_setValidationFailure(Mage::helper('hermes')->__(
                    'Partial shipment is not allowed for cash on delivery shipments which should be shipped with Hermes.'));
            }
        }
    }

    /**
     *
     *
     * @param string $errorMessage
     */
    protected function _setValidationFailure($errorMessage)
    {
        $this->_validationFails = true;
        /* @var $messageCollection Mage_Core_Model_Message_Collection */
        $messageCollection = Mage::getSingleton('adminhtml/session')
            ->getMessages();
        $messages = $messageCollection->getItemsByType(Mage_Core_Model_Message::SUCCESS);
        /* @var $message Mage_Core_Model_Message_Abstract */
        foreach ($messages as $message) {
            $messageCollection->deleteMessageByIdentifier($message->getIdentifier());
        }
        Mage::throwException($errorMessage);
    }

    /**
     * Adds the Hermes parcel form to shipment creation
     *
     * @event core_block_abstract_to_html_after
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Netresearch_Hermes_Model_Observer
     */
    public function addShipmentCreationForm(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Block_Sales_Order_Shipment_Create_Tracking $block */
        $block = $observer->getBlock();
        /** @var Varien_Object $transport */
        $transport = $observer->getTransport();

        if ($this->config->isEnabled()
            && $block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_Create_Tracking
        ) {
            /** @var Netresearch_Hermes_Block_Adminhtml_Sales_Order_Shipment_Create_Hermes $hermesForm */
            $hermesForm = $block->getLayout()->createBlock(
                'hermes/adminhtml_sales_order_shipment_create_hermes',
                'hermes_form', array('template' => 'hermes/sales/order/shipment/create/hermes.phtml'));
            $formHtml = $hermesForm->renderView();

            $html = $transport->getHtml() . $formHtml;
            $transport->setHtml($html);
        }

        return $this;
    }

    /**
     * Adds the Hermes parcel form to shipment view
     *
     * @event core_block_abstract_to_html_after
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Netresearch_Hermes_Model_Observer
     */
    public function addShipmentViewInfo(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Block_Sales_Order_Shipment_View_Tracking $block */
        $block = $observer->getBlock();
        /** @var Varien_Object $transport */
        $transport = $observer->getTransport();

        if ($this->config->isEnabled()
            && $block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View_Tracking
        ) {
            /** @var Netresearch_Hermes_Block_Adminhtml_Sales_Order_Shipment_Create_Hermes $hermesForm */
            $hermesForm = $block->getLayout()->createBlock(
                'hermes/adminhtml_sales_order_shipment_create_hermes',
                'hermes_view', array('template' => 'hermes/sales/order/shipment/view/hermes.phtml'));
            $formHtml = $hermesForm->renderView();

            $html = $transport->getHtml() . $formHtml;
            $transport->setHtml($html);
        }

        return $this;
    }

    /**
     * Adds the Hermes mass action to the sales order grid
     *
     * @event adminhtml_block_html_before
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Netresearch_Hermes_Model_Observer
     */
    public function addOrderGridMassActionAndIcon(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Block_Sales_Order_Grid $block */
        $block = $observer->getBlock();
        if ($this->config->isEnabled()
            && $block instanceof Mage_Adminhtml_Block_Sales_Order_Grid
            && Mage::getSingleton('admin/session')->isAllowed('sales/shipment/create_hermes_shipments')
        ) {
            /* provide parcel class selection for tier price merchants */
            if ($this->config->isTieredPriceMerchant()) {
                $parcelClasses = $this->config->getAllProductClassesasKeyValue();
                array_unshift($parcelClasses, array('value' => '', 'label' => Mage::helper('hermes')->__('use default')));
                $additionalOptions = array(
                    'parcelClass' => array(
                        'name' => 'parcelClass',
                        'type' => 'select',
                        'label' => Mage::helper('hermes')->__('Parcel class'),
                        'values' => $parcelClasses
                    )
                );
            } else {
                $additionalOptions = array();
            }
            /* add checkbox to notify customer */
            $additionalOptions['notifyCustomer'] = array(
                'name' => 'notifyCustomer',
                'type' => 'checkbox',
                'label' => Mage::helper('hermes')->__('Notify Customer by Email'),
            );

            $block->getMassactionBlock()->addItem('create_hermes_shipments', array(
                'label' => Mage::helper('hermes')->__('Create shipment(s) for Hermes'),
                'url' => $block->getUrl('adminhtml/shipment/createShipments'),
                'additional' => $additionalOptions
            ));

            /* Add frame callback to column, so we can add our icon html rather unobstrusive */
            $column = $block->getColumn('real_order_id');
            $column->setFrameCallback(array($this, 'renderHermesIcon'));
            $block->addColumn('real_order_id', $column->toArray());
        }

        return $this;
    }

    /**
     * Frame callback added to the real_order_id column in the sales_order_grid
     *
     * @param string $renderedValue
     * @param Mage_Sales_Model_Order $row
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param boolean $isExport
     *
     * @return string
     */
    public function renderHermesIcon($renderedValue, $row, $column, $isExport)
    {
        if ($isExport === false
            && $column->getId() === 'real_order_id'
        ) {
            /** @var Netresearch_Hermes_Block_Adminhtml_Sales_Order_Grid_Renderer_Icon $renderer */
            $renderer = Mage::getBlockSingleton('hermes/adminhtml_sales_order_grid_renderer_icon');
            $renderedValue = $renderedValue . $renderer->getHermesStatusOutput($row);
        }
        return $renderedValue;
    }

    /**
     * Adds the Hermes mass action to the sales shipment grid
     *
     * @event adminhtml_block_html_before
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Netresearch_Hermes_Model_Observer $this
     */
    public function addShipmentGridMassActionAndColumns(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Block_Sales_Shipment_Grid $block */
        $block = $observer->getBlock();
        if ($this->config->isEnabled()
            && $block instanceof Mage_Adminhtml_Block_Sales_Shipment_Grid
            && Mage::getSingleton('admin/session')->isAllowed('sales/shipment/create_hermes_shipments')
        ) {
            $block->getMassactionBlock()->addItem('cancelHermesParcels', array(
                    'label' => Mage::helper('hermes')->__('Cancel Hermes shipments'),
                    'url' => $block->getUrl('adminhtml/parcel/massCancel'),
                )
            );

            $logo = '<img src="' . $block->getSkinUrl('images/hermes/logo_small.png') .
                '" alt="Hermes" title="Hermes" height="12" align="top" id="hermes_logo_small" />';

            $block->addColumn('status_code', array(
                'header'    => $logo . Mage::helper('hermes')->__('Hermes Status'),
                'index'     => 'status_code',
                'type'      => 'options',
                'options'   => Mage::getSingleton('hermes/parcel')->getStatusCodes(),
                'sortable'  => true,
                'is_system' => false,
            ));

        }
        return $this;
    }

    /**
     * Adds join for the hermes parcel table to the shipment grid collection,
     * so the data is available for the custom columns
     *
     * @event sales_order_shipment_grid_collection_load_before
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Netresearch_Hermes_Model_Observer $this
     */
    public function addParcelsToShipmentGridCollection(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Resource_Order_Shipment_Grid_Collection $collection */
        $collection = $observer->getOrderShipmentGridCollection();
        $collection->getSelect()->joinLeft(
            array('hermes' => $collection->getTable('hermes/parcel')),
            'entity_id=hermes.shipment_id'
        );
        return $this;
    }
}
