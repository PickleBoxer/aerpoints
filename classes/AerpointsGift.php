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

class AerpointsGift extends ObjectModel
{
    public $id_aerpoints_gift;
    public $name;
    public $description;
    public $points_cost;
    public $quantity;
    public $image;
    public $active;
    public $position;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'aerpoints_gift',
        'primary' => 'id_aerpoints_gift',
        'fields' => array(
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),
            'description' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'points_cost' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'quantity' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'image' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'position' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Get all active gifts
     *
     * @param int|null $id_lang Language ID (for future multi-language support)
     * @param string $order_by Order by field
     * @param string $order_way Order direction (ASC/DESC)
     * @return array Array of active gifts
     */
    public static function getActiveGifts($id_lang = null, $order_by = 'position', $order_way = 'ASC')
    {
        $order_by = pSQL($order_by);
        $order_way = pSQL($order_way);
        
        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'aerpoints_gift`
                WHERE `active` = 1
                ORDER BY `' . $order_by . '` ' . $order_way;

        $gifts = Db::getInstance()->executeS($sql);
        
        if (!$gifts) {
            return array();
        }

        // Add image path and availability info
        foreach ($gifts as &$gift) {
            $gift['image_path'] = self::getGiftImagePath($gift['id_aerpoints_gift'], $gift['image']);
            $gift['is_available'] = ((int)$gift['active'] == 1 && (int)$gift['quantity'] > 0);
        }

        return $gifts;
    }

    /**
     * Check if gift is available for redemption
     *
     * @return bool
     */
    public function isAvailable()
    {
        return ($this->active && $this->quantity > 0);
    }

    /**
     * Decrement gift stock
     *
     * @return bool Success status
     */
    public function decrementStock()
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'aerpoints_gift`
                SET `quantity` = `quantity` - 1,
                    `date_upd` = NOW()
                WHERE `id_aerpoints_gift` = ' . (int)$this->id . '
                AND `quantity` > 0';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Increment gift stock (used on cancellation)
     *
     * @return bool Success status
     */
    public function incrementStock()
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'aerpoints_gift`
                SET `quantity` = `quantity` + 1,
                    `date_upd` = NOW()
                WHERE `id_aerpoints_gift` = ' . (int)$this->id;

        return Db::getInstance()->execute($sql);
    }

    /**
     * Get gift image path
     *
     * @return string Image URL or placeholder
     */
    public function getImagePath()
    {
        return self::getGiftImagePath($this->id, $this->image);
    }

    /**
     * Get gift image path (static)
     *
     * @param int $id_gift Gift ID
     * @param string|null $image Image filename
     * @return string Image URL
     */
    public static function getGiftImagePath($id_gift, $image = null)
    {
        $base_url = _MODULE_DIR_ . 'aerpoints/views/img/gifts/';
        
        if ($image && file_exists(_PS_MODULE_DIR_ . 'aerpoints/views/img/gifts/' . $image)) {
            return $base_url . $image;
        }
        
        // Return placeholder
        return $base_url . 'no-image.png';
    }

    /**
     * Get gift by ID
     *
     * @param int $id_gift Gift ID
     * @return array|false Gift data or false
     */
    public static function getGiftById($id_gift)
    {
        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'aerpoints_gift`
                WHERE `id_aerpoints_gift` = ' . (int)$id_gift;

        $gift = Db::getInstance()->getRow($sql);
        
        if ($gift) {
            $gift['image_path'] = self::getGiftImagePath($gift['id_aerpoints_gift'], $gift['image']);
            $gift['is_available'] = ((int)$gift['active'] == 1 && (int)$gift['quantity'] > 0);
        }

        return $gift;
    }
}
