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

require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsGift.php');
require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsGiftOrder.php');

class AdminAerpointsGiftsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'aerpoints_gift';
        $this->className = 'AerpointsGift';
        $this->identifier = 'id_aerpoints_gift';
        $this->lang = false;
        $this->deleted = false;
        $this->colorOnBackground = false;
        
        $this->bulk_actions = array(
            'enableSelection' => array(
                'text' => $this->l('Enable selection'),
                'icon' => 'icon-power-off text-success'
            ),
            'disableSelection' => array(
                'text' => $this->l('Disable selection'),
                'icon' => 'icon-power-off text-danger'
            ),
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        parent::__construct();

        $this->fields_list = array(
            'id_aerpoints_gift' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'image' => array(
                'title' => $this->l('Image'),
                'align' => 'center',
                'image' => 'g',
                'callback' => 'displayImage',
                'orderby' => false,
                'search' => false,
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'width' => 'auto'
            ),
            'points_cost' => array(
                'title' => $this->l('Points Cost'),
                'align' => 'center',
                'callback' => 'displayPointsCost'
            ),
            'quantity' => array(
                'title' => $this->l('Stock'),
                'align' => 'center',
                'callback' => 'displayQuantity'
            ),
            'active' => array(
                'title' => $this->l('Active'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool'
            ),
            'position' => array(
                'title' => $this->l('Position'),
                'filter_key' => 'a!position',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'position' => 'position'
            ),
            'date_upd' => array(
                'title' => $this->l('Last Updated'),
                'align' => 'center',
                'type' => 'datetime'
            )
        );

        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';
    }

    /**
     * Display image callback
     */
    public function displayImage($value, $row)
    {
        $image_path = AerpointsGift::getGiftImagePath($row['id_aerpoints_gift'], $row['image']);
        return '<img src="' . $image_path . '" alt="' . htmlspecialchars($row['name']) . '" style="max-width: 50px; max-height: 50px;" />';
    }

    /**
     * Display points cost with icon
     */
    public function displayPointsCost($value, $row)
    {
        return '<span class="badge badge-info">' . (int)$value . ' ★</span>';
    }

    /**
     * Display quantity with color coding
     */
    public function displayQuantity($value, $row)
    {
        $color = ($value < 5) ? 'danger' : (($value < 10) ? 'warning' : 'success');
        return '<span class="badge badge-' . $color . '">' . (int)$value . '</span>';
    }

    /**
     * Render form for add/edit
     */
    public function renderForm()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Gift'),
                'icon' => 'icon-gift'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'required' => true,
                    'maxlength' => 255,
                    'hint' => $this->l('Gift display name (e.g., Amazon Gift Card)')
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Description'),
                    'name' => 'description',
                    'autoload_rte' => true,
                    'rows' => 10,
                    'cols' => 60,
                    'hint' => $this->l('Detailed description, features, or usage instructions')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Points Cost'),
                    'name' => 'points_cost',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                    'suffix' => 'points',
                    'hint' => $this->l('How many points required to redeem this gift')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Stock Quantity'),
                    'name' => 'quantity',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                    'hint' => $this->l('Available stock (0 = out of stock)')
                ),
                array(
                    'type' => 'file',
                    'label' => $this->l('Image'),
                    'name' => 'image',
                    'display_image' => true,
                    'hint' => $this->l('Upload gift image (JPG, PNG, GIF, WebP - max 2MB)')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'required' => false,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                    'hint' => $this->l('Display this gift in the catalog')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Position'),
                    'name' => 'position',
                    'class' => 'fixed-width-sm',
                    'hint' => $this->l('Display order (lower number = higher priority)')
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );

        return parent::renderForm();
    }

    /**
     * Process form submission
     */
    public function postProcess()
    {
        // Handle image upload before parent processing
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            $this->processImageUpload();
        }

        return parent::postProcess();
    }

    /**
     * Handle image upload
     */
    private function processImageUpload()
    {
        if (isset($_FILES['image']) && isset($_FILES['image']['tmp_name']) && !empty($_FILES['image']['tmp_name'])) {
            $id_gift = (int)Tools::getValue('id_aerpoints_gift');
            
            // Validate file
            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $file_info = pathinfo($_FILES['image']['name']);
            $extension = Tools::strtolower($file_info['extension']);
            
            if (!in_array($extension, $allowed_extensions)) {
                $this->errors[] = $this->l('Invalid image format. Allowed: JPG, PNG, GIF, WebP');
                return false;
            }
            
            if ($_FILES['image']['size'] > 2097152) { // 2MB
                $this->errors[] = $this->l('Image file is too large (max 2MB)');
                return false;
            }
            
            // Create directory if doesn't exist
            $gift_img_dir = _PS_MODULE_DIR_ . 'aerpoints/views/img/gifts';
            if (!file_exists($gift_img_dir)) {
                mkdir($gift_img_dir, 0755, true);
            }
            
            // Generate filename
            if ($id_gift > 0) {
                $filename = $id_gift . '.' . $extension;
            } else {
                // For new gifts, we'll update after insert
                $filename = 'temp_' . time() . '.' . $extension;
            }
            
            $destination = $gift_img_dir . '/' . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $_POST['image'] = $filename;
            } else {
                $this->errors[] = $this->l('Failed to upload image');
                return false;
            }
        }
        
        return true;
    }

    /**
     * Process after successful add
     */
    protected function afterAdd($object)
    {
        // If temp image exists, rename it with the proper ID
        if ($object->image && strpos($object->image, 'temp_') === 0) {
            $gift_img_dir = _PS_MODULE_DIR_ . 'aerpoints/views/img/gifts';
            $old_path = $gift_img_dir . '/' . $object->image;
            
            $extension = pathinfo($object->image, PATHINFO_EXTENSION);
            $new_filename = $object->id . '.' . $extension;
            $new_path = $gift_img_dir . '/' . $new_filename;
            
            if (file_exists($old_path)) {
                rename($old_path, $new_path);
                $object->image = $new_filename;
                $object->update();
            }
        }
        
        return parent::afterAdd($object);
    }

    /**
     * Process before deletion - check for active orders
     */
    protected function beforeDelete($object)
    {
        if (AerpointsGiftOrder::hasActiveOrders($object->id)) {
            $this->errors[] = $this->l('Cannot delete gift with pending or processing orders. Please complete or cancel those orders first.');
            return false;
        }
        
        return parent::beforeDelete($object);
    }

    /**
     * Process after deletion - delete image file
     */
    protected function afterDelete($object, $old_id)
    {
        if ($object->image) {
            $image_path = _PS_MODULE_DIR_ . 'aerpoints/views/img/gifts/' . $object->image;
            if (file_exists($image_path)) {
                @unlink($image_path);
            }
        }
        
        return parent::afterDelete($object, $old_id);
    }
}
