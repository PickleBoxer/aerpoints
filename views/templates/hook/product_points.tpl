{*
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
*}

{if $product_points}
<div class="aerpoints-product-info">
    {if $product_points.points_earn > 0}
    <div class="aerpoints-earn">
        <i class="icon-trophy"></i>
        <span class="aerpoints-text">
            {l s='Earn' mod='aerpoints'} <strong>{$product_points.points_earn}</strong> {l s='points' mod='aerpoints'}
        </span>
    </div>
    {/if}
    
    {if $product_points.points_buy > 0}
    <div class="aerpoints-buy">
        <i class="icon-gift"></i>
        <span class="aerpoints-text">
            {l s='Buy with' mod='aerpoints'} <strong>{$product_points.points_buy}</strong> {l s='points' mod='aerpoints'}
            {assign var="discount_value" value=($product_points.points_buy / $point_value)}
            <small>({l s='≈' mod='aerpoints'} {$discount_value|string_format:"%.2f"}€)</small>
        </span>
    </div>
    {/if}
</div>
{/if}
