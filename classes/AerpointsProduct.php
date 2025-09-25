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

class AerpointsProduct extends ObjectModel
{
    public $id_aerpoints_product;
    public $id_product;
    public $points_earn;
    public $active;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'aerpoints_product',
        'primary' => 'id_aerpoints_product',
        'fields' => array(
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'points_earn' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Get point configuration for a product
     */
    public static function getProductPoints($id_product)
    {
        $sql = 'SELECT * 
                FROM ' . _DB_PREFIX_ . 'aerpoints_product 
                WHERE id_product = ' . (int)$id_product . '';
        
        return Db::getInstance()->getRow($sql);
    }

    /**
     * Set point configuration for a product
     */
    public static function setProductPoints($id_product, $points_earn = 0, $active = true)
    {
        $existing = self::getProductPoints($id_product);
        
        if ($existing) {
            // Update existing record
            $sql = 'UPDATE ' . _DB_PREFIX_ . 'aerpoints_product 
                    SET points_earn = ' . (int)$points_earn . ',
                        active = ' . ($active ? 1 : 0) . ',
                        date_upd = NOW()
                    WHERE id_product = ' . (int)$id_product;
            
            return Db::getInstance()->execute($sql);
        } else {
            // Create new record
            $aerpoints_product = new AerpointsProduct();
            $aerpoints_product->id_product = $id_product;
            $aerpoints_product->points_earn = $points_earn;
            $aerpoints_product->active = $active;
            $aerpoints_product->date_add = date('Y-m-d H:i:s');
            $aerpoints_product->date_upd = date('Y-m-d H:i:s');
            
            return $aerpoints_product->add();
        }
    }

    /**
     * Get all products with point configuration
     */
    public static function getProductsWithPoints($active_only = true)
    {
        $sql = 'SELECT ap.*, p.name, pl.name as product_name
                FROM ' . _DB_PREFIX_ . 'aerpoints_product ap
                LEFT JOIN ' . _DB_PREFIX_ . 'product p ON ap.id_product = p.id_product
                LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . (int)Context::getContext()->language->id . ')';
        
        if ($active_only) {
            $sql .= ' WHERE ap.active = 1';
        }
        
        $sql .= ' ORDER BY ap.date_upd DESC';
        
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Check if product has points configured
     */
    public static function hasPoints($id_product)
    {
        $points = self::getProductPoints($id_product);
        return $points && $points['points_earn'] > 0;
    }

    /**
     * Delete product points configuration
     */
    public static function deleteProductPoints($id_product)
    {
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'aerpoints_product 
                WHERE id_product = ' . (int)$id_product;
        
        return Db::getInstance()->execute($sql);
    }

}
