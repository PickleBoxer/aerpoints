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

class AerpointsAjaxModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        if (! Tools::getValue('ajax')) {
            die(Tools::jsonEncode(array('status' => 'error', 'message' => $this->module->l('Invalid request'))));
        }

        $action = Tools::getValue('action');

        switch ($action) {
            case 'applyPoints':
                $this->processApplyPoints();
                break;
            default:
                die(Tools::jsonEncode(array('status' => 'error', 'message' => $this->module->l('Invalid action'))));
        }
    }

    /**
     * Process point redemption
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
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
        $customer_points = AerpointsCustomer::getPointBalance($this->context->customer->id);

        if ($points_to_redeem > $customer_points) {
            die(Tools::jsonEncode(array('status' => 'error', 'message' => $this->module->l('Insufficient points'))));
        }

        // Calculate discount amount
        $point_value = (int) Configuration::get('AERPOINTS_POINT_VALUE', 100);
        $discount_amount = $points_to_redeem / $point_value;

        // Create cart rule (voucher)
        $cart_rule = $this->createPointsCartRule($points_to_redeem, $discount_amount);

        if (! $cart_rule) {
            die(Tools::jsonEncode(array('status' => 'error', 'message' => $this->module->l('Failed to create voucher'))));
        }

        // Apply cart rule to current cart
        if ($this->context->cart->addCartRule($cart_rule->id)) {
            // Remove points from customer balance immediately
            require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');
            AerpointsCustomer::removePoints($this->context->customer->id, $points_to_redeem, AerpointsHistory::TYPE_REDEEMED, sprintf('Points redeemed for discount - Cart Rule ID: %d', $cart_rule->id), null, $cart_rule->id);

            die(Tools::jsonEncode(array('status' => 'success', 'message' => $this->module->l('Points applied successfully'))));
        } else {
            // Failed to apply cart rule, clean up the cart rule and refund points
            $cart_rule->delete();
            die(Tools::jsonEncode(array('status' => 'error', 'message' => $this->module->l('Failed to apply points to cart'))));
        }
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
}
