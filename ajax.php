<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

// Check if customer is logged in
if (!Context::getContext()->customer->isLogged()) {
    die(json_encode(array('error' => 'Not logged in')));
}

$action = Tools::getValue('action');
$customer_id = Context::getContext()->customer->id;

switch ($action) {
    case 'applyPoints':
        $points = (int)Tools::getValue('points');
        
        if ($points <= 0) {
            die(json_encode(array('error' => 'Invalid points amount')));
        }
        
        // Check if customer has enough points
        include_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
        //$customer_points = AerpointsCustomer::getCustomerPoints($customer_id);
        $customer_points = AerpointsCustomer::getPointBalance($customer_id);
        
        if ($points > $customer_points) {
            die(json_encode(array('error' => 'Not enough points')));
        }
        
        // Store redemption in session
        Context::getContext()->cookie->aerpoints_redeem = $points;
        Context::getContext()->cookie->write();
        
        die(json_encode(array('success' => true, 'points' => $points)));
        break;
        
    case 'removePoints':
        // Remove redemption from session
        unset(Context::getContext()->cookie->aerpoints_redeem);
        Context::getContext()->cookie->write();
        
        die(json_encode(array('success' => true)));
        break;
        
    default:
        die(json_encode(array('error' => 'Invalid action')));
}
