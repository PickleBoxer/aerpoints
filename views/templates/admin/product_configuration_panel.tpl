<div class="panel" id="aerpoints-explanation-panel">
    <div class="panel-heading">
        <i class="icon-star"></i>
        {l s='Product Points Configuration' mod='aerpoints'}
    </div>
    <div class="panel-body">
        <p>
            {l s='Configure how many points customers earn when buying products, and how many points are required to purchase products. Only products with at least one points value will appear in the list above.' mod='aerpoints'}
        </p>
        
        <h4>{l s='Add New Product Points Configuration' mod='aerpoints'}</h4>
        <form method="post" action="{$current}&amp;token={$token}">
            <div class="form-group">
                <label for="id_product">{l s='Product' mod='aerpoints'}</label>
                <select id="id_product" name="id_product" class="form-control" required>
                    <option value="">{l s='Select a product...' mod='aerpoints'}</option>
                    {if isset($products)}
                        {foreach from=$products item=product}
                            <option value="{$product.id_product}">{$product.name}</option>
                        {/foreach}
                    {/if}
                </select>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="points_earn">{l s='Points Earned' mod='aerpoints'}</label>
                        <input type="number" id="points_earn" name="points_earn" class="form-control" min="0" placeholder="0" value="0">
                        <p class="help-block">{l s='Points customer earns when buying this product' mod='aerpoints'}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="points_buy">{l s='Points to Buy' mod='aerpoints'}</label>
                        <input type="number" id="points_buy" name="points_buy" class="form-control" min="0" placeholder="0" value="0">
                        <p class="help-block">{l s='Points required to purchase this product (0 = cannot buy with points)' mod='aerpoints'}</p>
                    </div>
                </div>
            </div>
            <input type="hidden" name="submitAddaerpoints_product" value="1" />
            <button type="submit" name="submitAddaerpoints_product" class="btn btn-primary">{l s='Set Points' mod='aerpoints'}</button>
        </form>
    </div>
</div>
