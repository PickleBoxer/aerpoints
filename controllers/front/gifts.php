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

require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsGift.php');
require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsGiftOrder.php');
require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');

class AerpointsGiftsModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $auth = true;
    public $authRedirection = 'my-account';

    /**
     * Initialize content
     */
    public function initContent()
    {
        parent::initContent();

        // Check if module is enabled
        if (!Configuration::get('AERPOINTS_ENABLED')) {
            Tools::redirect('index.php?controller=my-account');
        }

        // Get customer's available points
        $id_customer = $this->context->customer->id;
        $available_points = AerpointsCustomer::getPointBalance($id_customer);

        // Get active gifts
        $gifts = AerpointsGift::getActiveGifts($this->context->language->id);

        $this->context->smarty->assign(array(
            'gifts' => $gifts,
            'available_points' => $available_points,
            'ajax_url' => $this->context->link->getModuleLink('aerpoints', 'gifts', array(), true),
            'module_dir' => $this->module->getPathUri()
        ));

        $this->setTemplate('gifts.tpl');
    }

    /**
     * AJAX: Redeem gift
     */
    public function displayAjaxRedeemGift()
    {
        // Check if customer is logged in
        if (!$this->context->customer->isLogged()) {
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->module->l('Please log in to redeem gifts', 'gifts')
            )));
        }

        // Check if module is enabled
        if (!Configuration::get('AERPOINTS_ENABLED')) {
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->module->l('Points system is currently disabled', 'gifts')
            )));
        }

        $id_gift = (int)Tools::getValue('id_gift');
        $customer_notes = Tools::getValue('customer_notes', '');
        $id_customer = $this->context->customer->id;

        // Load gift
        $gift = new AerpointsGift($id_gift);
        if (!Validate::isLoadedObject($gift)) {
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->module->l('Gift not found', 'gifts')
            )));
        }

        // Check if gift is available
        if (!$gift->isAvailable()) {
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->module->l('This gift is currently unavailable', 'gifts')
            )));
        }

        // Check customer points
        $available_points = AerpointsCustomer::getPointBalance($id_customer);
        if ($available_points < $gift->points_cost) {
            $shortage = $gift->points_cost - $available_points;
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => sprintf(
                    $this->module->l('Insufficient points. You need %d more points', 'gifts'),
                    $shortage
                )
            )));
        }

        // Deduct points from customer
        if (!AerpointsCustomer::removePoints($id_customer, $gift->points_cost, AerpointsHistory::TYPE_GIFT_REDEEMED, 'Gift redeemed: ' . $gift->name)) {
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->module->l('Failed to deduct points', 'gifts')
            )));
        }

        // Create gift order
        $order = new AerpointsGiftOrder();
        $order->id_customer = $id_customer;
        $order->id_aerpoints_gift = $gift->id;
        $order->gift_name = $gift->name;
        $order->points_spent = $gift->points_cost;
        $order->status = AerpointsGiftOrder::STATUS_PENDING;
        $order->customer_notes = pSQL($customer_notes, true);
        $order->date_add = date('Y-m-d H:i:s');
        $order->date_upd = date('Y-m-d H:i:s');

        if (!$order->add()) {
            // Refund points if order creation fails
            AerpointsCustomer::addPoints($id_customer, $gift->points_cost, AerpointsHistory::TYPE_REFUND, 'Gift order failed - points refunded');
            
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->module->l('Failed to create gift order', 'gifts')
            )));
        }

        // Decrement gift stock
        if (!$gift->decrementStock()) {
            // Refund points and delete order if stock update fails
            AerpointsCustomer::addPoints($id_customer, $gift->points_cost, AerpointsHistory::TYPE_REFUND, 'Gift stock update failed - points refunded');
            $order->delete();
            
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->module->l('Failed to update gift stock', 'gifts')
            )));
        }

        // Log to history
        /*AerpointsHistory::addHistoryEntry(
            $id_customer,
            -$gift->points_cost,
            AerpointsHistory::TYPE_GIFT_REDEEMED,
            'Gift: ' . $gift->name . ' (Order #' . $order->id . ')',
            null,
            null
        );*/

        die(Tools::jsonEncode(array(
            'success' => true,
            'message' => $this->module->l('Gift redeemed successfully! Check "My Gift Orders" for details.', 'gifts'),
            'order_id' => $order->id,
            'remaining_points' => $available_points - $gift->points_cost
        )));
    }
}
