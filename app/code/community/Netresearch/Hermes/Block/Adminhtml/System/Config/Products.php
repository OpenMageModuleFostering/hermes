<?php

class Netresearch_Hermes_Block_Adminhtml_System_Config_Products extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'hermes/system/config/products.phtml';
    
    public function render(Varien_Data_Form_Element_Abstract $fieldset)
    {
        $config = Mage::getModel('hermes/config');
        /* @var $config Netresearch_Hermes_Model_Config */
        
        $products = $config->getListOfProductsProducts();
        ksort($products);
        
        $this->addData(array(
            'fieldset_label' => $fieldset->getLegend(),
            'list_of_products_dated' => $config->getListOfProductsData('dated'),
            'list_of_products_products' => $products
        ));

        $html = $this->_getHeaderHtml($fieldset);
        $html.= $this->toHtml();
        $html .= $this->_getFooterHtml($fieldset);
        
        return $html;
    }
    
    public function getCountryName($code)
    {
        $country = Mage::getModel('directory/country');
        Mage::getResourceModel('directory/country')->loadByCode($country, $code);
        return $country->getName();
    }
}