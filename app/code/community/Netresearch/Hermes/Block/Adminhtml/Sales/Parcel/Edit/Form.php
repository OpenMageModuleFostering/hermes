<?php

class Netresearch_Hermes_Block_Adminhtml_Sales_Parcel_Edit_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected $parcel;
    
    public function __construct()
    {
        parent::__construct();
        $this->setId('parcel_form');
        $this->setTitle(Mage::helper('hermes')->__('Parcel Information'));
        
        $this->parcel = Mage::getModel('hermes/parcel')->load(
            $this->getRequest()->getParam('id')
        );
    }
    
    protected function _prepareForm()
    {
        $parcelClasses = Mage::getModel('hermes/config')->getAllProductClassesasKeyValue();
        array_unshift($parcelClasses, array('value' => '', 'label' => Mage::helper('hermes')->__('use default')));

        $form = new Varien_Data_Form(array(
            'id'     => 'edit_form',
            'action' => $this->getUrl('*/parcel/save'),
            'method' => 'post'
        ));
        
        $form->setHtmlIdPrefix('parcel_');
        
        $fieldset = $form->addFieldset('receiver_data', array(
            'legend' => Mage::helper('hermes')->__('Receiver Data')
        ));
        
        $fieldset->addField('id', 'hidden', array(
            'name'      => 'parcel_id'
        ));
        
        $fieldset->addField('save_and_resume', 'hidden', array(
            'name' => 'save_and_resume'
        ));
        
        $fieldset->addField('receiver_firstname', 'text', array(
            'name'      => 'receiver[firstname]',
            'label'     => Mage::helper('hermes')->__('First Name'),
            'title'     => Mage::helper('hermes')->__('First Name'),
            'note'      => Mage::helper('hermes')->__('Leave empty if receiver is a company'),
            'required'  => false
        ));
        
        $fieldset->addField('receiver_lastname', 'text', array(
            'name'      => 'receiver[lastname]',
            'label'     => Mage::helper('hermes')->__('Last Name/Company'),
            'title'     => Mage::helper('hermes')->__('Last Name/Company'),
            'note'      => Mage::helper('hermes')->__(
                'If you enter a company name, you could submit the c/o in field <em>%s</em>',
                Mage::helper('hermes')->__('Additional Address Data')
            ),
            'required'  => true
        ));

        $fieldset->addField('receiver_address_add', 'text', array(
            'name'      => 'receiver[address_add]',
            'label'     => Mage::helper('hermes')->__('Additional Address Data'),
            'title'     => Mage::helper('hermes')->__('Additional Address Data'),
            'required'  => false
        ));
        
        $fieldset->addField('receiver_street', 'text', array(
            'name'      => 'receiver[street]',
            'label'     => Mage::helper('hermes')->__('Street Address'),
            'title'     => Mage::helper('hermes')->__('Street Address'),
            'note'      => Mage::helper('hermes')->__('May contain house number'),
            'required'  => true,
        ));
        
        $fieldset->addField('receiver_house_number', 'text', array(
            'name'      => 'receiver[house_number]',
            'label'     => Mage::helper('hermes')->__('House Number'),
            'title'     => Mage::helper('hermes')->__('House Number'),
            'required'  => false
        ));

        $fieldset->addField('receiver_postcode', 'text', array(
            'name'      => 'receiver[postcode]',
            'label'     => Mage::helper('hermes')->__('Zip/Postal Code'),
            'title'     => Mage::helper('hermes')->__('Zip/Postal Code'),
            'required'  => true
        ));

        $fieldset->addField('receiver_city', 'text', array(
            'name'      => 'receiver[city]',
            'label'     => Mage::helper('hermes')->__('City'),
            'title'     => Mage::helper('hermes')->__('City'),
            'required'  => true
        ));
        
        // only display field if country is IRL!
        if ($this->parcel->getReceiverCountryCode() === 'IRL') {
            $fieldset->addField('receiver_district', 'text', array(
                'name'      => 'receiver[district]',
                'label'     => Mage::helper('hermes')->__('District'),
                'title'     => Mage::helper('hermes')->__('District'),
                'required'  => true
            ));
        }
        
        // display readonly!
        $fieldset->addField('receiver_country_code', 'text', array(
            'name'      => 'receiver[country_code]',
            'label'     => Mage::helper('hermes')->__('Country Code'),
            'title'     => Mage::helper('hermes')->__('Country Code'),
            'required'  => true
        ))->setReadonly(true);
        
        $fieldset->addField('receiver_email', 'text', array(
            'name'      => 'receiver[email]',
            'label'     => Mage::helper('hermes')->__('Email'),
            'title'     => Mage::helper('hermes')->__('Email'),
            'note'      => Mage::helper('hermes')->__('Let Hermes send an email containing the tracking link'),
            'required'  => false
        ));

        $fieldset->addField('receiver_telephone_number', 'text', array(
            'name'      => 'receiver[telephone_number]',
            'label'     => Mage::helper('hermes')->__('Telephone'),
            'title'     => Mage::helper('hermes')->__('Telephone'),
            'required'  => false
        ));
        
        $fieldset->addField('receiver_telephone_prefix', 'text', array(
            'name'      => 'receiver[telephone_prefix]',
            'label'     => Mage::helper('hermes')->__('Telephone Prefix'),
            'title'     => Mage::helper('hermes')->__('Telephone Prefix'),
            'required'  => false
        ));
        
        if (Mage::getModel('hermes/config')->isTieredPriceMerchant()) {
            $fieldset->addField('parcel_class', 'select', array(
                'name'      => 'parcel_class',
                'label'     => Mage::helper('hermes')->__('Parcel class'),
                'title'     => Mage::helper('hermes')->__('Parcel class'),
                'values'    => $parcelClasses
            ));
        }
        
        $form->setValues($this->parcel->getData());
        $form->getElement('save_and_resume')->setValue('0');
        $this->setForm($form);
        $form->setUseContainer(true);

        return parent::_prepareForm();
    }
}
