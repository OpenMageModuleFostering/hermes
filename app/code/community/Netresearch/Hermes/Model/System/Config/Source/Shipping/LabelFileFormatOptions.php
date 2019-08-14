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
 * Hermes System Config Shipment Label file format Source
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Netresearch_Hermes_Model_System_Config_Source_Shipping_LabelFileFormatOptions
{

    const PDF = 'pdf';

    const JPEG = 'jpeg';

    /**
     * Get possible label formats e.g. pdf, jpeg as value => label array
     *
     * @return array $formats
     */
    public function toOptionArray()
    {
        $formatOptions = array(
            array('value' => self::PDF, 'label' => 'PDF'),
        );

        return $formatOptions;
    }

    /**
     * gets all label file formats
     *
     * @return array the fileformats
     */
    static public function getLabelFileFormats()
    {
        return array(
            self::PDF,
            self::JPEG
        );
    }

}
