<?php

/**
 * wallee Magento 1
 *
 * This Magento extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

/**
 * Add column to mark orders as canceled.
 */
$installer->getConnection()->addColumn(
    $installer->getTable('sales/order'), 'wallee_canceled', array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'default' => '0',
        'comment' => 'wallee Canceled'
    )
);

$installer->endSetup();