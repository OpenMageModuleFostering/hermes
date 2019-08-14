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
 * Database setup script
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */

$installer = $this;
$installer->startSetup();

$installer->run("
-- DROP TABLE IF EXISTS {$this->getTable('hermes_parcel')};

CREATE TABLE IF NOT EXISTS {$this->getTable('hermes_parcel')} (
  `id` int(10) unsigned NOT NULL auto_increment,
  `shipment_id` int(10) NOT NULL,
  `hermes_order_no` varchar(50),
  `hermes_shipping_id` varchar(16),
  `hermes_creation_date` datetime,
  `hermes_status_text` varchar(255),
  `hermes_status` int(11),
  `receiver_firstname` varchar(255),
  `receiver_lastname` varchar(25),
  `receiver_street` varchar(27),
  `receiver_house_number` varchar(5),
  `receiver_address_add` varchar(255),
  `receiver_postcode` varchar(255),
  `receiver_city` varchar(30),
  `receiver_district` varchar(255),
  `receiver_country_code` varchar(3),
  `receiver_email` varchar(255),
  `receiver_telephone_number` varchar(255),
  `receiver_telephone_prefix` varchar(50),
  `parcel_class` varchar(3),
  `amount_cash_on_delivery_eurocent` int(11) NOT NULL default 0,
  `include_cash_on_delivery` int(1) NOT NULL default 0,
  `error_code` varchar(10),
  `error_message` varchar(1000),
  `status_code` int NOT NULL default 0,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `shipment_id` (`shipment_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Hermes Shipments';

-- set installation timestamp
REPLACE INTO {$this->getTable('core_config_data')} (scope, scope_id, path, value) VALUES ('default', 0, 'hermes/general/installation_date', NOW());
");

$installer->endSetup();

