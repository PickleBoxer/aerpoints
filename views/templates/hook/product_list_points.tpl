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
*  International Registered Trademark & Property of PrestaShop SA
*}
<div class="aerpoints-list-badge" style="margin-bottom:5px;">
    <img src="{$module_dir}views/img/points-icon.svg" alt="points" width="14" height="14" />
    <span>+{$points} Pt.</span>
</div>

{* Hidden notification template for cart popup - will be cloned by JS *}
<div class="aerpoints-cart-notification-template" style="display:none;" data-product-id="{if isset($product.id_product)}{$product.id_product}{/if}">
    <div class="aerpoints-notification-content" style="padding:10px; background:#f0f8ff; border:1px solid #3266ba; border-radius:5px; margin-top:10px;">
        <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width:16px; height:16px; vertical-align:middle; margin-right:5px;" />
        <strong>{l s='You added a %s product! 🎉' sprintf=[$manufacturer] mod='aerpoints'}</strong>
        <span>{l s='You earn %d loyalty points with this purchase.' sprintf=[$points] mod='aerpoints'}</span>
    </div>
</div>
