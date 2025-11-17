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

if (! defined('_PS_VERSION_')) {
    exit;
}

class Aerpoints extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'aerpoints';
        $this->tab = 'pricing_promotion';
        $this->version = '1.3.0';
        $this->author = 'AerDigital';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('AerPoints');
        $this->description = $this->l('Product-based loyalty points system for customer engagement');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '9.0');
    }

    /**
     * Module installation
     */
    public function install()
    {
        Configuration::updateValue('AERPOINTS_ENABLED', 1);
        Configuration::updateValue('AERPOINTS_CUSTOMERS', '');
        Configuration::updateValue('AERPOINTS_POINT_VALUE', 100);
        Configuration::updateValue('AERPOINTS_MIN_REDEMPTION', 100);
        Configuration::updateValue('AERPOINTS_PARTIAL_PAYMENT', 1);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionValidateOrder') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('displayProductButtons') &&
            $this->registerHook('displayShoppingCartFooter') &&
            $this->registerHook('displayCustomerAccount') &&
            $this->registerHook('displayAdminProductsExtra') &&
            $this->registerHook('actionProductUpdate') &&
            $this->registerHook('displayAdminOrder') &&
            $this->registerHook('displayAdminCustomers') &&
            $this->registerHook('actionAdminProductsListingFieldsModifier') &&
            $this->registerHook('displayShoppingCart') &&
            $this->registerHook('displayOrderDetail') &&
            $this->registerHook('displayProductListReviews') &&
            $this->installTab();
    }

    public function uninstall()
    {
        Configuration::deleteByName('AERPOINTS_ENABLED');
        Configuration::deleteByName('AERPOINTS_CUSTOMERS');
        Configuration::deleteByName('AERPOINTS_POINT_VALUE');
        Configuration::deleteByName('AERPOINTS_MIN_REDEMPTION');
        Configuration::deleteByName('AERPOINTS_PARTIAL_PAYMENT');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall() && $this->uninstallTab();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitAerpointsModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign(
            array(
                'link' => $this->context->link,
                'module_dir' => $this->_path
            )
        );

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAerpointsModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        // Define order statuses for refund dropdown
        $order_statuses = array();
        foreach (OrderState::getOrderStates($this->context->language->id) as $status) {
            $order_statuses[] = array(
                'id_option' => $status['id_order_state'],
                'name' => $status['name'],
            );
        }

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable Points System'),
                        'name' => 'AERPOINTS_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->l('Enable or disable the points system'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user"></i>',
                        'desc' => $this->l('Enable module just for specific customers. Leave empty to enable for all customers. Separate customer IDs with comma.'),
                        'name' => 'AERPOINTS_CUSTOMERS',
                        'label' => $this->l('Customers'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('How many points equal 1 euro (default: 100)'),
                        'name' => 'AERPOINTS_POINT_VALUE',
                        'label' => $this->l('Points per Euro'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Minimum points required for redemption'),
                        'name' => 'AERPOINTS_MIN_REDEMPTION',
                        'label' => $this->l('Minimum Redemption'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Allow Partial Payment'),
                        'name' => 'AERPOINTS_PARTIAL_PAYMENT',
                        'is_bool' => true,
                        'desc' => $this->l('Allow customers to use points for partial payment'),
                        'values' => array(
                            array(
                                'id' => 'partial_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'partial_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Refund Order Status'),
                        'name' => 'AERPOINTS_REFUND_STATUS',
                        'desc' => $this->l('Select the order status to trigger points refund.'),
                        'options' => array(
                            'query' => $order_statuses,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'AERPOINTS_ENABLED' => (bool) Tools::getValue('AERPOINTS_ENABLED', Configuration::get('AERPOINTS_ENABLED')),
            'AERPOINTS_CUSTOMERS' => Tools::getValue('AERPOINTS_CUSTOMERS', Configuration::get('AERPOINTS_CUSTOMERS')),
            'AERPOINTS_POINT_VALUE' => (int) Tools::getValue('AERPOINTS_POINT_VALUE', Configuration::get('AERPOINTS_POINT_VALUE')),
            'AERPOINTS_MIN_REDEMPTION' => (int) Tools::getValue('AERPOINTS_MIN_REDEMPTION', Configuration::get('AERPOINTS_MIN_REDEMPTION')),
            'AERPOINTS_PARTIAL_PAYMENT' => (bool) Tools::getValue('AERPOINTS_PARTIAL_PAYMENT', Configuration::get('AERPOINTS_PARTIAL_PAYMENT')),
            'AERPOINTS_REFUND_STATUS' => (int) Tools::getValue('AERPOINTS_REFUND_STATUS', Configuration::get('AERPOINTS_REFUND_STATUS')),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Check if current customer is allowed to use points system
     * @return bool
     */
    private function isCustomerAllowed()
    {
        if (! $this->context->customer->isLogged()) {
            return false;
        }

        $allowed_customers = Configuration::get('AERPOINTS_CUSTOMERS');
        if (empty($allowed_customers)) {
            return true; // If empty, allow all customers
        }

        $customer_ids = array_map('trim', explode(',', $allowed_customers));
        return in_array((string) $this->context->customer->id, $customer_ids);
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        if (! $this->isCustomerAllowed()) {
            return;
        }

        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');

        // Add user points and eligibility to JavaScript
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
        $customer_points = AerpointsCustomer::getPointBalance($this->context->customer->id);
        Media::addJsDef(array(
            'aerpoints_user_points' => (int)$customer_points,
            'aerpoints_eligible' => true
        ));
    }

    /**
     * Hook: actionValidateOrder
     * Called when order is validated - create pending points entry
     */
    public function hookActionValidateOrder($params)
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        // Check if customer is allowed to use points system
        $order = $params['order'];
        $customer_id = $order->id_customer;

        $allowed_customers = Configuration::get('AERPOINTS_CUSTOMERS');
        if (! empty($allowed_customers)) {
            $customer_ids = array_map('trim', explode(',', $allowed_customers));
            if (! in_array((string) $customer_id, $customer_ids)) {
                return; // Customer not allowed
            }
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsPending.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsProduct.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsRule.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsRuleCondition.php');

        $cart = $params['cart'];
        $customer = new Customer($customer_id);

        $total_points_to_earn = 0;

        // Calculate points to earn from products in cart
        foreach ($cart->getProducts() as $product) {
            $product_points = AerpointsProduct::getProductPoints($product['id_product']);
            // Only count points if product is active
            if ($product_points && isset($product_points['active']) && $product_points['active'] == 1) {
                // Get tax-excluded price
                $null = null;
                $price_tax_excl = Product::getPriceStatic($product['id_product'], false, null, 6, null, false, true, 1, false, null, null, null, $null, true, true, null, true, false);
                //$price_tax_excl = Product::getPriceStatic($product['id_product'], false);
                // Use new calculation method (supports both fixed and ratio)
                $points = AerpointsProduct::calculateProductPoints(
                    $product['id_product'],
                    $price_tax_excl,
                    $product['quantity']
                );
                $total_points_to_earn += $points;
            }
        }

        // Apply Points Rules
        $rules = AerpointsRule::getActiveRules();
        $bonus_points = 0;
        $multiplier = 1.0;

        foreach ($rules as $rule) {
            if ($rule->isValid($cart, $customer)) {
                // Get quantity multiplier for product-based rules
                $qty_multiplier = $rule->getQuantityMultiplier($cart);

                if ($rule->action_type === 'bonus') {
                    $rule_bonus = (int)$rule->action_value * $qty_multiplier;
                    $bonus_points += $rule_bonus;
                    // Record usage
                    $rule->recordUsage($customer_id, $order->id, $rule_bonus);
                } elseif ($rule->action_type === 'multiplier') {
                    // Use highest multiplier
                    if ((float)$rule->action_value > $multiplier) {
                        $multiplier = (float)$rule->action_value;
                    }
                    // Record usage (0 points as it's a multiplier)
                    $rule->recordUsage($customer_id, $order->id, 0);
                }
            }
        }

        // Calculate final points: (base × multiplier) + bonus
        $total_points_to_earn = (int)(($total_points_to_earn * $multiplier) + $bonus_points);

        // Create pending points entry for earned points only
        if ($total_points_to_earn > 0) {
            AerpointsPending::createPendingEntry(
                $order->id,
                $order->id_customer,
                $total_points_to_earn,
                0 // No redeemed points to track here
            );
        }
    }

    /**
     * Hook: actionOrderStatusPostUpdate
     * Called when order status changes - process pending points or refunds
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        // Check if customer is allowed to use points system
        $order = new Order($params['id_order']);
        $customer_id = $order->id_customer;
        $cart_rules = $params['cart']->getCartRules();

        $allowed_customers = Configuration::get('AERPOINTS_CUSTOMERS');
        if (! empty($allowed_customers)) {
            $customer_ids = array_map('trim', explode(',', $allowed_customers));
            if (! in_array((string) $customer_id, $customer_ids)) {
                return; // Customer not allowed
            }
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsPending.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');

        $new_status = $params['newOrderStatus'];

        // Check if order is completed (payment accepted)
        if ($new_status->paid == 1) {
            AerpointsPending::completePendingPoints($order->id);

            // Also log cart rule completion if there were AerPoints redemptions
            $this->logCartRuleCompletion($order->id);
        }
        // Check if order is cancelled
        elseif ($new_status->id == Configuration::get('PS_OS_CANCELED')) {
            // If order was cancelled before completion, cancel pending points
            AerpointsPending::cancelPendingPoints($order->id);
        }
        // Check if order is refunded
        if ($new_status->id == (int) Configuration::get('AERPOINTS_REFUND_STATUS')) {
            $order_history = AerpointsHistory::getOrderHistory($order->id);
            $points = 0;
            $description = 'Points removed due to cancellation for order #'.$order->id;

            // Remove earned points
            if (is_array($order_history)) {
                foreach ($order_history as $entry) {
                    if (isset($entry['points']) && $entry['type'] == AerpointsHistory::TYPE_EARNED) {
                        $points += (int) $entry['points'];
                    }
                }
            }
            if ($points > 0) {
                AerpointsCustomer::removePoints($customer_id, $points, AerpointsHistory::TYPE_REFUND, $description, $order->id);
            }

            // Refund voucher points
            $cart_rules = $params['cart']->getCartRules();
            foreach ($cart_rules as $cart_rule) {
                $history_entry = AerpointsHistory::getHistoryByCartRule($cart_rule['id_cart_rule']);
                if ($history_entry && $history_entry['id_order'] == $order->id && isset($history_entry['points'])) {
                    $voucher_points = abs((int) $history_entry['points']);
                    if ($voucher_points > 0) {
                        AerpointsCustomer::removePoints($customer_id, $voucher_points, AerpointsHistory::TYPE_REFUND, $description, $order->id);
                    }
                }
            }
        }
    }

    /**
     * Hook: displayProductButtons
     * Show points information under product price
     */
    public function hookDisplayProductButtons($params)
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        if (! $this->isCustomerAllowed()) {
            return;
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsProduct.php');

        $product = $params['product'];
        if (! $product) {
            return;
        }

        $product_points = AerpointsProduct::getProductPoints($product->id);
        // Only show if product is active and has either fixed points or ratio
        if (
            ! $product_points ||
            (isset($product_points['active']) && $product_points['active'] == 0)
        ) {
            return;
        }

        // Calculate actual points customer will earn
        $null = null;
        $price_tax_excl = Product::getPriceStatic($product->id, false, null, 6, null, false, true, 1, false, null, null, null, $null, true, true, null, true, false);
        //$price_tax_excl = Product::getPriceStatic($product->id, false);
        $calculated_points = AerpointsProduct::calculateProductPoints($product->id, $price_tax_excl, 1);

        if ($calculated_points <= 0) {
            return; // Don't show if no points
        }

        $this->context->smarty->assign(array(
            'product_points' => $product_points,
            'calculated_points' => $calculated_points,
            'point_value' => Configuration::get('AERPOINTS_POINT_VALUE', 100),
            'module_dir' => $this->_path,
        ));

        return $this->display(__FILE__, 'views/templates/hook/product_points.tpl');
    }

    /**
     * Hook: displayShoppingCartFooter
     * Show points redemption option in cart
     */
    public function hookDisplayShoppingCartFooter($params)
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        if (! $this->isCustomerAllowed()) {
            return;
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');

        $customer_id = $this->context->customer->id;
        $customer_points = AerpointsCustomer::getPointBalance($customer_id);

        // Customer point minimum redemption
        $min_redemption = (int) Configuration::get('AERPOINTS_MIN_REDEMPTION');
        if ($customer_points < $min_redemption) {
            return; // Not enough points to redeem
        }

        // Check for active AerPoints cart rules in current cart
        $redeemed_points = 0;
        $redeemed_discount = 0;
        $point_value = (int) Configuration::get('AERPOINTS_POINT_VALUE', 100);

        // Check current cart rules for AerPoints redemptions
        $cart_rules = $this->context->cart->getCartRules();
        foreach ($cart_rules as $cart_rule) {
            if (strpos($cart_rule['code'], 'AERPOINTS_') === 0) {
                // Extract points from cart rule code: AERPOINTS_customerid_points_timestamp
                preg_match('/AERPOINTS_\d+_(\d+)_\d+/', $cart_rule['code'], $matches);
                if (isset($matches[1])) {
                    $redeemed_points = (int) $matches[1];
                    $redeemed_discount = $cart_rule['value_real'];
                    break; // Assume only one AerPoints rule per cart
                }
            }
        }

        $this->context->smarty->assign(array(
            'customer_points' => $customer_points,
            'redeemed_points' => $redeemed_points,
            'redeemed_discount' => Tools::displayPrice($redeemed_discount),
            'point_value' => $point_value,
            'min_redemption' => $min_redemption,
            'module_dir' => $this->_path,
        ));

        return $this->display(__FILE__, 'views/templates/hook/cart_redemption.tpl');
    }

    /**
     * Hook: displayProductListReviews
     * Show points badge in product lists
     */
    public function hookDisplayProductListReviews($params)
    {
        if (!Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        if (!$this->isCustomerAllowed()) {
            return;
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsProduct.php');

        $product = isset($params['product']) ? $params['product'] : null;
        if (!$product || !isset($product['id_product'])) {
            return;
        }

        $product_points = AerpointsProduct::getProductPoints($product['id_product']);
        if (!$product_points || (isset($product_points['active']) && $product_points['active'] == 0)) {
            return;
        }

        $null = null;
        $price_tax_excl = Product::getPriceStatic($product['id_product'], false, null, 6, null, false, true, 1, false, null, null, null, $null, true, true, null, true, false);
        //$price_tax_excl = isset($product['price_tax_exc']) ? $product['price_tax_exc'] : $product['price'];
        $calculated_points = AerpointsProduct::calculateProductPoints($product['id_product'], $price_tax_excl, 1);

        if ($calculated_points <= 0) {
            return;
        }

        $manufacturer_name = isset($product['manufacturer_name']) ? $product['manufacturer_name'] : $this->l('this brand');

        $this->context->smarty->assign(array(
            'points' => $calculated_points,
            'manufacturer' => $manufacturer_name,
            'product' => $product,
            'module_dir' => $this->_path,
        ));

        return $this->display(__FILE__, 'views/templates/hook/product_list_points.tpl');
    }

    /**
     * Hook: displayShoppingCart
     * Display points preview in cart summary
     */
    public function hookDisplayShoppingCart($params)
    {
        if (!Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        if (!$this->context->customer->isLogged()) {
            return;
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsProduct.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsRule.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsRuleCondition.php');

        $cart = $this->context->cart;
        $customer = $this->context->customer;

        // Calculate base product points
        $base_points = 0;
        foreach ($cart->getProducts() as $product) {
            $product_points = AerpointsProduct::getProductPoints($product['id_product']);
            if ($product_points && isset($product_points['active']) && $product_points['active'] == 1) {
                $null = null;
                $price_tax_excl = Product::getPriceStatic($product['id_product'], false, null, 6, null, false, true, 1, false, null, null, null, $null, true, true, null, true, false);
                //$price_tax_excl = Product::getPriceStatic($product['id_product'], false);
                $points = AerpointsProduct::calculateProductPoints(
                    $product['id_product'],
                    $price_tax_excl,
                    $product['quantity']
                );
                $base_points += $points;
            }
        }

        // Get applicable rules
        $rules = AerpointsRule::getActiveRules();
        $applicable_rules = array();
        $bonus_points = 0;
        $multiplier = 1.0;

        foreach ($rules as $rule) {
            if ($rule->isValid($cart, $customer)) {
                // Get quantity multiplier for product-based rules
                $qty_multiplier = $rule->getQuantityMultiplier($cart);

                if ($rule->action_type === 'bonus') {
                    $rule_bonus = (int)$rule->action_value * $qty_multiplier;
                    $bonus_points += $rule_bonus;
                    $applicable_rules[] = array(
                        'name' => $rule->name,
                        'type' => 'bonus',
                        'value' => $rule_bonus,
                        'base_value' => (int)$rule->action_value,
                        'quantity' => $qty_multiplier
                    );
                } elseif ($rule->action_type === 'multiplier') {
                    $multiplier *= (float)$rule->action_value;
                    $applicable_rules[] = array(
                        'name' => $rule->name,
                        'type' => 'multiplier',
                        'value' => (float)$rule->action_value
                    );
                }
            }
        }

        $total_points = (int)(($base_points * $multiplier) + $bonus_points);

        if ($total_points > 0) {
            $this->context->smarty->assign(array(
                'total_points' => $total_points,
                'base_points' => $base_points,
                'bonus_points' => $bonus_points,
                'multiplier' => $multiplier,
                'applicable_rules' => $applicable_rules,
                'module_dir' => $this->_path,
            ));

            return $this->display(__FILE__, 'views/templates/hook/cart_points_preview.tpl');
        }

        return;
    }

    /**
     * Hook: displayOrderDetail
     * Display points earned on order confirmation/detail page
     */
    public function hookDisplayOrderDetail($params)
    {
        if (!Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        if (!$this->context->customer->isLogged()) {
            return;
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsProduct.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsRule.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsRuleCondition.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsPending.php');

        $order = $params['order'];
        $customer = $this->context->customer;

        // Check if this order has pending points
        $pending = AerpointsPending::getOrderPending($order->id);

        if (!$pending) {
            return;
        }

        $points_to_earn = (int)$pending['points_to_earn'];

        if ($points_to_earn > 0) {
            $this->context->smarty->assign(array(
                'total_points' => $points_to_earn,
                'order_id' => $order->id,
                'points_status' => $pending['status'],
                'module_dir' => $this->_path,
            ));

            return $this->display(__FILE__, 'views/templates/hook/order_points_earned.tpl');
        }

        return;
    }

    /**
     * Hook: displayCustomerAccount
     * Add "My Points" link to customer account
     */
    public function hookDisplayCustomerAccount($params)
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        if (! $this->isCustomerAllowed()) {
            return;
        }

        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
        ));

        return $this->display(__FILE__, 'views/templates/hook/customer_account.tpl');
    }

    /**
     * Hook: displayAdminProductsExtra
     * Add points configuration fields to product edit page
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsProduct.php');

        $id_product = (int) Tools::getValue('id_product');
        $product_points = null;

        if ($id_product > 0) {
            $product_points = AerpointsProduct::getProductPoints($id_product);
        }

        $this->context->smarty->assign(array(
            'product_points' => $product_points,
            'id_product' => $id_product,
        ));

        return $this->display(__FILE__, 'views/templates/admin/product_points.tpl');
    }

    /**
     * Hook: actionProductUpdate
     * Save points configuration when product is updated
     */
    public function hookActionProductUpdate($params)
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        // Only process aerpoints data if aerpoints fields are present in the request
        if (! Tools::isSubmit('aerpoints_earn') && ! Tools::isSubmit('aerpoints_ratio')) {
            return;
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsProduct.php');

        $id_product = (int) $params['id_product'];
        $points_earn = (int) Tools::getValue('aerpoints_earn', 0);
        $points_ratio = (float) Tools::getValue('aerpoints_ratio', 0.00);
        $active = (bool) Tools::getValue('aerpoints_active', true);

        // Validate ratio
        if ($points_ratio > 100) {
            $points_ratio = 100;
        }

        // Save if either fixed points or ratio is set
        if ($points_earn > 0 || $points_ratio > 0) {
            AerpointsProduct::setProductPoints($id_product, $points_earn, $active, $points_ratio);
        } else {
            // Remove points configuration if both values are 0
            AerpointsProduct::deleteProductPoints($id_product);
        }
    }

    /**
     * Hook: actionAdminProductsListingFieldsModifier
     * Add points earned column to admin products list
     */
    public function hookActionAdminProductsListingFieldsModifier($params)
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        // Add points_earned, points_ratio and ap_active fields to the listing
        $params['select'] .= ', COALESCE(ap.points_earn, 0) as points_earned, COALESCE(ap.points_ratio, 0) as points_ratio, COALESCE(ap.active, 0) as aerpoints_active';
        $params['join'] .= ' LEFT JOIN `'._DB_PREFIX_.'aerpoints_product` ap ON (a.`id_product` = ap.`id_product`)';

        // Add the field to the fields list for display
        $params['fields']['points_earned'] = array(
            'title' => $this->l('Points Earned').' <img src="'.$this->_path.'views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;" />',
            'align' => 'text-center',
            'class' => 'fixed-width-xs',
            'callback' => 'formatPointsEarned',
            'callback_object' => $this
        );
    }

    /**
     * Callback to format points earned display
     * Shows fixed points or ratio based on configuration
     */
    public function formatPointsEarned($value, $row)
    {
        $points = (int) $value;
        $ratio = isset($row['points_ratio']) ? (float) $row['points_ratio'] : 0;
        $active = isset($row['aerpoints_active']) ? (int) $row['aerpoints_active'] : 0;

        if ($active !== 1) {
            return '<span style="color: #aaa;">Disabled</span>';
        }

        $icon = '<img src="'.$this->_path.'views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;" />';

        if ($points > 0) {
            return $points.' '.$icon.' <small>(fixed)</small>';
        } elseif ($ratio > 0) {
            return $ratio.'× <small>(ratio)</small>';
        }

        return '-';
    }

    /**
     * Hook: displayAdminOrder
     * Show points information in admin order detail page
     */
    public function hookDisplayAdminOrder($params)
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsPending.php');

        $order = new Order($params['id_order']);

        // Get points history for this order
        $order_history = AerpointsHistory::getOrderHistory($order->id);

        // Get pending points for this order
        $pending_points = AerpointsPending::getOrderPending($order->id);

        $this->context->smarty->assign(array(
            'order_history' => $order_history,
            'pending_points' => $pending_points,
            'order_id' => $order->id,
        ));

        return $this->display(__FILE__, 'views/templates/admin/order_points.tpl');
    }

    /**
     * Hook: displayAdminCustomers
     * Show points information in admin customer detail page
     */
    public function hookDisplayAdminCustomers($params)
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');

        $customer = new Customer($params['id_customer']);

        // Get customer points balance
        $customer_points = AerpointsCustomer::getPointBalance($customer->id);

        // Get customer points history
        $customer_history = AerpointsHistory::getCustomerHistory($customer->id, 10); // Last 10 transactions

        $this->context->smarty->assign(array(
            'customer_points' => $customer_points,
            'customer_history' => $customer_history,
            'customer_id' => $customer->id,
            'ajax_url' => $this->context->link->getAdminLink('AdminModules', false)
                .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
        ));

        return $this->display(__FILE__, 'views/templates/admin/customer_points.tpl');
    }

    /**
     * Install admin tab for product points management
     */
    private function installTab()
    {
        // Create main Product Points tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAerpointsProduct';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'AerPoints Products';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->module = $this->name;
        $result = $tab->add();

        // Create Points Rules tab
        $tab_rules = new Tab();
        $tab_rules->active = 1;
        $tab_rules->class_name = 'AdminAerpointsRules';
        $tab_rules->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab_rules->name[$lang['id_lang']] = 'Points Rules';
        }
        $tab_rules->id_parent = (int) Tab::getIdFromClassName('AdminPriceRule');
        $tab_rules->module = $this->name;
        $result = $result && $tab_rules->add();

        // Create Gift Catalog tab
        $tab_gifts = new Tab();
        $tab_gifts->active = 1;
        $tab_gifts->class_name = 'AdminAerpointsGifts';
        $tab_gifts->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab_gifts->name[$lang['id_lang']] = 'AerPoints Gifts';
        }
        $tab_gifts->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab_gifts->module = $this->name;
        $result = $result && $tab_gifts->add();

        // Create Gift Orders tab
        $tab_gift_orders = new Tab();
        $tab_gift_orders->active = 1;
        $tab_gift_orders->class_name = 'AdminAerpointsGiftOrders';
        $tab_gift_orders->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab_gift_orders->name[$lang['id_lang']] = 'AerPoints Gift Orders';
        }
        $tab_gift_orders->id_parent = (int) Tab::getIdFromClassName('AdminParentOrders');
        $tab_gift_orders->module = $this->name;
        $result = $result && $tab_gift_orders->add();

        return $result;
    }

    /**
     * Uninstall admin tab
     */
    private function uninstallTab()
    {
        $result = true;

        // Remove Product Points tab
        $id_tab = (int) Tab::getIdFromClassName('AdminAerpointsProduct');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $result = $result && $tab->delete();
        }

        // Remove Points Rules tab
        $id_tab_rules = (int) Tab::getIdFromClassName('AdminAerpointsRules');
        if ($id_tab_rules) {
            $tab_rules = new Tab($id_tab_rules);
            $result = $result && $tab_rules->delete();
        }

        // Remove Gift Catalog tab
        $id_tab_gifts = (int) Tab::getIdFromClassName('AdminAerpointsGifts');
        if ($id_tab_gifts) {
            $tab_gifts = new Tab($id_tab_gifts);
            $result = $result && $tab_gifts->delete();
        }

        // Remove Gift Orders tab
        $id_tab_gift_orders = (int) Tab::getIdFromClassName('AdminAerpointsGiftOrders');
        if ($id_tab_gift_orders) {
            $tab_gift_orders = new Tab($id_tab_gift_orders);
            $result = $result && $tab_gift_orders->delete();
        }

        return $result;
    }

    public function ajaxProcessAdjustPoints()
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            die(json_encode(array('success' => false, 'error' => 'Module is disabled')));
        }

        try {
            // Get POST data
            $action = Tools::getValue('adjust_type');
            $customer_id = (int) Tools::getValue('customer_id');
            $points = (int) Tools::getValue('points');

            // Validate input
            if (! in_array($action, array('add', 'remove'))) {
                throw new Exception('Invalid action');
            }

            if ($customer_id <= 0) {
                throw new Exception('Invalid customer ID');
            }

            if ($points <= 0) {
                throw new Exception('Points must be greater than 0');
            }

            // Include necessary classes
            include_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
            include_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');

            // Adjust points based on action
            if ($action == 'add') {
                $result = AerpointsCustomer::addPoints($customer_id, $points, AerpointsHistory::TYPE_MANUAL_ADD);
                $type = AerpointsHistory::TYPE_MANUAL_ADD;
                $description = 'Manual addition by admin';
            } else {
                $result = AerpointsCustomer::removePoints($customer_id, $points, AerpointsHistory::TYPE_MANUAL_REMOVE);
                $type = AerpointsHistory::TYPE_MANUAL_REMOVE;
                $description = 'Manual removal by admin';
                $points = -$points; // Make negative for history
            }

            if ($result) {
                // Add to history
                //AerpointsHistory::addHistoryEntry($customer_id, $points, $type, $description);

                // Get updated balance
                $new_balance = AerpointsCustomer::getPointBalance($customer_id);

                echo json_encode(array(
                    'success' => true,
                    'message' => 'Points adjusted successfully',
                    'new_balance' => $new_balance,
                    'points_adjusted' => abs($points),
                    'action' => $action
                ));
            } else {
                throw new Exception('Failed to adjust points');
            }

        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Log cart rule completion for order
     */
    private function logCartRuleCompletion($id_order)
    {
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');

        // Get order cart rules
        $order_cart_rules = Db::getInstance()->executeS('
            SELECT ocr.id_cart_rule
            FROM '._DB_PREFIX_.'order_cart_rule ocr
            WHERE ocr.id_order = '.(int) $id_order);

        foreach ($order_cart_rules as $cart_rule_data) {
            // Update existing history entry with order ID
            AerpointsHistory::updateCartRuleOrderId(
                (int) $cart_rule_data['id_cart_rule'],
                $id_order
            );
        }
    }
}
