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

class AerpointsGiftOrder extends ObjectModel
{
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public $id_aerpoints_gift_order;
    public $id_customer;
    public $id_aerpoints_gift;
    public $gift_name;
    public $points_spent;
    public $status;
    public $customer_notes;
    public $admin_notes;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'aerpoints_gift_order',
        'primary' => 'id_aerpoints_gift_order',
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_aerpoints_gift' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'gift_name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),
            'points_spent' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'status' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 20),
            'customer_notes' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'admin_notes' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Get valid statuses
     *
     * @return array
     */
    public static function getValidStatuses()
    {
        return array(
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED
        );
    }

    /**
     * Get status labels
     *
     * @return array
     */
    public static function getStatusLabels()
    {
        return array(
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        );
    }

    /**
     * Get status label for current order
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = self::getStatusLabels();
        return isset($labels[$this->status]) ? $labels[$this->status] : $this->status;
    }

    /**
     * Get customer's gift orders
     *
     * @param int $id_customer Customer ID
     * @param int|null $id_lang Language ID
     * @return array Array of gift orders
     */
    public static function getCustomerOrders($id_customer, $id_lang = null)
    {
        $sql = 'SELECT go.*, g.image
                FROM `' . _DB_PREFIX_ . 'aerpoints_gift_order` go
                LEFT JOIN `' . _DB_PREFIX_ . 'aerpoints_gift` g 
                    ON go.`id_aerpoints_gift` = g.`id_aerpoints_gift`
                WHERE go.`id_customer` = ' . (int)$id_customer . '
                ORDER BY go.`date_add` DESC';

        $orders = Db::getInstance()->executeS($sql);
        
        if (!$orders) {
            return array();
        }

        // Add additional info
        foreach ($orders as &$order) {
            $order['status_label'] = self::getStatusLabelStatic($order['status']);
            $order['can_cancel'] = ($order['status'] === self::STATUS_PENDING);
            $order['image_path'] = AerpointsGift::getGiftImagePath($order['id_aerpoints_gift'], $order['image']);
        }

        return $orders;
    }

    /**
     * Get all gift orders with optional filters
     *
     * @param array $filters Filters (status, date_from, date_to)
     * @return array Array of gift orders
     */
    public static function getAllOrders($filters = array())
    {
        $where = array();
        
        if (isset($filters['status']) && $filters['status']) {
            $where[] = 'go.`status` = \'' . pSQL($filters['status']) . '\'';
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $where[] = 'go.`date_add` >= \'' . pSQL($filters['date_from']) . '\'';
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $where[] = 'go.`date_add` <= \'' . pSQL($filters['date_to']) . ' 23:59:59\'';
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = 'SELECT go.*, 
                       CONCAT(c.`firstname`, \' \', c.`lastname`) as customer_name,
                       c.`email` as customer_email
                FROM `' . _DB_PREFIX_ . 'aerpoints_gift_order` go
                LEFT JOIN `' . _DB_PREFIX_ . 'customer` c 
                    ON go.`id_customer` = c.`id_customer`
                ' . $where_clause . '
                ORDER BY go.`date_add` DESC';

        $orders = Db::getInstance()->executeS($sql);
        
        if (!$orders) {
            return array();
        }

        foreach ($orders as &$order) {
            $order['status_label'] = self::getStatusLabelStatic($order['status']);
        }

        return $orders;
    }

    /**
     * Check if gift has active orders
     *
     * @param int $id_gift Gift ID
     * @return bool
     */
    public static function hasActiveOrders($id_gift)
    {
        $sql = 'SELECT COUNT(*)
                FROM `' . _DB_PREFIX_ . 'aerpoints_gift_order`
                WHERE `id_aerpoints_gift` = ' . (int)$id_gift . '
                AND `status` IN (\'' . self::STATUS_PENDING . '\', \'' . self::STATUS_PROCESSING . '\')';

        return (int)Db::getInstance()->getValue($sql) > 0;
    }

    /**
     * Update order status
     *
     * @param string $new_status New status
     * @return bool Success status
     */
    public function updateStatus($new_status)
    {
        // Validate status
        if (!in_array($new_status, self::getValidStatuses())) {
            return false;
        }

        $old_status = $this->status;
        $this->status = $new_status;
        $this->date_upd = date('Y-m-d H:i:s');

        // If cancelling order, restore points and stock
        if ($new_status === self::STATUS_CANCELLED && $old_status !== self::STATUS_CANCELLED) {
            require_once dirname(__FILE__) . '/AerpointsCustomer.php';
            require_once dirname(__FILE__) . '/AerpointsGift.php';
            require_once dirname(__FILE__) . '/AerpointsHistory.php';

            // Restore points to customer
            AerpointsCustomer::addPoints(
                $this->id_customer,
                $this->points_spent,
                'Gift order cancelled: ' . $this->gift_name
            );

            // Restore stock
            $gift = new AerpointsGift($this->id_aerpoints_gift);
            if (Validate::isLoadedObject($gift)) {
                $gift->incrementStock();
            }

            // Log refund in history
            AerpointsHistory::addHistoryEntry(
                $this->id_customer,
                $this->points_spent,
                AerpointsHistory::TYPE_REFUND,
                'Gift order #' . $this->id . ' cancelled: ' . $this->gift_name,
                null,
                null
            );
        }

        return $this->update();
    }

    /**
     * Check if order can be cancelled by customer
     *
     * @return bool
     */
    public function canBeCancelledByCustomer()
    {
        return ($this->status === self::STATUS_PENDING);
    }

    /**
     * Get status label (static)
     *
     * @param string $status Status
     * @return string
     */
    private static function getStatusLabelStatic($status)
    {
        $labels = self::getStatusLabels();
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
}
