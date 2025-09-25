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

<div class="panel" id="aerpoints-product-panel">
    <div class="panel-heading">
        <i class="icon-star"></i>
        {l s='AerPoints Configuration' mod='aerpoints'}
    </div>
    <div class="panel-body">
        <p class="help-block">
            {l s='Configure how many points customers earn when buying this product.' mod='aerpoints'}
        </p>
        
        <div class="form-group">
            <label class="control-label">
                <i class="icon-plus"></i>
                {l s='Points Earned' mod='aerpoints'}
            </label>
            <input type="number" 
                   name="aerpoints_earn" 
                   id="aerpoints_earn"
                   class="form-control" 
                   value="{if $product_points}{$product_points.points_earn|intval}{else}0{/if}"
                   min="0"
                   placeholder="0">
            <p class="help-block">
                {l s='Points customer earns when buying this product' mod='aerpoints'}
            </p>
        </div>
        
        <div class="form-group">
            <label class="control-label">
                <i class="icon-check"></i>
                {l s='Active' mod='aerpoints'}
            </label>
            <span class="switch prestashop-switch fixed-width-lg">
                <input type="radio" name="aerpoints_active" id="aerpoints_active_on" value="1" {if !$product_points || $product_points.active == 1}checked="checked"{/if}>
                <label for="aerpoints_active_on">{l s='Yes' mod='aerpoints'}</label>
                <input type="radio" name="aerpoints_active" id="aerpoints_active_off" value="0" {if $product_points && $product_points.active == 0}checked="checked"{/if}>
                <label for="aerpoints_active_off">{l s='No' mod='aerpoints'}</label>
                <a class="slide-button btn"></a>
            </span>
            <p class="help-block">
                {l s='Enable or disable points earning for this product' mod='aerpoints'}
            </p>
        </div>
        
        {if $product_points}
        <div class="alert alert-info">
            <p><strong>{l s='Current Configuration:' mod='aerpoints'}</strong></p>
            <ul>
                <li>{l s='Points Earned:' mod='aerpoints'} <strong>{$product_points.points_earn|intval}</strong></li>
                <li>{l s='Status:' mod='aerpoints'} <strong>{if $product_points.active == 1}<span class="badge badge-success">{l s='Active' mod='aerpoints'}</span>{else}<span class="badge badge-danger">{l s='Inactive' mod='aerpoints'}</span>{/if}</strong></li>
                <li>{l s='Last Updated:' mod='aerpoints'} {$product_points.date_upd}</li>
            </ul>
        </div>
        {/if}
        
        <div class="alert alert-warning">
            <p><strong>{l s='Note:' mod='aerpoints'}</strong> {l s='Set value to 0 to remove points configuration for this product.' mod='aerpoints'}</p>
        </div>
    </div>
    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminAerpointsProduct')|escape:'html':'UTF-8'}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel' mod='aerpoints'}</a>
        <button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save' mod='aerpoints'}</button>
        <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay' mod='aerpoints'}</button>
    </div>
</div>

<style>
#aerpoints-product-panel .form-group label i {
    margin-right: 5px;
}
#aerpoints-product-panel .alert ul {
    margin-bottom: 0;
}
</style>
