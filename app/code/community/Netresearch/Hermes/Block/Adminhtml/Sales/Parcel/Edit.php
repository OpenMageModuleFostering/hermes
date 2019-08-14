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
 * Netresearch_Hermes_Block_Adminhtml_Sales_Parcel_Edit
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Netresearch_Hermes_Block_Adminhtml_Sales_Parcel_Edit 
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_headerText = $this->helper('hermes')->__('Edit Receiver Data');
        parent::__construct();
        
        $this->_removeButton('delete');
        
        $parcel = Mage::getModel('hermes/parcel')->load(
            $this->getRequest()->getParam('id')
        );
        /* @var $parcel Netresearch_Hermes_Model_Parcel */
        if ($parcel->canBeResumed()) {
            $this->_addButton('saveandresume', array(
                'label'     => Mage::helper('hermes')->__('Save parcel and resume'),
                'onclick'   => '$(\'parcel_save_and_resume\').setValue(1); editForm.submit();',
                'class'     => 'save',
            ), 2);
            
        }
    }
    
    protected function _prepareLayout()
    {
        $block = $this->getLayout()->createBlock(
            'hermes/adminhtml_sales_parcel_edit_form' 
        );

        /* @var $block Netresearch_Hermes_Block_Adminhtml_Sales_Parcel_Edit_Form */
        $this->setChild('form', $block);

        /* avoid rendering of non-existing child block */
        $this->_blockGroup = null;

        return parent::_prepareLayout();
    }
    
    public function getBackUrl()
    {
        $parcel = Mage::getModel('hermes/parcel')->load(
            $this->getRequest()->getParam('id')
        );
        return $this->getUrl('adminhtml/sales_shipment/view', array(
            'shipment_id' => $parcel->getShipmentId()
        ));
    }
}
