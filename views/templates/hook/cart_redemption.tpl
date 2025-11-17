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
    <div id="aerpoints-cart-redemption" class="panel panel-default" style="margin-top:20px;">
        <div class="panel-heading">
            <h4 class="panel-title"><img src="/modules/aerpoints/views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;"> {l s='Redeem Points' mod='aerpoints'}</h4>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="well well-sm" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="text-muted small" style="margin-bottom: 5px;">
                                    {l s='Available Points' mod='aerpoints'}</p>
                                <h4 class="-text-success" style="margin: 0; font-weight: bold;">{$customer_points}</h4>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-muted small" style="margin-bottom: 5px;">{l s='Minimum' mod='aerpoints'}
                                </p>
                                <h5 style="margin: 0; color: #6c757d;">{$min_redemption} {l s='points' mod='aerpoints'}
                                </h5>
                            </div>
                        </div>
                    </div>

                    {if $redeemed_points > 0}
                        <div class="alert alert-success">
                            <span>{l s='You are redeeming' mod='aerpoints'} <strong>{$redeemed_points}</strong>
                                {l s='points for a discount of' mod='aerpoints'} <strong>{$redeemed_discount}</strong></span>
                        </div>
                    {else}
                        <form class="form-inline" style="margin-top: 20px;">
                            <div class="form-group" style="margin-right: 10px;">
                                <label class="sr-only"
                                    for="aerpoints_redeem_amount">{l s='Points to redeem' mod='aerpoints'}</label>
                                <div class="input-group" style="width: 160px;">
                                    <input type="number" id="aerpoints_redeem_amount" name="aerpoints_redeem_amount"
                                        class="form-control" min="{$min_redemption}" max="{$customer_points}"
                                        value="{$min_redemption}" placeholder="{l s='Points' mod='aerpoints'}">
                                    <span class="input-group-addon">
                                        <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;" />
                                    </span>
                                </div>
                            </div>
                            <button type="button" id="aerpoints_apply_btn" class="btn btn-primary btn-sm">
                                <i class="icon-magic"></i> {l s='Apply Points' mod='aerpoints'}
                            </button>
                        </form>
                        <small class="help-block">{$point_value} {l s='points = 1€ discount' mod='aerpoints'}</small>
                    {/if}
                </div>
                <div class="col-md-4">
                    <div class="text-center" style="padding: 20px 0;">
                        <i class="icon-gift" style="font-size: 48px; color: #17a2b8; opacity: 0.3;"></i>
                        <p class="text-muted small" style="margin-top: 10px; line-height: 1.4;">
                            {l s='Turn your points into instant savings' mod='aerpoints'}
                        </p>
                    </div>
                </div>
            </div>
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
