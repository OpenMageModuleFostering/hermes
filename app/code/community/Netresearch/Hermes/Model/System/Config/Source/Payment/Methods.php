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
 * Hermes System Config Payment Methods Source
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      André Herrn <andre.herrn@netresearch.de>
 */
class Netresearch_Hermes_Model_System_Config_Source_Payment_Methods
{
    /**
     * Get payment methods.
     *
     * @return array $methods
     */
    public function toOptionArray()
    {
        $methods = array(array('value' => '', 'label' => ''));
        foreach (Mage::getStoreConfig('payment') as $code => $payment):
            $payment = new ArrayObject($payment);
            if (!$payment->offsetExists('title') ||
                !$payment->offsetExists('active') ||
                (int) $payment->offsetGet('active') !== 1):
                continue;
            endif;
            $methods[$code] = array(
                'label' => Mage::helper('payment')->__($payment->offsetGet('title')),
                'value' => $code);
        endforeach;
        return $methods;
    }
}