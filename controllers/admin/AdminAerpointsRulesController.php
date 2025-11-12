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

require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsRule.php');
require_once(_PS_MODULE_DIR_.'aerpoints/classes/AerpointsRuleCondition.php');

class AdminAerpointsRulesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'aerpoints_rules';
        $this->className = 'AerpointsRule';
        $this->identifier = 'id_aerpoints_rule';
        $this->lang = false;

        parent::__construct();

        $this->fields_list = array(
            'id_aerpoints_rule' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'name' => array(
                'title' => $this->l('Rule Name'),
                'filter_key' => 'a!name'
            ),
            'action_type' => array(
                'title' => $this->l('Type'),
                'align' => 'center',
                'callback' => 'formatActionType'
            ),
            'action_value' => array(
                'title' => $this->l('Value'),
                'align' => 'center',
                'callback' => 'formatActionValue'
            ),
            'priority' => array(
                'title' => $this->l('Priority'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'date_from' => array(
                'title' => $this->l('From'),
                'type' => 'date',
                'align' => 'center'
            ),
            'date_to' => array(
                'title' => $this->l('To'),
                'type' => 'date',
                'align' => 'center'
            ),
            'active' => array(
                'title' => $this->l('Status'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-sm'
            ),
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        );

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function renderList()
    {
        $this->addRowActionSkipList('delete', array());

        return parent::renderList();
    }

    public static function formatActionType($value)
    {
        if ($value === 'bonus') {
            return '<span class="label label-success">Bonus Points</span>';
        } elseif ($value === 'multiplier') {
            return '<span class="label label-info">Multiplier</span>';
        }
        return $value;
    }

    public static function formatActionValue($value, $row)
    {
        if ($row['action_type'] === 'bonus') {
            $icon = '<img src="../modules/aerpoints/views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;" />';
            return '+' . (int)$value . ' ' . $icon;
        } elseif ($row['action_type'] === 'multiplier') {
            return number_format((float)$value, 2) . 'x';
        }
        return $value;
    }

    public function renderForm()
    {
        if (!($obj = $this->loadObject(true))) {
            return;
        }

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Points Rule'),
                'icon' => 'icon-star'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Rule Name'),
                    'name' => 'name',
                    'required' => true,
                    'col' => 6,
                    'hint' => $this->l('Admin-friendly name for this rule')
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Description'),
                    'name' => 'description',
                    'rows' => 3,
                    'col' => 6,
                    'hint' => $this->l('Optional notes for internal use')
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Action Type'),
                    'name' => 'action_type',
                    'required' => true,
                    'default_value' => 'bonus',
                    'values' => array(
                        array(
                            'id' => 'action_type_bonus',
                            'value' => 'bonus',
                            'label' => $this->l('Bonus Points (fixed amount)')
                        ),
                        array(
                            'id' => 'action_type_multiplier',
                            'value' => 'multiplier',
                            'label' => $this->l('Points Multiplier (multiply base points)')
                        ),
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Action Value'),
                    'name' => 'action_value',
                    'required' => true,
                    'col' => 3,
                    'suffix' => '<img src="../modules/aerpoints/views/img/points-icon.svg" alt="points" style="width: 12px; height: 12px; vertical-align: middle;" /> / x',
                    'hint' => $this->l('Points amount for bonus, or multiplier value (e.g., 2.0 for double points)')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Priority'),
                    'name' => 'priority',
                    'required' => true,
                    'col' => 2,
                    'desc' => $this->l('Lower values are processed first (1 = highest priority)'),
                    'default_value' => 1
                ),
                array(
                    'type' => 'datetime',
                    'label' => $this->l('Valid From'),
                    'name' => 'date_from',
                    'required' => true,
                    'col' => 3
                ),
                array(
                    'type' => 'datetime',
                    'label' => $this->l('Valid To'),
                    'name' => 'date_to',
                    'required' => true,
                    'col' => 3
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Total Usage Limit'),
                    'name' => 'quantity',
                    'col' => 3,
                    'desc' => $this->l('Maximum total uses (0 = unlimited)')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Per Customer Limit'),
                    'name' => 'quantity_per_user',
                    'col' => 3,
                    'desc' => $this->l('Maximum uses per customer (0 = unlimited)')
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
                ),
                array(
                    'type' => 'html',
                    'name' => 'conditions_html',
                    'html_content' => $this->renderConditionsBlock($obj)
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );

        return parent::renderForm();
    }

    protected function renderConditionsBlock($rule)
    {
        $html = '<div class="panel"><div class="panel-heading"><i class="icon-filter"></i> ' . $this->l('Conditions') . '</div>';
        $html .= '<div class="panel-body">';
        $html .= '<p class="help-block">' . $this->l('Add conditions to restrict when this rule applies. All conditions must be met (AND logic).') . '</p>';

        // Get existing conditions if editing
        $existing_conditions = array();
        if ($rule->id) {
            $existing_conditions = Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'aerpoints_rules_conditions`
                WHERE `id_aerpoints_rule` = ' . (int)$rule->id
            );
        }

        $html .= '<div id="conditions-container">';

        if (empty($existing_conditions)) {
            $html .= '<div class="condition-row" data-index="0">';
            $html .= $this->renderConditionRow(0, array());
            $html .= '</div>';
        } else {
            foreach ($existing_conditions as $index => $condition) {
                $html .= '<div class="condition-row" data-index="' . $index . '">';
                $html .= $this->renderConditionRow($index, $condition);
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        $html .= '<button type="button" class="btn btn-default" id="add-condition"><i class="icon-plus"></i> ' . $this->l('Add Condition') . '</button>';
        $html .= '</div></div>';

        // JavaScript for dynamic condition management
        $html .= $this->renderConditionsJavaScript();

        return $html;
    }

    protected function renderConditionRow($index, $condition = array())
    {
        $condition_type = isset($condition['condition_type']) ? $condition['condition_type'] : '';
        $condition_operator = isset($condition['condition_operator']) ? $condition['condition_operator'] : '';
        $condition_value = isset($condition['condition_value']) ? $condition['condition_value'] : '';
        $id_condition = isset($condition['id_condition']) ? $condition['id_condition'] : '';

        $html = '<div class="form-group">';
        $html .= '<div class="row">';

        // Hidden ID field for existing conditions
        if ($id_condition) {
            $html .= '<input type="hidden" name="conditions[' . $index . '][id]" value="' . $id_condition . '" />';
        }

        // Condition Type
        $html .= '<div class="col-lg-3">';
        $html .= '<select name="conditions[' . $index . '][type]" class="form-control condition-type" data-index="' . $index . '">';
        $html .= '<option value="">' . $this->l('Select condition...') . '</option>';
        $html .= '<option value="cart_amount"' . ($condition_type === 'cart_amount' ? ' selected' : '') . '>' . $this->l('Cart Total') . '</option>';
        $html .= '<option value="product"' . ($condition_type === 'product' ? ' selected' : '') . '>' . $this->l('Specific Products') . '</option>';
        $html .= '<option value="category"' . ($condition_type === 'category' ? ' selected' : '') . '>' . $this->l('Product Categories') . '</option>';
        $html .= '<option value="customer_group"' . ($condition_type === 'customer_group' ? ' selected' : '') . '>' . $this->l('Customer Group') . '</option>';
        $html .= '<option value="first_order"' . ($condition_type === 'first_order' ? ' selected' : '') . '>' . $this->l('First Order') . '</option>';
        $html .= '<option value="customer"' . ($condition_type === 'customer' ? ' selected' : '') . '>' . $this->l('Specific Customers') . '</option>';
        $html .= '</select>';
        $html .= '</div>';

        // Operator (for cart_amount)
        $operator_display = ($condition_type === 'cart_amount') ? '' : ' style="display:none;"';
        $html .= '<div class="col-lg-2 condition-operator-wrapper" data-index="' . $index . '"' . $operator_display . '>';
        $html .= '<select name="conditions[' . $index . '][operator]" class="form-control">';
        $html .= '<option value=">"' . ($condition_operator === '>' ? ' selected' : '') . '>&gt;</option>';
        $html .= '<option value=">="' . ($condition_operator === '>=' ? ' selected' : '') . '>&gt;=</option>';
        $html .= '<option value="<"' . ($condition_operator === '<' ? ' selected' : '') . '>&lt;</option>';
        $html .= '<option value="<="' . ($condition_operator === '<=' ? ' selected' : '') . '>&lt;=</option>';
        $html .= '<option value="="' . ($condition_operator === '=' ? ' selected' : '') . '>=</option>';
        $html .= '</select>';
        $html .= '</div>';

        // Value field
        $html .= '<div class="col-lg-5">';
        $html .= '<input type="text" name="conditions[' . $index . '][value]" class="form-control" value="' . htmlentities($condition_value, ENT_QUOTES, 'UTF-8') . '" placeholder="' . $this->l('Value (comma-separated IDs for products/categories/customers)') . '" />';
        $html .= '</div>';

        // Remove button
        $html .= '<div class="col-lg-2">';
        $html .= '<button type="button" class="btn btn-danger remove-condition"><i class="icon-trash"></i></button>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function renderConditionsJavaScript()
    {
        $html = '<script type="text/javascript">';
        $html .= '$(document).ready(function() {';
        $html .= '    var conditionIndex = $(".condition-row").length;';

        // Add condition button
        $html .= '    $("#add-condition").on("click", function() {';
        $html .= '        var newRow = $("<div>").addClass("condition-row").attr("data-index", conditionIndex);';
        $html .= '        newRow.html(' . json_encode($this->renderConditionRow(999, array())) . '.replace(/999/g, conditionIndex));';
        $html .= '        $("#conditions-container").append(newRow);';
        $html .= '        conditionIndex++;';
        $html .= '    });';

        // Remove condition button
        $html .= '    $(document).on("click", ".remove-condition", function() {';
        $html .= '        if ($(".condition-row").length > 1) {';
        $html .= '            $(this).closest(".condition-row").remove();';
        $html .= '        } else {';
        $html .= '            alert("' . $this->l('You must have at least one condition') . '");';
        $html .= '        }';
        $html .= '    });';

        // Show/hide operator field based on condition type
        $html .= '    $(document).on("change", ".condition-type", function() {';
        $html .= '        var index = $(this).data("index");';
        $html .= '        var type = $(this).val();';
        $html .= '        if (type === "cart_amount") {';
        $html .= '            $(".condition-operator-wrapper[data-index=" + index + "]").show();';
        $html .= '        } else {';
        $html .= '            $(".condition-operator-wrapper[data-index=" + index + "]").hide();';
        $html .= '        }';
        $html .= '    });';

        $html .= '});';
        $html .= '</script>';

        return $html;
    }

    /**
     * Clear rules cache
     */
    protected function clearRulesCache()
    {
        try {
            Cache::clean('aerpoints_active_rules');
        } catch (Exception $e) {
            // Cache clear failed, continue
        }
    }

    public function postProcess()
    {
        // Handle custom save logic for conditions
        if (Tools::isSubmit('submitAdd'.$this->table)) {
            // Let parent handle the main save first
            parent::postProcess();

            // Then save conditions if rule was saved successfully
            if (!count($this->errors)) {
                $id_rule = (int)Tools::getValue('id_aerpoints_rule');

                // If new rule, get the ID from the last inserted object
                if (!$id_rule && isset($this->object) && $this->object->id) {
                    $id_rule = $this->object->id;
                }

                if ($id_rule) {
                    $this->saveConditions($id_rule);
                    // Clear cache after successful save
                    $this->clearRulesCache();
                }
            }

            return;
        }

        // Clear cache for other operations (status toggle, etc.)
        if (Tools::isSubmit('status'.$this->table) || Tools::isSubmit('submitBulkdelete'.$this->table)) {
            parent::postProcess();
            $this->clearRulesCache();
            return;
        }

        return parent::postProcess();
    }

    /**
     * Override postDelete to clear cache when rule is deleted
     */
    public function postDelete()
    {
        $result = parent::postDelete();

        if ($result) {
            $this->clearRulesCache();
        }

        return $result;
    }

    protected function saveConditions($id_rule)
    {
        // Delete existing conditions
        Db::getInstance()->delete('aerpoints_rules_conditions', 'id_aerpoints_rule = ' . (int)$id_rule);

        // Get submitted conditions
        $conditions = Tools::getValue('conditions');

        if (!is_array($conditions) || empty($conditions)) {
            return true;
        }

        // Insert new conditions
        foreach ($conditions as $condition) {
            if (empty($condition['type'])) {
                continue;
            }

            $condition_data = array(
                'id_aerpoints_rule' => (int)$id_rule,
                'condition_type' => pSQL($condition['type']),
                'condition_value' => pSQL(isset($condition['value']) ? $condition['value'] : ''),
                'condition_operator' => pSQL(isset($condition['operator']) ? $condition['operator'] : null)
            );

            Db::getInstance()->insert('aerpoints_rules_conditions', $condition_data);
        }

        return true;
    }
}
