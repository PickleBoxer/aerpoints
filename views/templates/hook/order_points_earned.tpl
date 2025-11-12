{*
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
*}

<div class="aerpoints-order-earned" style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #28a745; border-radius: 4px;">
    <h4 style="margin: 0 0 10px 0; color: #0f7fe3; font-size: 16px;">
        {l s='Congratulations! You Earned Points' mod='aerpoints'}
    </h4>

    <div style="font-size: 14px; line-height: 1.6;">
        {if $points_status == 'pending'}
            <p style="margin: 5px 0;">
                <strong style="font-size: 24px; color: #28a745;">{$total_points|intval} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 20px; height: 20px; vertical-align: middle;" /></strong>
                {l s='points will be added to your account once your order is confirmed.' mod='aerpoints'}
            </p>
            <p style="margin: 5px 0; font-size: 12px; color: #666;">
                <i class="icon-clock-o"></i> {l s='Status: Pending confirmation' mod='aerpoints'}
            </p>
        {elseif $points_status == 'completed'}
            <p style="margin: 5px 0;">
                <strong style="font-size: 24px; color: #28a745;">{$total_points|intval} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 20px; height: 20px; vertical-align: middle;" /></strong>
                {l s='points have been added to your account!' mod='aerpoints'}
            </p>
            <p style="margin: 5px 0; font-size: 12px; color: #28a745;">
                <i class="icon-check"></i> {l s='Status: Completed' mod='aerpoints'}
            </p>
        {/if}
    </div>
</div>
