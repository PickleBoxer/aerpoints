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

require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsProduct.php');

class AdminAerpointsProductController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'aerpoints_product';
        $this->className = 'AerpointsProduct';
        $this->lang = false;
        $this->deleted = false;
        $this->colorOnBackground = false;
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?')
            )
        );

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        parent::__construct();

        $this->fields_list = $this->getFieldsList();

        $this->_select = 'pl.name as product_name';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (a.`id_product` = pl.`id_product` AND pl.`id_lang` = '.(int) $this->context->language->id.')';
        $this->_where = 'AND (a.points_earn > 0 OR a.points_buy > 0)';
    }

    /**
     * Define fields list configuration
     */
    private function getFieldsList()
    {
        return array(
            'id_product' => array(
                'title' => $this->l('Product ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'product_name' => array(
                'title' => $this->l('Product Name'),
                'width' => 'auto',
                'filter_key' => 'pl!name'
            ),
            'points_earn' => array(
                'title' => $this->l('Points Earned'),
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ),
            'points_buy' => array(
                'title' => $this->l('Points to Buy'),
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ),
            'date_upd' => array(
                'title' => $this->l('Last Updated'),
                'align' => 'center',
                'type' => 'datetime'
            )
        );
    }

    /**
     * Override renderList to add content after the product table
     */
    public function renderList()
    {
        return parent::renderList() . $this->renderCombinedCard();
    }

    /**
     * Render combined explanation and product selection card
     */
    private function renderCombinedCard()
    {
        // Get products for the dropdown
        $products = Product::getProducts($this->context->language->id, 0, 0, 'name', 'ASC');
        $this->context->smarty->assign('products', $products);
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_.'aerpoints/views/templates/admin/product_configuration_panel.tpl');
    }

    public function renderForm()
    {
        if (! ($obj = $this->loadObject(true))) {
            return;
        }

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Product Points Configuration'),
                'icon' => 'icon-star'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Product'),
                    'name' => 'id_product',
                    'required' => true,
                    'options' => array(
                        'query' => Product::getProducts($this->context->language->id, 0, 0, 'name', 'ASC'),
                        'id' => 'id_product',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Points Earned'),
                    'name' => 'points_earn',
                    'class' => 'fixed-width-sm',
                    'desc' => $this->l('Points customer earns when buying this product')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Points to Buy'),
                    'name' => 'points_buy',
                    'class' => 'fixed-width-sm',
                    'desc' => $this->l('Points required to purchase this product (0 = cannot buy with points)')
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );


        // Always show product dropdown for new entries
        // For editing, disable the dropdown to prevent changing product
        if ($obj->id) {
            // Find the product select input and set 'disabled' => true
            foreach ($this->fields_form['input'] as &$input) {
                if ($input['name'] === 'id_product') {
                    $input['disabled'] = true;
                    break;
                }
            }
        }

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd'.$this->table)) {
            // Try to get id_product from POST, fallback to object if editing
            $id_product = (int) Tools::getValue('id_product');
            $points_earn = (int) Tools::getValue('points_earn');
            $points_buy = (int) Tools::getValue('points_buy');

            // If editing and id_product is missing (input disabled), get from object
            if (! $id_product && Tools::getValue('id_aerpoints_product')) {
                $obj = $this->loadObject(true);
                if ($obj && isset($obj->id_product)) {
                    $id_product = (int) $obj->id_product;
                }
            }

            if ($points_earn < 0 || $points_buy < 0) {
                $this->errors[] = Tools::displayError($this->l('Points values cannot be negative'));
                return false;
            }

            if ($points_earn == 0 && $points_buy == 0) {
                $this->errors[] = Tools::displayError($this->l('At least one points value must be greater than 0'));
                return false;
            }

            if (! Product::existsInDatabase($id_product, 'product')) {
                $this->errors[] = Tools::displayError($this->l('Invalid product selected'));
                return false;
            }

            // Check if product already has points configuration
            $existing = AerpointsProduct::getProductPoints($id_product);

            if ($existing && ! Tools::getValue('id_aerpoints_product')) {
                $this->errors[] = Tools::displayError($this->l('This product already has points configuration. Please edit the existing entry.'));
                return false;
            }

            AerpointsProduct::setProductPoints($id_product, $points_earn, $points_buy);

            $this->confirmations[] = $this->l('Product points configuration saved successfully');
            return true;
        }

        return parent::postProcess();
    }

    public function processBulkDelete()
    {
        $ids = Tools::getValue($this->table.'Box');
        if (is_array($ids) && count($ids)) {
            foreach ($ids as $id) {
                $product_points = new AerpointsProduct((int) $id);
                if (Validate::isLoadedObject($product_points)) {
                    AerpointsProduct::deleteProductPoints($product_points->id_product);
                }
            }
            $this->confirmations[] = sprintf($this->l('%d product(s) points configuration deleted'), count($ids));
        }
    }

    public function processDelete()
    {
        $id = (int) Tools::getValue('id_aerpoints_product');
        $product_points = new AerpointsProduct($id);

        if (Validate::isLoadedObject($product_points)) {
            AerpointsProduct::deleteProductPoints($product_points->id_product);
            $this->confirmations[] = $this->l('Product points configuration deleted successfully');
        } else {
            $this->errors[] = Tools::displayError($this->l('An error occurred while deleting the object.'));
        }
    }
}
