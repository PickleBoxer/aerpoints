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

require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsGiftOrder.php');
require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsGift.php');

class AdminAerpointsGiftOrdersController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'aerpoints_gift_order';
        $this->className = 'AerpointsGiftOrder';
        $this->identifier = 'id_aerpoints_gift_order';
        $this->lang = false;
        $this->deleted = false;
        $this->colorOnBackground = false;
        $this->allow_export = true;
        
        $this->addRowAction('view');

        parent::__construct();

        $this->fields_list = array(
            'id_aerpoints_gift_order' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'customer_name' => array(
                'title' => $this->l('Customer'),
                'width' => 'auto',
                'callback' => 'displayCustomerLink'
            ),
            'gift_name' => array(
                'title' => $this->l('Gift'),
                'width' => 'auto'
            ),
            'points_spent' => array(
                'title' => $this->l('Points'),
                'align' => 'center',
            ),
            'status' => array(
                'title' => $this->l('Status'),
                'align' => 'center',
                'callback' => 'displayStatus',
                'type' => 'select',
                'list' => AerpointsGiftOrder::getStatusLabels(),
                'filter_key' => 'a!status',
            ),
            'date_add' => array(
                'title' => $this->l('Redemption Date'),
                'align' => 'center',
                'type' => 'datetime',
            )
        );

        $this->_select = 'CONCAT(c.`firstname`, \' \', c.`lastname`) as customer_name';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'customer` c ON (a.`id_customer` = c.`id_customer`)';
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';
    }

    /**
     * Display customer link
     */
    public function displayCustomerLink($value, $row)
    {
        $customer_url = $this->context->link->getAdminLink('AdminCustomers') . '&id_customer=' . (int)$row['id_customer'] . '&viewcustomer';
        return '<a href="' . $customer_url . '">' . htmlspecialchars($value) . '</a>';
    }

    /**
     * Display status badge
     */
    public function displayStatus($value, $row)
    {
        $badge_map = array(
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger'
        );
        
        $badge_class = isset($badge_map[$value]) ? $badge_map[$value] : 'default';
        $label = AerpointsGiftOrder::getStatusLabels();
        $status_label = isset($label[$value]) ? $label[$value] : $value;
        
        return '<span class="badge badge-' . $badge_class . '">' . $status_label . '</span>';
    }

    /**
     * Render view for order details
     */
    public function renderView()
    {
        $this->tpl_view_vars = array();
        
        $id_order = (int)Tools::getValue('id_aerpoints_gift_order');
        $order = new AerpointsGiftOrder($id_order);
        
        if (!Validate::isLoadedObject($order)) {
            $this->errors[] = $this->l('Gift order not found');
            return;
        }

        // Get customer info
        $customer = new Customer($order->id_customer);
        
        // Get gift info
        $gift = AerpointsGift::getGiftById($order->id_aerpoints_gift);
        
        // Get status labels
        $status_labels = AerpointsGiftOrder::getStatusLabels();
        $current_statuses = array();
        foreach ($status_labels as $key => $label) {
            $current_statuses[] = array(
                'value' => $key,
                'label' => $label,
                'selected' => ($key == $order->status)
            );
        }
        
        $this->context->smarty->assign(array(
            'order' => array(
                'id' => $order->id,
                'gift_name' => $order->gift_name,
                'points_spent' => $order->points_spent,
                'status' => $order->status,
                'status_label' => $order->getStatusLabel(),
                'customer_notes' => $order->customer_notes,
                'admin_notes' => $order->admin_notes,
                'date_add' => $order->date_add,
                'date_upd' => $order->date_upd
            ),
            'customer' => array(
                'id' => $customer->id,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'email' => $customer->email,
                'link' => $this->context->link->getAdminLink('AdminCustomers') . '&id_customer=' . $customer->id . '&viewcustomer'
            ),
            'gift' => $gift,
            'statuses' => $current_statuses,
            'can_cancel' => $order->canBeCancelledByCustomer(),
            'token' => $this->token,
            'current' => self::$currentIndex
        ));
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_.'aerpoints/views/templates/admin/gift_order_view.tpl');
    }

    /**
     * AJAX: Update order status
     */
    public function ajaxProcessUpdateOrderStatus()
    {
        $id_order = (int)Tools::getValue('id_order');
        $new_status = Tools::getValue('new_status');
        
        $order = new AerpointsGiftOrder($id_order);
        
        if (!Validate::isLoadedObject($order)) {
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->l('Gift order not found')
            )));
        }
        
        if (!in_array($new_status, AerpointsGiftOrder::getValidStatuses())) {
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->l('Invalid status')
            )));
        }
        
        if ($order->updateStatus($new_status)) {
            die(Tools::jsonEncode(array(
                'success' => true,
                'message' => $this->l('Status updated successfully')
            )));
        } else {
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->l('Failed to update status')
            )));
        }
    }

    /**
     * AJAX: Update admin notes
     */
    public function ajaxProcessUpdateAdminNotes()
    {
        $id_order = (int)Tools::getValue('id_order');
        $admin_notes = Tools::getValue('admin_notes');
        
        $order = new AerpointsGiftOrder($id_order);
        
        if (!Validate::isLoadedObject($order)) {
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->l('Gift order not found')
            )));
        }
        
        $order->admin_notes = pSQL($admin_notes, true);
        $order->date_upd = date('Y-m-d H:i:s');
        
        if ($order->update()) {
            die(Tools::jsonEncode(array(
                'success' => true,
                'message' => $this->l('Notes updated successfully')
            )));
        } else {
            die(Tools::jsonEncode(array(
                'success' => false,
                'error' => $this->l('Failed to update notes')
            )));
        }
    }
}
