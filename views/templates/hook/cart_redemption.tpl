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

{if $customer_points > 0}
<div id="aerpoints-cart-redemption" class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">{l s='AerPoints Redemption' mod='aerpoints'}</h4>
    </div>
    <div class="panel-body">
        <p>{l s='You have' mod='aerpoints'} <strong>{$customer_points}</strong> {l s='points available' mod='aerpoints'}</p>
        <p>{l s='1 point = 1 currency unit discount' mod='aerpoints'}</p>
        
        <div class="form-group">
            <label for="aerpoints_redeem_amount">{l s='Points to redeem:' mod='aerpoints'}</label>
            <input type="number" 
                   id="aerpoints_redeem_amount" 
                   name="aerpoints_redeem_amount" 
                   class="form-control" 
                   min="0" 
                   max="{$customer_points}" 
                   value="0"
                   style="width: 100px; display: inline-block;">
            <button type="button" id="aerpoints_apply_btn" class="btn btn-primary btn-sm">
                {l s='Apply Points' mod='aerpoints'}
            </button>
        </div>
        
        {if $redeemed_points > 0}
        <div class="alert alert-success">
            <p>{l s='You are redeeming' mod='aerpoints'} <strong>{$redeemed_points}</strong> {l s='points for a discount of' mod='aerpoints'} <strong>{$redeemed_discount}</strong></p>
            <button type="button" id="aerpoints_remove_btn" class="btn btn-warning btn-sm">
                {l s='Remove Points' mod='aerpoints'}
            </button>
        </div>
        {/if}
    </div>
</div>

<script type="text/javascript">
{literal}
$(document).ready(function() {
    $('#aerpoints_apply_btn').click(function() {
        var points = parseInt($('#aerpoints_redeem_amount').val());
        if (points > 0) {
            // Ajax call to apply points
            $.ajax({
                url: baseUri + 'modules/aerpoints/ajax.php',
                type: 'POST',
                data: {
                    action: 'applyPoints',
                    points: points
                },
                success: function(response) {
                    location.reload();
                }
            });
        }
    });
    
    $('#aerpoints_remove_btn').click(function() {
        // Ajax call to remove points
        $.ajax({
            url: baseUri + 'modules/aerpoints/ajax.php',
            type: 'POST',
            data: {
                action: 'removePoints'
            },
            success: function(response) {
                location.reload();
            }
        });
    });
});
{/literal}
</script>
{/if}
