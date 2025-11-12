<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

$sql = array();

// Customer Points Balance Table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aerpoints_customer` (
    `id_aerpoints_customer` int(11) NOT NULL AUTO_INCREMENT,
    `id_customer` int(11) NOT NULL,
    `available_points` int(11) NOT NULL DEFAULT 0,
    `total_earned` int(11) NOT NULL DEFAULT 0,
    `total_redeemed` int(11) NOT NULL DEFAULT 0,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_aerpoints_customer`),
    UNIQUE KEY `id_customer` (`id_customer`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Product Points Configuration Table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aerpoints_product` (
    `id_aerpoints_product` int(11) NOT NULL AUTO_INCREMENT,
    `id_product` int(11) NOT NULL,
    `points_earn` int(11) NOT NULL DEFAULT 0,
    `points_ratio` decimal(10,2) NOT NULL DEFAULT 0.00,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_aerpoints_product`),
    UNIQUE KEY `id_product` (`id_product`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Pending Points Table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aerpoints_pending` (
    `id_aerpoints_pending` int(11) NOT NULL AUTO_INCREMENT,
    `id_customer` int(11) NOT NULL,
    `id_order` int(11) NOT NULL,
    `points_to_earn` int(11) NOT NULL DEFAULT 0,
    `points_redeemed` int(11) NOT NULL DEFAULT 0,
    `status` varchar(20) NOT NULL DEFAULT \'pending\',
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_aerpoints_pending`),
    UNIQUE KEY `id_order` (`id_order`),
    KEY `id_customer` (`id_customer`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Points History Table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aerpoints_history` (
    `id_aerpoints_history` int(11) NOT NULL AUTO_INCREMENT,
    `id_customer` int(11) NOT NULL,
    `id_order` int(11) DEFAULT NULL,
    `id_cart_rule` int(11) DEFAULT NULL,
    `points` int(11) NOT NULL,
    `type` varchar(20) NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_aerpoints_history`),
    KEY `id_customer` (`id_customer`),
    KEY `id_cart_rule` (`id_cart_rule`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Points Rules Table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aerpoints_rules` (
    `id_aerpoints_rule` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `action_type` enum(\'bonus\',\'multiplier\') NOT NULL DEFAULT \'bonus\',
    `action_value` decimal(10,2) NOT NULL DEFAULT 0.00,
    `priority` int(10) unsigned NOT NULL DEFAULT 1,
    `date_from` datetime NOT NULL,
    `date_to` datetime NOT NULL,
    `quantity` int(10) unsigned NOT NULL DEFAULT 0,
    `quantity_per_user` int(10) unsigned NOT NULL DEFAULT 0,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_aerpoints_rule`),
    KEY `active_dates` (`active`, `date_from`, `date_to`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Points Rules Conditions Table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aerpoints_rules_conditions` (
    `id_condition` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `id_aerpoints_rule` int(11) unsigned NOT NULL,
    `condition_type` varchar(50) NOT NULL,
    `condition_value` text,
    `condition_operator` varchar(20) DEFAULT NULL,
    PRIMARY KEY (`id_condition`),
    KEY `rule_conditions` (`id_aerpoints_rule`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Points Rules Usage Table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aerpoints_rules_usage` (
    `id_usage` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `id_aerpoints_rule` int(11) unsigned NOT NULL,
    `id_customer` int(11) unsigned NOT NULL,
    `id_order` int(11) unsigned NOT NULL,
    `points_awarded` int(11) NOT NULL DEFAULT 0,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_usage`),
    KEY `rule_customer` (`id_aerpoints_rule`, `id_customer`),
    KEY `customer_usage` (`id_customer`, `id_aerpoints_rule`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
