<?php
class Netresearch_Hermes_ConfigController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('system/hermes');
    }

    protected function _checkSectionAllowed($section)
    {
        if (false == Mage::getSingleton('admin/session')->isAllowed('system/hermes/' . $section)) {
            $this->forward('denied');
        }
    }
    
    public function updateListOfProductsAction()
    {
        $this->_checkSectionAllowed('update_list_of_products');
        $client = Mage::getModel('hermes/client');
        /* @var $client Netresearch_Hermes_Model_Client */
        
        try {
            if (null === $client->updateListOfProducts()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('hermes')->__('The list of products could not be updated.')
                );
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('hermes')->__('The list of products was successfully updated.')
                );
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('hermes')->__($e->getMessage()));
        }
        $this->_redirectReferer();
    }
}
