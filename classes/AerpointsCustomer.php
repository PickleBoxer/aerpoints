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

class AerpointsCustomer extends ObjectModel
{
    public $id_aerpoints_customer;
    public $id_customer;
    public $available_points;
    public $total_earned;
    public $total_redeemed;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'aerpoints_customer',
        'primary' => 'id_aerpoints_customer',
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'available_points' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'total_earned' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'total_redeemed' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Get customer's point balance
     */
    public static function getPointBalance($id_customer)
    {
        $sql = 'SELECT available_points 
                FROM ' . _DB_PREFIX_ . 'aerpoints_customer 
                WHERE id_customer = ' . (int)$id_customer;
        
        $result = Db::getInstance()->getValue($sql);
        return $result ? (int)$result : 0;
    }

    /**
     * Get customer's complete point record
     */
    public static function getCustomerRecord($id_customer)
    {
        $sql = 'SELECT * 
                FROM ' . _DB_PREFIX_ . 'aerpoints_customer 
                WHERE id_customer = ' . (int)$id_customer;
        
        return Db::getInstance()->getRow($sql);
    }

    /**
     * Add points to customer
     */
    public static function addPoints($id_customer, $points, $type = null, $description = '', $id_order = null)
    {
        if ($points <= 0) {
            throw new Exception('Points to add must be greater than zero.');
        }

        // Get or create customer record
        $customer_record = self::getCustomerRecord($id_customer);
        
        if (!$customer_record) {
            // Create new customer record
            $aerpoints_customer = new AerpointsCustomer();
            $aerpoints_customer->id_customer = $id_customer;
            $aerpoints_customer->available_points = $points;
            $aerpoints_customer->total_earned = $points;
            $aerpoints_customer->total_redeemed = 0;
            $aerpoints_customer->date_add = date('Y-m-d H:i:s');
            $aerpoints_customer->date_upd = date('Y-m-d H:i:s');
            $result = $aerpoints_customer->add();
        } else {
            // Update existing customer record
            $sql = 'UPDATE ' . _DB_PREFIX_ . 'aerpoints_customer 
                    SET available_points = available_points + ' . (int)$points . ',
                        total_earned = total_earned + ' . (int)$points . ',
                        date_upd = NOW()
                    WHERE id_customer = ' . (int)$id_customer;
            $result = Db::getInstance()->execute($sql);
        }

        if ($result) {
            // Log the transaction
            require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');
            AerpointsHistory::addHistoryEntry(
                $id_customer, 
                $points, 
                $type ?? AerpointsHistory::TYPE_EARNED, 
                $description ?: 'Points earned',
                $id_order
            );
        }

        return $result;
    }

    /**
     * Remove points from customer
     */
    public static function removePoints($id_customer, $points, $type = null, $description = '', $id_order = null, $id_cart_rule = null)
    {
        if ($points <= 0) {
            throw new Exception('Points to remove must be greater than zero.');
        }

        $available_points = self::getPointBalance($id_customer);
        if ($available_points < $points) {
            throw new Exception('Insufficient points.');
        }

        $sql = 'UPDATE ' . _DB_PREFIX_ . 'aerpoints_customer 
                SET available_points = available_points - ' . (int)$points . ',
                    total_redeemed = total_redeemed + ' . (int)$points . ',
                    date_upd = NOW()
                WHERE id_customer = ' . (int)$id_customer;
        
        $result = Db::getInstance()->execute($sql);

        if ($result) {
            // Log the transaction
            require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');
            AerpointsHistory::addHistoryEntry(
                $id_customer, 
                -$points, 
                $type ?? AerpointsHistory::TYPE_REDEEMED, 
                $description ?: 'Points redeemed',
                $id_order,
                $id_cart_rule
            );
        }

        return $result;
    }

    /**
     * Get customer's point history
     */
    public static function getPointsHistory($id_customer, $limit = 50)
    {
        require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');
        return AerpointsHistory::getCustomerHistory($id_customer, $limit);
    }
}
