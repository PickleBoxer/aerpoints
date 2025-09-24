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
            <p>{l s='Minimum redemption:' mod='aerpoints'} <strong>{$min_redemption}</strong> {l s='points' mod='aerpoints'}</p>

            {if $redeemed_points > 0}
                <div class="alert alert-success">
                    <p>{l s='You are redeeming' mod='aerpoints'} <strong>{$redeemed_points}</strong>
                        {l s='points for a discount of' mod='aerpoints'} <strong>{$redeemed_discount}</strong></p>
                </div>
            {else}
                <div class="form-group">
                    <label for="aerpoints_redeem_amount">{l s='Points to redeem:' mod='aerpoints'}</label>
                    <div class="input-group" style="width: 200px;">
                        <input type="number" id="aerpoints_redeem_amount" name="aerpoints_redeem_amount" class="form-control"
                            min="{$min_redemption}" max="{$customer_points}" value="{$min_redemption}">
                        <span class="input-group-btn">
                            <button type="button" id="aerpoints_apply_btn" class="btn btn-primary">
                                {l s='Apply Points' mod='aerpoints'}
                            </button>
                        </span>
                    </div>
                    <small class="help-block">{$point_value} {l s='points = 1€ discount' mod='aerpoints'}</small>
                </div>
            {/if}
        </div>
    </div>

    <script type="text/javascript">
    {literal}
        $(document).ready(function() {
            var ajaxurl = '{/literal}{$link->getModuleLink('aerpoints', 'ajax')}{literal}';
            
            // Only bind event if button exists (when no points are already redeemed)
            $('#aerpoints_apply_btn').click(function() {
                var $btn = $(this);
                var points = parseInt($('#aerpoints_redeem_amount').val());
                var minRedemption = {/literal}{$min_redemption}{literal};
                
                if (points < minRedemption) {
                    alert('{/literal}{l s='Minimum redemption is' mod='aerpoints'}{literal} ' + minRedemption + ' {/literal}{l s='points' mod='aerpoints'}{literal}');
                    return;
                }
                
                $btn.prop('disabled', true).text('{/literal}{l s='Applying...' mod='aerpoints'}{literal}');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    async: false,
                    cache: false,
                    dataType: 'json',
                    data: {
                        ajax: true,
                        action: 'applyPoints',
                        points: points
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            location.reload();
                        } else {
                            alert(response.message);
                            $btn.prop('disabled', false).text('{/literal}{l s='Apply Points' mod='aerpoints'}{literal}');
                        }
                    },
                    error: function() {
                        alert('{/literal}{l s='An error occurred. Please try again.' mod='aerpoints'}{literal}');
                        $btn.prop('disabled', false).text('{/literal}{l s='Apply Points' mod='aerpoints'}{literal}');
                    }
                });
            });
        });
    {/literal}
    </script>
{/if}