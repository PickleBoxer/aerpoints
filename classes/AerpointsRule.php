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

class AerpointsRule extends ObjectModel
{
    public $id_aerpoints_rule;
    public $name;
    public $description;
    public $action_type;
    public $action_value;
    public $priority;
    public $date_from;
    public $date_to;
    public $quantity;
    public $quantity_per_user;
    public $active;
    public $date_add;
    public $date_upd;

    /**
     * @var array|null Cached conditions to avoid DB queries when loaded from cache
     */
    private $cached_conditions = null;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'aerpoints_rules',
        'primary' => 'id_aerpoints_rule',
        'fields' => array(
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),
            'description' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'action_type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
            'action_value' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'priority' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'date_from' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true),
            'date_to' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true),
            'quantity' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'quantity_per_user' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Get all active rules for a specific date
     *
     * @param string|null $date Date to check (null = current date)
     * @return array Array of AerpointsRule objects
     */
    public static function getActiveRules($date = null)
    {
        if ($date === null) {
            $date = date('Y-m-d H:i:s');
        }

        $cache_key = 'aerpoints_active_rules';
        $rules = array();

        // Try to retrieve from cache
        try {
            if (Cache::isStored($cache_key)) {
                $cached_data = Cache::retrieve($cache_key);

                // Build rule objects from cached data
                foreach ($cached_data as $rule_data) {
                    $rule = new AerpointsRule();

                    // Populate rule properties
                    foreach ($rule_data as $key => $value) {
                        if ($key !== 'conditions' && property_exists($rule, $key)) {
                            $rule->$key = $value;
                        }
                    }

                    // Set cached conditions
                    if (isset($rule_data['conditions'])) {
                        $rule->setCachedConditions($rule_data['conditions']);
                    }

                    $rules[] = $rule;
                }

                return $rules;
            }
        } catch (Exception $e) {
            // Cache retrieval failed, continue to DB query
        }

        // Cache miss - query database with JOIN to get rules + conditions in one query
        $sql = 'SELECT r.*, c.id_condition, c.condition_type, c.condition_value, c.condition_operator
                FROM `' . _DB_PREFIX_ . 'aerpoints_rules` r
                LEFT JOIN `' . _DB_PREFIX_ . 'aerpoints_rules_conditions` c
                    ON r.id_aerpoints_rule = c.id_aerpoints_rule
                WHERE r.`active` = 1
                AND r.`date_from` <= "' . pSQL($date) . '"
                AND r.`date_to` >= "' . pSQL($date) . '"
                ORDER BY r.`priority` ASC, r.`id_aerpoints_rule` ASC';

        $results = Db::getInstance()->executeS($sql);

        if (!$results) {
            return array();
        }

        // Build nested array: group conditions by rule
        $rules_data = array();
        $current_rule_id = null;
        $current_rule_index = -1;

        foreach ($results as $row) {
            // New rule
            if ($current_rule_id !== (int)$row['id_aerpoints_rule']) {
                $current_rule_id = (int)$row['id_aerpoints_rule'];
                $current_rule_index++;

                // Store rule data
                $rules_data[$current_rule_index] = array(
                    'id_aerpoints_rule' => $row['id_aerpoints_rule'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'action_type' => $row['action_type'],
                    'action_value' => $row['action_value'],
                    'priority' => $row['priority'],
                    'date_from' => $row['date_from'],
                    'date_to' => $row['date_to'],
                    'quantity' => $row['quantity'],
                    'quantity_per_user' => $row['quantity_per_user'],
                    'active' => $row['active'],
                    'date_add' => $row['date_add'],
                    'date_upd' => $row['date_upd'],
                    'conditions' => array()
                );
            }

            // Add condition if exists (LEFT JOIN might have null conditions)
            if ($row['id_condition']) {
                $rules_data[$current_rule_index]['conditions'][] = array(
                    'id_condition' => $row['id_condition'],
                    'condition_type' => $row['condition_type'],
                    'condition_value' => $row['condition_value'],
                    'condition_operator' => $row['condition_operator']
                );
            }
        }

        // Store in cache
        try {
            Cache::store($cache_key, $rules_data);
        } catch (Exception $e) {
            // Cache storage failed, continue without caching
        }

        // Convert to rule objects
        foreach ($rules_data as $rule_data) {
            $rule = new AerpointsRule();

            foreach ($rule_data as $key => $value) {
                if ($key !== 'conditions' && property_exists($rule, $key)) {
                    $rule->$key = $value;
                }
            }

            if (isset($rule_data['conditions'])) {
                $rule->setCachedConditions($rule_data['conditions']);
            }

            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * Get conditions for this rule
     *
     * @return array Array of conditions
     */
    public function getConditions()
    {
        // Return cached conditions if available
        if ($this->cached_conditions !== null) {
            return $this->cached_conditions;
        }

        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'aerpoints_rules_conditions`
                WHERE `id_aerpoints_rule` = ' . (int)$this->id;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Set cached conditions (used when loading from cache)
     *
     * @param array $conditions Array of conditions
     * @return void
     */
    public function setCachedConditions($conditions)
    {
        $this->cached_conditions = $conditions;
    }

    /**
     * Check if all conditions are met for this rule
     *
     * @param Cart $cart Customer's cart
     * @param Customer $customer Customer object
     * @return bool True if all conditions met
     */
    public function checkConditions($cart, $customer)
    {
        $conditions = $this->getConditions();

        // No conditions = always applies
        if (empty($conditions)) {
            return true;
        }

        // All conditions must be met (AND logic)
        foreach ($conditions as $condition) {
            $check = AerpointsRuleCondition::checkCondition(
                $condition['condition_type'],
                $condition['condition_operator'],
                $condition['condition_value'],
                $cart,
                $customer
            );

            if (!$check) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if rule is valid (date range, usage limits, conditions)
     *
     * @param Cart $cart Customer's cart
     * @param Customer $customer Customer object
     * @return bool True if valid
     */
    public function isValid($cart, $customer)
    {
        // Check if active
        if (!$this->active) {
            return false;
        }

        // Check date range
        $now = date('Y-m-d H:i:s');
        if ($now < $this->date_from || $now > $this->date_to) {
            return false;
        }

        // Check total usage limit
        if ($this->quantity > 0) {
            $total_uses = $this->getTotalUses();
            if ($total_uses >= $this->quantity) {
                return false;
            }
        }

        // Check per-customer usage limit
        if ($this->quantity_per_user > 0 && $customer->id) {
            $customer_uses = $this->getCustomerUses((int)$customer->id);
            if ($customer_uses >= $this->quantity_per_user) {
                return false;
            }
        }

        // Check conditions
        if (!$this->checkConditions($cart, $customer)) {
            return false;
        }

        return true;
    }

    /**
     * Get total number of times this rule has been used
     *
     * @return int Usage count
     */
    public function getTotalUses()
    {
        $sql = 'SELECT COUNT(*) as total
                FROM `' . _DB_PREFIX_ . 'aerpoints_rules_usage`
                WHERE `id_aerpoints_rule` = ' . (int)$this->id;

        $result = Db::getInstance()->getRow($sql);
        return (int)$result['total'];
    }

    /**
     * Get number of times a specific customer has used this rule
     *
     * @param int $id_customer Customer ID
     * @return int Usage count
     */
    public function getCustomerUses($id_customer)
    {
        $sql = 'SELECT COUNT(*) as total
                FROM `' . _DB_PREFIX_ . 'aerpoints_rules_usage`
                WHERE `id_aerpoints_rule` = ' . (int)$this->id . '
                AND `id_customer` = ' . (int)$id_customer;

        $result = Db::getInstance()->getRow($sql);
        return (int)$result['total'];
    }

    /**
     * Get remaining uses for this rule
     *
     * @param int|null $id_customer Customer ID (null for total remaining)
     * @return int|string Remaining uses or 'unlimited'
     */
    public function getRemainingUses($id_customer = null)
    {
        if ($id_customer !== null && $this->quantity_per_user > 0) {
            $used = $this->getCustomerUses((int)$id_customer);
            $remaining = $this->quantity_per_user - $used;
            return max(0, $remaining);
        }

        if ($this->quantity > 0) {
            $used = $this->getTotalUses();
            $remaining = $this->quantity - $used;
            return max(0, $remaining);
        }

        return 'unlimited';
    }

    /**
     * Record usage of this rule
     *
     * @param int $id_customer Customer ID
     * @param int $id_order Order ID
     * @param int $points_awarded Points awarded
     * @return bool Success
     */
    public function recordUsage($id_customer, $id_order, $points_awarded)
    {
        return Db::getInstance()->insert('aerpoints_rules_usage', array(
            'id_aerpoints_rule' => (int)$this->id,
            'id_customer' => (int)$id_customer,
            'id_order' => (int)$id_order,
            'points_awarded' => (int)$points_awarded,
            'date_add' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Get quantity multiplier for product-based rules
     * If rule has product condition, returns total quantity of matching products
     *
     * @param Cart $cart Customer's cart
     * @return int Quantity multiplier (1 if not product-based)
     */
    public function getQuantityMultiplier($cart)
    {
        $conditions = $this->getConditions();
        $quantity = 1;

        foreach ($conditions as $condition) {
            if ($condition['condition_type'] === 'product') {
                $required_products = array_map('intval', explode(',', $condition['condition_value']));
                $cart_products = $cart->getProducts();

                foreach ($cart_products as $product) {
                    if (in_array((int)$product['id_product'], $required_products)) {
                        $quantity += (int)$product['cart_quantity'] - 1;
                    }
                }
                break; // Only check first product condition
            }
        }

        return $quantity;
    }

    /**
     * Calculate points based on this rule
     *
     * @param Cart $cart Customer's cart
     * @param int $base_points Base product points
     * @return array Array with 'bonus' and 'multiplier' keys
     */
    public function calculatePoints($cart, $base_points)
    {
        $result = array(
            'bonus' => 0,
            'multiplier' => 1.0,
        );

        if ($this->action_type === 'bonus') {
            $result['bonus'] = (int)$this->action_value;
        } elseif ($this->action_type === 'multiplier') {
            $result['multiplier'] = (float)$this->action_value;
        }

        return $result;
    }

    /**
     * Add a condition to this rule
     *
     * @param string $type Condition type
     * @param string $operator Condition operator
     * @param string $value Condition value
     * @return bool Success
     */
    public function addCondition($type, $operator, $value)
    {
        return Db::getInstance()->insert('aerpoints_rules_conditions', array(
            'id_aerpoints_rule' => (int)$this->id,
            'condition_type' => pSQL($type),
            'condition_operator' => pSQL($operator),
            'condition_value' => pSQL($value),
        ));
    }

    /**
     * Delete all conditions for this rule
     *
     * @return bool Success
     */
    public function deleteConditions()
    {
        return Db::getInstance()->delete(
            'aerpoints_rules_conditions',
            'id_aerpoints_rule = ' . (int)$this->id
        );
    }

    /**
     * Delete this rule and all related data
     *
     * @return bool Success
     */
    public function delete()
    {
        // Delete conditions
        $this->deleteConditions();

        // Note: We don't delete usage history for reporting purposes
        // If you want to delete usage: Db::getInstance()->delete('aerpoints_rules_usage', 'id_aerpoints_rule = ' . (int)$this->id);

        return parent::delete();
    }
}
