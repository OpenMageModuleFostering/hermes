<?php

/**
 * Netresearch _Hermes
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
 * Hermes API client unittest
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelPlacements
{
    
    /**
     * Get label placement options as value => label array
     *
     * @return array $methods
     */
    public function toOptionArray()
    {
        $placementOptions = array(
            array('value' => 1, 'label' =>  Mage::helper('hermes')->__('top left')),
            array('value' => 2, 'label' =>  Mage::helper('hermes')->__('top right')),
            array('value' => 3, 'label' =>  Mage::helper('hermes')->__('bottom left')),
            array('value' => 4, 'label' =>  Mage::helper('hermes')->__('bottom right'))
        );
        return $placementOptions;
    }
}
