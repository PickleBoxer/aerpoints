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

class AerpointsPending extends ObjectModel
{
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public $id_aerpoints_pending;
    public $id_customer;
    public $id_order;
    public $points_to_earn;
    public $points_redeemed;
    public $status;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'aerpoints_pending',
        'primary' => 'id_aerpoints_pending',
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'points_to_earn' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'points_redeemed' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'status' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Get valid statuses
     */
    public static function getValidStatuses()
    {
        return array(
            self::STATUS_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED
        );
    }

    /**
     * Check if status is valid
     */
    public function isValidStatus($status)
    {
        return in_array($status, self::getValidStatuses());
    }

    /**
     * Get status labels
     */
    public static function getStatusLabels()
    {
        return array(
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        );
    }

    /**
     * Get status label
     */
    public function getStatusLabel()
    {
        $labels = self::getStatusLabels();
        return isset($labels[$this->status]) ? $labels[$this->status] : $this->status;
    }

    /**
     * Create pending points entry for an order
     */
    public static function createPendingEntry($id_order, $id_customer, $points_to_earn = 0, $points_redeemed = 0)
    {
        // Check if entry already exists
        $existing = self::getPendingByOrder($id_order);
        if ($existing) {
            return false; // Entry already exists
        }

        $pending = new AerpointsPending();
        $pending->id_order = $id_order;
        $pending->id_customer = $id_customer;
        $pending->points_to_earn = $points_to_earn;
        $pending->points_redeemed = $points_redeemed;
        $pending->status = self::STATUS_PENDING;
        $pending->date_add = date('Y-m-d H:i:s');
        $pending->date_upd = date('Y-m-d H:i:s');

        return $pending->add();
    }

    /**
     * Get pending points by order ID
     */
    public static function getPendingByOrder($id_order)
    {
        $sql = 'SELECT * 
                FROM ' . _DB_PREFIX_ . 'aerpoints_pending 
                WHERE id_order = ' . (int)$id_order;
        
        return Db::getInstance()->getRow($sql);
    }

    /**
     * Complete pending points - award points to customer
     */
    public static function completePendingPoints($id_order)
    {
        $pending = self::getPendingByOrder($id_order);
        if (!$pending || $pending['status'] !== self::STATUS_PENDING) {
            return false;
        }

        $result = true;

        // Award points to customer if any
        if ($pending['points_to_earn'] > 0) {
            require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
            $result = AerpointsCustomer::addPoints(
                $pending['id_customer'],
                $pending['points_to_earn'],
                null,
                'Points earned from order #' . $id_order,
                $id_order
            );
        }

        if ($result) {
            // Update status to completed
            $sql = 'UPDATE ' . _DB_PREFIX_ . 'aerpoints_pending 
                    SET status = \'' . self::STATUS_COMPLETED . '\',
                        date_upd = NOW()
                    WHERE id_order = ' . (int)$id_order;
            
            return Db::getInstance()->execute($sql);
        }

        return false;
    }

    /**
     * Cancel pending points - restore redeemed points to customer
     */
    public static function cancelPendingPoints($id_order)
    {
        $pending = self::getPendingByOrder($id_order);
        if (!$pending) {
            return false;
        }

        $result = true;

        // Restore redeemed points to customer if any
        if ($pending['points_redeemed'] > 0) {
            require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsCustomer.php');
            $result = AerpointsCustomer::addPoints(
                $pending['id_customer'],
                $pending['points_redeemed'],
                null,
                'Points refunded from cancelled order #' . $id_order,
                $id_order
            );

            // Log refund in history
            if ($result) {
                require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsHistory.php');
                AerpointsHistory::addHistoryEntry(
                    $pending['id_customer'],
                    $pending['points_redeemed'],
                    AerpointsHistory::TYPE_REFUND,
                    'Points refunded from cancelled order #' . $id_order,
                    $id_order
                );
            }
        }

        if ($result) {
            // Update status to cancelled
            $sql = 'UPDATE ' . _DB_PREFIX_ . 'aerpoints_pending 
                    SET status = \'' . self::STATUS_CANCELLED . '\',
                        date_upd = NOW()
                    WHERE id_order = ' . (int)$id_order;
            
            return Db::getInstance()->execute($sql);
        }

        return false;
    }

    /**
     * Get customer's pending points
     */
    public static function getCustomerPendingPoints($id_customer)
    {
        $sql = 'SELECT * 
                FROM ' . _DB_PREFIX_ . 'aerpoints_pending 
                WHERE id_customer = ' . (int)$id_customer . '
                AND status = \'' . self::STATUS_PENDING . '\'
                ORDER BY date_add DESC';
        
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get pending points for a specific order
     */
    public static function getOrderPending($id_order)
    {
        $sql = 'SELECT * 
                FROM ' . _DB_PREFIX_ . 'aerpoints_pending 
                WHERE id_order = ' . (int)$id_order . '
                ORDER BY date_add DESC';
        
        return Db::getInstance()->executeS($sql);
    }
}
