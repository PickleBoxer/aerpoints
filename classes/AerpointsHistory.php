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

class AerpointsHistory extends ObjectModel
{
    // Type constants
    const TYPE_EARNED = 'earned';
    const TYPE_REDEEMED = 'redeemed';
    const TYPE_MANUAL_ADD = 'manual_add';
    const TYPE_MANUAL_REMOVE = 'manual_remove';
    const TYPE_REFUND = 'refund';

    public $id_aerpoints_history;
    public $id_customer;
    public $id_order;
    public $id_cart_rule;
    public $points;
    public $type;
    public $description;
    public $date_add;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'aerpoints_history',
        'primary' => 'id_aerpoints_history',
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'),
            'id_cart_rule' => array('type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'),
            'points' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'type' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'description' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Get valid types
     */
    public static function getValidTypes()
    {
        return array(
            self::TYPE_EARNED,
            self::TYPE_REDEEMED,
            self::TYPE_MANUAL_ADD,
            self::TYPE_MANUAL_REMOVE,
            self::TYPE_REFUND
        );
    }

    /**
     * Check if type is valid
     */
    public function isValidType($type)
    {
        return in_array($type, self::getValidTypes());
    }

    /**
     * Get type labels
     */
    public static function getTypeLabels()
    {
        return array(
            self::TYPE_EARNED => 'Points Earned',
            self::TYPE_REDEEMED => 'Points Redeemed',
            self::TYPE_MANUAL_ADD => 'Manual Addition',
            self::TYPE_MANUAL_REMOVE => 'Manual Removal',
            self::TYPE_REFUND => 'Refund'
        );
    }

    /**
     * Get type label
     */
    public function getTypeLabel()
    {
        $labels = self::getTypeLabels();
        return isset($labels[$this->type]) ? $labels[$this->type] : $this->type;
    }

    /**
     * Add history entry
     */
    public static function addHistoryEntry($id_customer, $points, $type, $description = '', $id_order = null, $id_cart_rule = null)
    {
        // Validate type
        if (!in_array($type, self::getValidTypes())) {
            return false;
        }

        $history = new AerpointsHistory();
        $history->id_customer = $id_customer;
        $history->id_order = $id_order;
        $history->id_cart_rule = $id_cart_rule;
        $history->points = $points;
        $history->type = $type;
        $history->description = $description;
        $history->date_add = date('Y-m-d H:i:s');

        return $history->add();
    }

    /**
     * Get customer's history
     */
    public static function getCustomerHistory($id_customer, $limit = 50)
    {
        $sql = 'SELECT h.*, o.reference as order_reference, cr.code as cart_rule_code
                FROM ' . _DB_PREFIX_ . 'aerpoints_history h
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON h.id_order = o.id_order
                LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule cr ON h.id_cart_rule = cr.id_cart_rule
                WHERE h.id_customer = ' . (int)$id_customer . '
                ORDER BY h.date_add DESC';
        
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get history entries by cart rule ID
     */
    public static function getHistoryByCartRule($id_cart_rule)
    {
        $sql = 'SELECT h.*, o.reference as order_reference, cr.code as cart_rule_code
                FROM ' . _DB_PREFIX_ . 'aerpoints_history h
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON h.id_order = o.id_order
                LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule cr ON h.id_cart_rule = cr.id_cart_rule
                WHERE h.id_cart_rule = ' . (int)$id_cart_rule . '
                ORDER BY h.date_add DESC';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Update order ID for existing cart rule history entry
     */
    public static function updateCartRuleOrderId($id_cart_rule, $id_order)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'aerpoints_history 
                SET id_order = ' . (int)$id_order . '
                WHERE id_cart_rule = ' . (int)$id_cart_rule . ' 
                AND (id_order IS NULL OR id_order = 0)';
        
        return Db::getInstance()->execute($sql);
    }

    /**
     * Get points related to specific order
     */
    public static function getOrderHistory($id_order)
    {
        $sql = 'SELECT h.*, c.firstname, c.lastname
                FROM ' . _DB_PREFIX_ . 'aerpoints_history h
                LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON (h.id_customer = c.id_customer)
                WHERE (h.id_order = ' . (int)$id_order . ')
                ORDER BY h.date_add DESC';
        
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get all history with pagination
     */
    public static function getAllHistory($start = 0, $limit = 50, $id_customer = null)
    {
        $sql = 'SELECT h.*, c.firstname, c.lastname, c.email, o.reference as order_reference
                FROM ' . _DB_PREFIX_ . 'aerpoints_history h
                LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON h.id_customer = c.id_customer
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON h.id_order = o.id_order';
        
        if ($id_customer) {
            $sql .= ' WHERE h.id_customer = ' . (int)$id_customer;
        }
        
        $sql .= ' ORDER BY h.date_add DESC';
        
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int)$start . ', ' . (int)$limit;
        }
        
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get total points by type for customer
     */
    public static function getCustomerTotalsByType($id_customer)
    {
        $sql = 'SELECT type, SUM(points) as total_points
                FROM ' . _DB_PREFIX_ . 'aerpoints_history 
                WHERE id_customer = ' . (int)$id_customer . '
                GROUP BY type';
        
        $results = Db::getInstance()->executeS($sql);
        
        $totals = array();
        foreach ($results as $result) {
            $totals[$result['type']] = (int)$result['total_points'];
        }
        
        return $totals;
    }
}
