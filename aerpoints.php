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
        $this->version = '1.0.0';
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
            $this->installTab();
    }

    public function uninstall()
    {
        Configuration::deleteByName('AERPOINTS_ENABLED');
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
            'AERPOINTS_POINT_VALUE' => (int) Tools::getValue('AERPOINTS_POINT_VALUE', Configuration::get('AERPOINTS_POINT_VALUE')),
            'AERPOINTS_MIN_REDEMPTION' => (int) Tools::getValue('AERPOINTS_MIN_REDEMPTION', Configuration::get('AERPOINTS_MIN_REDEMPTION')),
            'AERPOINTS_PARTIAL_PAYMENT' => (bool) Tools::getValue('AERPOINTS_PARTIAL_PAYMENT', Configuration::get('AERPOINTS_PARTIAL_PAYMENT')),
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
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
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

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsPending.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsProduct.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');

        $order = $params['order'];
        $cart = $params['cart'];

        $total_points_to_earn = 0;
        $total_points_redeemed = 0;

        // Calculate points to earn from products in cart
        foreach ($cart->getProducts() as $product) {
            $product_points = AerpointsProduct::getProductPoints($product['id_product']);
            if ($product_points && $product_points['points_earn'] > 0) {
                $total_points_to_earn += $product_points['points_earn'] * $product['quantity'];
            }
        }

        // Handle point redemption from session
        if (isset($this->context->cookie->aerpoints_redeem) && $this->context->cookie->aerpoints_redeem > 0) {
            $total_points_redeemed = (int) $this->context->cookie->aerpoints_redeem;

            // Deduct points from customer immediately
            AerpointsCustomer::removeCustomerPoints($order->id_customer, $total_points_redeemed);

            // Add history entry
            AerpointsHistory::addHistoryEntry(
                $order->id_customer,
                -$total_points_redeemed,
                AerpointsHistory::TYPE_REDEEMED,
                'Points redeemed for order #'.$order->id
            );

            // Clear redemption from session
            unset($this->context->cookie->aerpoints_redeem);
            $this->context->cookie->write();
        }

        // Create pending points entry for earned points
        if ($total_points_to_earn > 0) {
            AerpointsPending::createPendingEntry(
                $order->id,
                $order->id_customer,
                $total_points_to_earn,
                0 // Redeemed points are already processed above
            );
        }
    }

    /**
     * Hook: actionOrderStatusPostUpdate
     * Called when order status changes - process pending points
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        if (! Configuration::get('AERPOINTS_ENABLED')) {
            return;
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsPending.php');

        $order = new Order($params['id_order']);
        $new_status = $params['newOrderStatus'];

        // Check if order is completed (payment accepted)
        if ($new_status->paid == 1) {
            AerpointsPending::completePendingPoints($order->id);
        }
        // Check if order is cancelled
        elseif ($new_status->id == Configuration::get('PS_OS_CANCELED')) {
            AerpointsPending::cancelPendingPoints($order->id);
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

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsProduct.php');

        $product = $params['product'];
        if (! $product) {
            return;
        }

        $product_points = AerpointsProduct::getProductPoints($product->id);
        if (! $product_points || (! $product_points['points_earn'] && ! $product_points['points_buy'])) {
            return;
        }

        $this->context->smarty->assign(array(
            'product_points' => $product_points,
            'point_value' => Configuration::get('AERPOINTS_POINT_VALUE', 100),
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

        if (! $this->context->customer->isLogged()) {
            return;
        }

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');

        $customer_id = $this->context->customer->id;
        //$customer_points = AerpointsCustomer::getCustomerPoints($customer_id);
        $customer_points = AerpointsCustomer::getPointBalance($customer_id);

        // Check if points are being redeemed (from session)
        $redeemed_points = (int) $this->context->cookie->aerpoints_redeem;
        $redeemed_discount = $redeemed_points * (Configuration::get('AERPOINTS_POINT_VALUE', 100) / 100);

        $this->context->smarty->assign(array(
            'customer_points' => $customer_points,
            'redeemed_points' => $redeemed_points,
            'redeemed_discount' => Tools::displayPrice($redeemed_discount),
        ));

        return $this->display(__FILE__, 'views/templates/hook/cart_redemption.tpl');
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

        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsProduct.php');

        $id_product = (int) $params['id_product'];
        $points_earn = (int) Tools::getValue('aerpoints_earn');
        $points_buy = (int) Tools::getValue('aerpoints_buy');

        // Only save if at least one point value is set
        if ($points_earn > 0 || $points_buy > 0) {
            AerpointsProduct::setProductPoints($id_product, $points_earn, $points_buy);
        } else {
            // Remove points configuration if both values are 0
            AerpointsProduct::deleteProductPoints($id_product);
        }
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
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAerpointsProduct';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'AerPoints Products';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->module = $this->name;
        return $tab->add();
    }

    /**
     * Uninstall admin tab
     */
    private function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminAerpointsProduct');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
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
}
