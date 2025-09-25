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
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (a.`id_product` = pl.`id_product` AND pl.`id_lang` = '.(int) $this->context->language->id.' AND pl.`id_shop` = '.(int)$this->context->shop->id.')';
        $this->_where = 'AND a.points_earn > 0';
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
            'active' => array(
                'title' => $this->l('Active'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
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
     * Get products for current shop context
     */
    private function getProductsForCurrentShop()
    {
        $sql = new DbQuery();
        $sql->select('p.id_product, pl.name');
        $sql->from('product', 'p');
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . ' AND pl.id_shop = ' . (int)$this->context->shop->id);
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . (int)$this->context->shop->id);
        $sql->where('p.active = 1');
        $sql->orderBy('pl.name ASC');
        
        return Db::getInstance()->executeS($sql);
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
        // Get categories and manufacturers for filters
        $categories = Category::getCategories($this->context->language->id, true, false);
        $manufacturers = Manufacturer::getManufacturers(false, $this->context->language->id);
        
        $this->context->smarty->assign(array(
            'categories' => $categories,
            'manufacturers' => $manufacturers,
            'current' => self::$currentIndex,
            'token' => $this->token,
            'ajax_url' => self::$currentIndex . '&ajax=1&action=getFilteredProducts&token=' . $this->token
        ));
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_.'aerpoints/views/templates/admin/product_configuration_panel.tpl');
    }

    /**
     * Handle AJAX requests
     */
    public function ajaxProcess()
    {
        $action = Tools::getValue('action');
        
        switch ($action) {
            case 'getFilteredProducts':
                $this->ajaxProcessGetFilteredProducts();
                break;
            default:
                die(Tools::jsonEncode(array('error' => 'Invalid action')));
        }
    }

    /**
     * AJAX: Get filtered products
     */
    public function ajaxProcessGetFilteredProducts()
    {
        $search = trim(Tools::getValue('search', ''));
        $category_id = (int)Tools::getValue('category_id', 0);
        $manufacturer_id = (int)Tools::getValue('manufacturer_id', 0);
        $limit = (int)Tools::getValue('limit', 50); // Limit results to 50 by default
        
        $sql = new DbQuery();
        $sql->select('p.id_product, pl.name, p.reference, p.price, sa.quantity, m.name as manufacturer_name');
        $sql->from('product', 'p');
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . ' AND pl.id_shop = ' . (int)$this->context->shop->id);
        $sql->leftJoin('stock_available', 'sa', 'p.id_product = sa.id_product AND sa.id_product_attribute = 0');
        $sql->leftJoin('manufacturer', 'm', 'p.id_manufacturer = m.id_manufacturer');
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . (int)$this->context->shop->id);
        
        // Add category filter
        if ($category_id > 0) {
            $sql->leftJoin('category_product', 'cp', 'p.id_product = cp.id_product');
            $sql->where('cp.id_category = ' . $category_id);
        }
        
        // Add manufacturer filter
        if ($manufacturer_id > 0) {
            $sql->where('p.id_manufacturer = ' . $manufacturer_id);
        }
        
        // Add search filter
        if (!empty($search)) {
            $sql->where('(pl.name LIKE "%' . pSQL($search) . '%" OR p.reference LIKE "%' . pSQL($search) . '%")');
        }
        
        $sql->orderBy('pl.name ASC');
        $sql->limit($limit);
        
        $products = Db::getInstance()->executeS($sql);
        
        if (!$products) {
            $products = array();
        }
        
        // Check which products already have points configured
        foreach ($products as &$product) {
            $existing_points = AerpointsProduct::getProductPoints($product['id_product']);
            $product['has_points'] = $existing_points ? true : false;
            $product['current_points_earn'] = $existing_points ? $existing_points['points_earn'] : 0;
            $product['price'] = number_format($product['price'], 2);
            $product['quantity'] = (int)$product['quantity'];
        }
        
        die(Tools::jsonEncode(array('products' => $products)));
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
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0
                        )
                    ),
                    'is_bool' => true,
                    'class' => 'fixed-width-sm',
                    'desc' => $this->l('Enable or disable points for this product')
                ),
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
        // Handle bulk product configuration panel submission
        if (Tools::isSubmit('submitBulkAddaerpoints_product')) {
            $id_products = Tools::getValue('id_product');
            $points_earn = (int) Tools::getValue('points_earn');

            if (!is_array($id_products) || empty($id_products)) {
                $this->errors[] = Tools::displayError($this->l('Please select at least one product.'));
                return false;
            }

            if ($points_earn <= 0) {
                $this->errors[] = Tools::displayError($this->l('Points earned must be greater than 0'));
                return false;
            }

            $success_count = 0;
            $error_count = 0;
            $updated_count = 0;
            $created_count = 0;
            foreach ($id_products as $id_product) {
                $id_product = (int)$id_product;
                if (!Product::existsInDatabase($id_product, 'product')) {
                    $this->errors[] = Tools::displayError(sprintf($this->l('Invalid product selected: %d'), $id_product));
                    $error_count++;
                    continue;
                }
                $existing = AerpointsProduct::getProductPoints($id_product);
                if ($existing) {
                    // Update existing configuration
                    AerpointsProduct::setProductPoints($id_product, $points_earn);
                    $updated_count++;
                } else {
                    // Create new configuration
                    AerpointsProduct::setProductPoints($id_product, $points_earn);
                    $created_count++;
                }
            }
            if ($created_count) {
                $this->confirmations[] = sprintf($this->l('%d product(s) points configuration created'), $created_count);
            }
            if ($updated_count) {
                $this->confirmations[] = sprintf($this->l('%d product(s) points configuration updated'), $updated_count);
            }
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
