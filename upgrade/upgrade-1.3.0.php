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
 * Upgrade module to version 1.3.0
 * Adds gift catalog and gift orders functionality
 */
function upgrade_module_1_3_0($module)
{
    $sql = array();

    // Create Gift Catalog Table
    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aerpoints_gift` (
        `id_aerpoints_gift` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` text,
        `points_cost` int(11) NOT NULL,
        `quantity` int(11) NOT NULL DEFAULT 0,
        `image` varchar(255) DEFAULT NULL,
        `active` tinyint(1) NOT NULL DEFAULT 1,
        `position` int(11) NOT NULL DEFAULT 0,
        `date_add` datetime NOT NULL,
        `date_upd` datetime NOT NULL,
        PRIMARY KEY (`id_aerpoints_gift`),
        KEY `active_position` (`active`, `position`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    // Create Gift Orders Table
    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aerpoints_gift_order` (
        `id_aerpoints_gift_order` int(11) NOT NULL AUTO_INCREMENT,
        `id_customer` int(11) NOT NULL,
        `id_aerpoints_gift` int(11) NOT NULL,
        `gift_name` varchar(255) NOT NULL,
        `points_spent` int(11) NOT NULL,
        `status` varchar(20) NOT NULL DEFAULT \'pending\',
        `customer_notes` text,
        `admin_notes` text,
        `date_add` datetime NOT NULL,
        `date_upd` datetime NOT NULL,
        PRIMARY KEY (`id_aerpoints_gift_order`),
        KEY `customer_idx` (`id_customer`),
        KEY `status_idx` (`status`),
        KEY `date_idx` (`date_add`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    // Execute all SQL queries
    foreach ($sql as $query) {
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }

    // Create gift images directory
    $gift_img_dir = dirname(__FILE__) . '/../views/img/gifts';
    if (!file_exists($gift_img_dir)) {
        if (!mkdir($gift_img_dir, 0755, true)) {
            return false;
        }
    }

    // Copy placeholder image if it doesn't exist
    $placeholder_src = dirname(__FILE__) . '/../views/img/no-image.png';
    $placeholder_dest = $gift_img_dir . '/no-image.png';
    
    // Create a simple placeholder if source doesn't exist
    if (!file_exists($placeholder_src) && !file_exists($placeholder_dest)) {
        // Create a simple 1x1 transparent PNG as placeholder
        $img = imagecreatetruecolor(400, 300);
        $bg_color = imagecolorallocate($img, 240, 240, 240);
        imagefill($img, 0, 0, $bg_color);
        $text_color = imagecolorallocate($img, 150, 150, 150);
        $text = 'No Image';
        imagestring($img, 5, 170, 145, $text, $text_color);
        imagepng($img, $placeholder_dest);
        imagedestroy($img);
    } elseif (file_exists($placeholder_src) && !file_exists($placeholder_dest)) {
        copy($placeholder_src, $placeholder_dest);
    }

    // Install admin tabs for gift management
    if (!installGiftTabs($module)) {
        return false;
    }

    return true;
}

/**
 * Install gift management admin tabs
 */
function installGiftTabs($module)
{
    // Create Gift Catalog tab
    $tab_gifts = new Tab();
    $tab_gifts->active = 1;
    $tab_gifts->class_name = 'AdminAerpointsGifts';
    $tab_gifts->name = array();
    foreach (Language::getLanguages(true) as $lang) {
        $tab_gifts->name[$lang['id_lang']] = 'AerPoints Gifts';
    }
    $tab_gifts->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
    $tab_gifts->module = $module->name;
    $result = $tab_gifts->add();

    // Create Gift Orders tab
    $tab_gift_orders = new Tab();
    $tab_gift_orders->active = 1;
    $tab_gift_orders->class_name = 'AdminAerpointsGiftOrders';
    $tab_gift_orders->name = array();
    foreach (Language::getLanguages(true) as $lang) {
        $tab_gift_orders->name[$lang['id_lang']] = 'AerPoints Gift Orders';
    }
    $tab_gift_orders->id_parent = (int) Tab::getIdFromClassName('AdminParentOrders');
    $tab_gift_orders->module = $module->name;
    $result = $result && $tab_gift_orders->add();

    return $result;
}
