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
    `points_buy` int(11) NOT NULL DEFAULT 0,
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
    UNIQUE KEY `id_order` (`id_order`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Points History Table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aerpoints_history` (
    `id_aerpoints_history` int(11) NOT NULL AUTO_INCREMENT,
    `id_customer` int(11) NOT NULL,
    `id_order` int(11) DEFAULT NULL,
    `points` int(11) NOT NULL,
    `type` varchar(20) NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_aerpoints_history`),
    KEY `id_customer` (`id_customer`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
