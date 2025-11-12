{*
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
*
* @author    PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<table id="aerpoints-cart-table" style="display: none;">
    <tbody id="aerpoints-cart-tbody">
        {* Base product points row *}
        {if $base_points > 0}
            <tr class="cart_discount aerpoints-row">
                <td class="cart_discount_name" colspan="3" style="padding: 8px; color: #666;">
                    {l s='Product Points' mod='aerpoints'}
                </td>
                <td class="cart_discount_price" style="padding: 8px; text-align: center; color: #666;">
                    <span>{$base_points} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;" /></span>
                </td>
                <td class="cart_discount_delete" colspan="2" style="padding: 8px; text-align: center; color: #666; font-size: 11px;">
                </td>
                <td class="cart_discount_price"  style="padding: 8px; text-align: right; color: #28a745;">
                    <span class="price">{$base_points} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;" /></span>
                </td>
            </tr>
        {/if}

        {* Rule rows *}
        {if $applicable_rules|count > 0}
            {foreach from=$applicable_rules item=rule}
                <tr class="cart_discount aerpoints-row">
                    <td class="cart_discount_name" colspan="3" style="padding: 8px; color: #666;">
                        {$rule.name|escape:'html':'UTF-8'}
                    </td>
                    <td class="cart_discount_price" style="padding: 8px; text-align: center; color: #666;">
                        {if $rule.type == 'bonus'}
                            <span>+ {$rule.base_value} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;" /></span>
                        {elseif $rule.type == 'multiplier'}
                            <span>× {$rule.value}</span>
                        {/if}
                    </td>
                    <td class="cart_discount_delete" colspan="2" style="padding: 8px; text-align: center; color: #666; font-size: 11px;">
                        {if $rule.type == 'bonus' && isset($rule.quantity) && $rule.quantity > 1}
                            × {$rule.quantity}
                        {elseif $rule.type == 'multiplier'}
                            {l s='Multiplier' mod='aerpoints'}
                        {/if}
                    </td>
                    <td class="cart_discount_price" style="padding: 8px; text-align: right; color: #28a745;">
                        {if $rule.type == 'bonus'}
                            <span class="price">{$rule.value} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;" /></span>
                        {/if}
                    </td>
                </tr>
            {/foreach}
        {/if}
    </tbody>

    {* Total row - separate for tfoot placement *}
    <tfoot id="aerpoints-cart-tfoot">
        <tr class="cart_total_points aerpoints-total-row">
            <td colspan="5" class="text-right" style="padding: 8px; font-weight: bold; color: #0f7fe3;">
                {l s='Total Loyalty Points You\'ll Earn' mod='aerpoints'}
            </td>
            <td colspan="2" class="price" style="padding: 8px; text-align: right; font-weight: bold; color: #0f7fe3; font-size: 16px;">
                <strong>{$total_points} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 16px; height: 16px; vertical-align: middle;" /></strong>
            </td>
        </tr>
    </tfoot>
</table>
