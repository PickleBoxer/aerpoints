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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade module to version 1.2.0
 * Adds Points Rules system tables, new hook, and admin tab
 *
 * @param Aerpoints $module
 * @return bool
 */
function upgrade_module_1_2_0($module)
{
    $sql = array();

    // Check if aerpoints_rules table exists
    $table_exists = Db::getInstance()->executeS(
        'SHOW TABLES LIKE "' . _DB_PREFIX_ . 'aerpoints_rules"'
    );

    if (empty($table_exists)) {
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
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        // Points Rules Conditions Table
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aerpoints_rules_conditions` (
            `id_condition` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_aerpoints_rule` int(11) unsigned NOT NULL,
            `condition_type` varchar(50) NOT NULL,
            `condition_value` text,
            `condition_operator` varchar(20) DEFAULT NULL,
            PRIMARY KEY (`id_condition`),
            KEY `rule_conditions` (`id_aerpoints_rule`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

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
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        // Execute all queries
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
    }

    // Register new hooks for points display
    if (!$module->isRegisteredInHook('displayShoppingCart')) {
        if (!$module->registerHook('displayShoppingCart')) {
            return false;
        }
    }

    if (!$module->isRegisteredInHook('displayOrderDetail')) {
        if (!$module->registerHook('displayOrderDetail')) {
            return false;
        }
    }

    // Install Points Rules admin tab if it doesn't exist
    $id_tab_rules = (int)Tab::getIdFromClassName('AdminAerpointsRules');
    if (!$id_tab_rules) {
        $tab_rules = new Tab();
        $tab_rules->active = 1;
        $tab_rules->class_name = 'AdminAerpointsRules';
        $tab_rules->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab_rules->name[$lang['id_lang']] = 'Points Rules';
        }
        $tab_rules->id_parent = (int)Tab::getIdFromClassName('AdminCatalog');
        $tab_rules->module = $module->name;

        if (!$tab_rules->add()) {
            return false;
        }
    }

    return true;
}
