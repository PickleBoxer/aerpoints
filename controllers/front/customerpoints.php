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

class AerpointsCustomerpointsModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $authRedirection = 'my-account';
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        // Handle AJAX requests first
        if (Tools::getValue('ajax')) {
            $this->processAjaxRequest();
            return;
        }
        // Check if module is enabled
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            Tools::redirect('index.php?controller=404');
        }

        // Check if customer is allowed to use points system
        $allowed_customers = Configuration::get('AERPOINTS_CUSTOMERS');
        if (! empty($allowed_customers)) {
            $customer_ids = array_map('trim', explode(',', $allowed_customers));
            if (! in_array((string)$this->context->customer->id, $customer_ids)) {
                Tools::redirect('index.php?controller=404');
            }
        }

        parent::initContent();

        include_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
        include_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');
        include_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsPending.php');

        $customer_id = $this->context->customer->id;
        
        // Get customer points
        $customer_points = AerpointsCustomer::getPointBalance($customer_id);
        
        // Get points history
        $points_history = AerpointsHistory::getCustomerHistory($customer_id);

        // Get pending points
        $pending_points = AerpointsPending::getCustomerPendingPoints($customer_id);

        // Get configuration values
        $point_value = (int) Configuration::get('AERPOINTS_POINT_VALUE', 100);
        $min_redemption = (int) Configuration::get('AERPOINTS_MIN_REDEMPTION', 100);

        $this->context->smarty->assign(array(
            'customer_points' => $customer_points,
            'points_history' => $points_history,
            'pending_points' => $pending_points,
            'navigationPipe' => Configuration::get('PS_NAVIGATION_PIPE'),
            'point_value' => $point_value,
            'min_redemption' => $min_redemption,
        ));

        $this->setTemplate('customer_points.tpl');
    }

    /**
     * Process AJAX requests
     */
    private function processAjaxRequest()
    {
        $action = Tools::getValue('action');
        
        if ($action === 'applyPoints') {
            $this->processApplyPoints();
        } else {
            die(Tools::jsonEncode(array('status' => 'error', 'message' => $this->module->l('Invalid action'))));
        }
    }

    /**
     * Process point redemption - creates cart rule but does NOT apply to cart
     */
    private function processApplyPoints()
    {
        // Check if customer is logged in
        if (! $this->context->customer->isLogged()) {
            die(Tools::jsonEncode(array('status' => 'error', 'message' => $this->module->l('Customer not logged in'))));
        }

        // Check if aerpoints is enabled
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            die(Tools::jsonEncode(array('status' => 'error', 'message' => $this->module->l('AerPoints is disabled'))));
        }

        // Get points to redeem
        $points_to_redeem = (int) Tools::getValue('points');
        $min_redemption = (int) Configuration::get('AERPOINTS_MIN_REDEMPTION', 100);

        if ($points_to_redeem < $min_redemption) {
            die(Tools::jsonEncode(array(
                'status' => 'error',
                'message' => sprintf($this->module->l('Minimum redemption is %d points'), $min_redemption)
            )));
        }

        // Load customer points
        include_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
        $customer_points = AerpointsCustomer::getPointBalance($this->context->customer->id);

        if ($points_to_redeem > $customer_points) {
            die(Tools::jsonEncode(array('status' => 'error', 'message' => $this->module->l('Insufficient points'))));
        }

        // Calculate discount amount
        $point_value = (int) Configuration::get('AERPOINTS_POINT_VALUE', 100);
        $discount_amount = $points_to_redeem / $point_value;

        // Create cart rule (voucher) but DO NOT apply to cart
        $cart_rule = $this->createPointsCartRule($points_to_redeem, $discount_amount);

        if (! $cart_rule) {
            die(Tools::jsonEncode(array('status' => 'error', 'message' => $this->module->l('Failed to create voucher'))));
        }

        // Remove points from customer balance immediately
        include_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');
        AerpointsCustomer::removePoints($this->context->customer->id, $points_to_redeem, AerpointsHistory::TYPE_REDEEMED, sprintf('Points redeemed for discount - Cart Rule ID: %d', $cart_rule->id), null, $cart_rule->id);

        // Return success with redemption details
        die(Tools::jsonEncode(array(
            'status' => 'success', 
            'message' => sprintf(
                $this->module->l('Voucher created! You redeemed %d points for a %s discount. Use code: %s on your next order.'),
                $points_to_redeem,
                Tools::displayPrice($discount_amount),
                $cart_rule->code
            )
        )));
    }

    /**
     * Create a cart rule for point redemption
     */
    private function createPointsCartRule($points, $discount_amount)
    {
        $cart_rule = new CartRule();
        $cart_rule->name = array(
            (int) Configuration::get('PS_LANG_DEFAULT') => sprintf($this->module->l('AerPoints Redemption - %d points'), $points)
        );
        $cart_rule->description = sprintf($this->module->l('Redeemed %d AerPoints'), $points);
        $cart_rule->code = 'AERPOINTS_'.$this->context->customer->id.'_'.$points.'_'.time();
        $cart_rule->id_customer = (int) $this->context->customer->id;
        $cart_rule->quantity = 1;
        $cart_rule->quantity_per_user = 1;
        $cart_rule->highlight = 1;
        $cart_rule->reduction_amount = (float) $discount_amount;
        $cart_rule->reduction_tax = 1;
        $cart_rule->reduction_currency = (int) $this->context->currency->id;
        $cart_rule->date_from = date('Y-m-d H:i:s');
        $cart_rule->date_to = date('Y-m-d H:i:s', strtotime('+1 day'));
        $cart_rule->active = 1;

        return $cart_rule->add() ? $cart_rule : false;
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        
        $breadcrumb['links'][] = array(
            'title' => $this->module->l('My account'),
            'url' => $this->context->link->getPageLink('my-account')
        );
        
        $breadcrumb['links'][] = array(
            'title' => $this->module->l('My Points'),
            'url' => $this->context->link->getModuleLink('aerpoints', 'customerpoints')
        );

        return $breadcrumb;
    }
}
