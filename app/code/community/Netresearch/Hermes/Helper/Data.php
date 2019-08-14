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
 * Data Helper for Hermes module
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Hermes_Helper_Data extends Mage_Core_Helper_Data
{
    /**
     * log to a separate log file
     *
     * @param string $message
     * @param int    $level
     * @param bool   $force
     * @return Netresearch_Hermes_Helper_Data
     */
    public function log($message, $level=null, $force=false)
    {
        if ($force || Mage::getModel('hermes/config')->isLoggingEnabled()) {
            Mage::log($message, $level, 'hermes.log', $force);
        }
        return $this;
    }

    public function getIso3CodeByIso2Code($iso2Code)
    {
        return Mage::getModel('directory/country')->load($iso2Code)->getIso3Code();
    }
}
