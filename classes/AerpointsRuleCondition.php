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

class AerpointsRuleCondition
{
    /**
     * Check a specific condition
     *
     * @param string $type Condition type
     * @param string $operator Condition operator
     * @param string $value Condition value
     * @param Cart $cart Customer's cart
     * @param Customer $customer Customer object
     * @return bool True if condition met
     */
    public static function checkCondition($type, $operator, $value, $cart, $customer)
    {
        switch ($type) {
            case 'cart_amount':
                return self::checkCartAmount($cart, $operator, $value);

            case 'product':
                return self::checkProducts($cart, $operator, $value);

            case 'category':
                return self::checkCategories($cart, $value);

            case 'customer_group':
                return self::checkCustomerGroup($customer, $value);

            case 'first_order':
                return self::checkFirstOrder($customer);

            case 'customer':
                return self::checkSpecificCustomers($customer, $value);

            default:
                return false;
        }
    }

    /**
     * Check cart amount condition
     *
     * @param Cart $cart
     * @param string $operator Operator: '>', '>=', '<', '<=', '='
     * @param string $value Amount to compare
     * @return bool
     */
    public static function checkCartAmount($cart, $operator, $value)
    {
        $cart_total = $cart->getOrderTotal(true, Cart::BOTH);
        $compare_value = (float)$value;

        switch ($operator) {
            case '>':
            case 'gt':
                return $cart_total > $compare_value;
            case '>=':
            case 'gte':
                return $cart_total >= $compare_value;
            case '<':
            case 'lt':
                return $cart_total < $compare_value;
            case '<=':
            case 'lte':
                return $cart_total <= $compare_value;
            case '=':
            case 'equals':
                return abs($cart_total - $compare_value) < 0.01;
            default:
                return false;
        }
    }

    /**
     * Check if cart contains specific products
     *
     * @param Cart $cart
     * @param string $operator 'in' (contains any) or 'all' (contains all) - ignored for product conditions, defaults to 'in'
     * @param string $value Comma-separated product IDs
     * @return bool
     */
    public static function checkProducts($cart, $operator, $value)
    {
        $required_products = array_map('intval', explode(',', $value));
        $cart_products = $cart->getProducts();
        $cart_product_ids = array();

        foreach ($cart_products as $product) {
            $cart_product_ids[] = (int)$product['id_product'];
        }

        // For product conditions, operator should be 'in' or 'all'
        // If invalid operator (like '>', '>=', etc.), default to 'in'
        if ($operator !== 'all' && $operator !== 'in') {
            $operator = 'in';
        }

        if ($operator === 'in') {
            // At least one required product must be in cart
            foreach ($required_products as $id_product) {
                if (in_array($id_product, $cart_product_ids)) {
                    return true;
                }
            }
            return false;
        } elseif ($operator === 'all') {
            // All required products must be in cart
            foreach ($required_products as $id_product) {
                if (!in_array($id_product, $cart_product_ids)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Check if cart contains products from specific categories
     *
     * @param Cart $cart
     * @param string $value Comma-separated category IDs
     * @return bool
     */
    public static function checkCategories($cart, $value)
    {
        // Any product from specified categories triggers the rule
        $required_categories = array_map('intval', explode(',', $value));
        $cart_products = $cart->getProducts();

        foreach ($cart_products as $product) {
            $product_categories = Product::getProductCategories((int)$product['id_product']);

            foreach ($required_categories as $id_category) {
                if (in_array($id_category, $product_categories)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if customer belongs to specific group(s)
     *
     * @param Customer $customer
     * @param string $value Comma-separated group IDs
     * @return bool
     */
    public static function checkCustomerGroup($customer, $value)
    {
        if (!Validate::isLoadedObject($customer)) {
            return false;
        }

        $required_groups = array_map('intval', explode(',', $value));
        $customer_groups = $customer->getGroups();

        foreach ($required_groups as $id_group) {
            if (in_array($id_group, $customer_groups)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this is customer's first order
     *
     * @param Customer $customer
     * @return bool
     */
    public static function checkFirstOrder($customer)
    {
        if (!Validate::isLoadedObject($customer)) {
            return false;
        }

        $orders = Order::getCustomerOrders((int)$customer->id);
        return empty($orders);
    }

    /**
     * Check if customer is in specific customers list
     *
     * @param Customer $customer
     * @param string $value Comma-separated customer IDs
     * @return bool
     */
    public static function checkSpecificCustomers($customer, $value)
    {
        if (!Validate::isLoadedObject($customer)) {
            return false;
        }

        $allowed_customers = array_map('intval', explode(',', $value));
        return in_array((int)$customer->id, $allowed_customers);
    }
}
