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
 * Upgrade to version 1.1.0 - Add ratio-based points calculation
 */
function upgrade_module_1_1_0($module)
{
    // Check if points_ratio column already exists
    $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `'._DB_PREFIX_.'aerpoints_product`');
    $column_exists = false;

    foreach ($columns as $column) {
        if ($column['Field'] == 'points_ratio') {
            $column_exists = true;
            break;
        }
    }

    // Add points_ratio column if it doesn't exist
    if (!$column_exists) {
        $sql = 'ALTER TABLE `'._DB_PREFIX_.'aerpoints_product`
                ADD COLUMN `points_ratio` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `points_earn`';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }
    }

    return true;
}
