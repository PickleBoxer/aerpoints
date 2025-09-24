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

{capture name=path}
    <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">{l s='My account' mod='aerpoints'}</a>
    <span class="navigation-pipe">{$navigationPipe}</span>
    <span class="navigation_page">{l s='My Points' mod='aerpoints'}</span>
{/capture}

<style>
    .panel-heading:after {
        display: none;
    }
</style>

<div class="box">
    <h1 class="page-heading">{l s='My AerPoints' mod='aerpoints'}</h1>

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">{l s='Points Summary' mod='aerpoints'}</h3>
                </div>
                <div class="panel-body">
                    <p class="aerpoints-balance">
                        {l s='Available Points:' mod='aerpoints'}
                        <strong class="aerpoints-total">{$customer_points}</strong>
                    </p>
                    <p class="aerpoints-value">
                        {l s='Estimated Value:' mod='aerpoints'}
                        <strong>{convertPrice price=$customer_points / $point_value}</strong>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">{l s='How to Earn Points' mod='aerpoints'}</h3>
                </div>
                <div class="panel-body">
                    <ul>
                        <li>{l s='Purchase products to earn points' mod='aerpoints'}</li>
                        <li>{l s='Each product has different point values' mod='aerpoints'}</li>
                        <li>{l s='Points are awarded after order confirmation' mod='aerpoints'}</li>
                        <li>{l s='Redeem points to create discount vouchers' mod='aerpoints'} -
                            <a href="{$link->getPageLink('discount', true)|escape:'html':'UTF-8'}" class="btn-link">
                                {l s='View your vouchers' mod='aerpoints'}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {if $customer_points > 0}
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="icon-gift"></i> {l s='Redeem Points' mod='aerpoints'}
                </h4>
            </div>
            <div class="panel-body">
                <div class="alert alert-info" style="margin: 15px 0; padding: 10px 15px; border-left: 4px solid #17a2b8;">
                    <i class="icon-info-sign"></i>
                    <small>{l s='Create a discount voucher for your next order' mod='aerpoints'}</small>
                </div>
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

                        <form class="form-inline" style="margin-top: 20px;">
                            <div class="form-group" style="margin-right: 10px;">
                                <label class="sr-only"
                                    for="aerpoints_redeem_amount">{l s='Points to redeem' mod='aerpoints'}</label>
                                <div class="input-group" style="width: 160px;">
                                    <input type="number" id="aerpoints_redeem_amount" name="aerpoints_redeem_amount"
                                        class="form-control" min="{$min_redemption}" max="{$customer_points}"
                                        value="{$min_redemption}" placeholder="{l s='Points' mod='aerpoints'}">
                                    <span class="input-group-addon">
                                        <i class="icon-star"></i>
                                    </span>
                                </div>
                            </div>
                            <button type="button" id="aerpoints_apply_btn" class="btn btn-primary btn-sm">
                                <i class="icon-magic"></i> {l s='Create Voucher' mod='aerpoints'}
                            </button>
                        </form>

                        <div id="aerpoints_message" style="display: none; margin-top: 10px;"></div>

                        <div style="margin-top: 10px;">
                            <small class="text-muted">
                                {$point_value} {l s='points = 1€ discount' mod='aerpoints'} •
                                <a href="{$link->getPageLink('discount', true)|escape:'html':'UTF-8'}" class="text-primary">
                                    <i class="icon-ticket"></i> {l s='Manage vouchers' mod='aerpoints'}
                                </a>
                            </small>
                        </div>
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
    {/if}

    {if $pending_points}
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">{l s='Pending Points' mod='aerpoints'}</h3>
            </div>
            <div class="panel-body">
                <p class="alert alert-info">
                    {l s='These points will be added to your account once your orders are confirmed and paid.' mod='aerpoints'}
                </p>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{l s='Order' mod='aerpoints'}</th>
                                <th>{l s='Points to Earn' mod='aerpoints'}</th>
                                <th>{l s='Status' mod='aerpoints'}</th>
                                <th>{l s='Date' mod='aerpoints'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$pending_points item=pending}
                                <tr>
                                    <td>#{$pending.id_order}</td>
                                    <td class="-text-success">+{$pending.points_to_earn}</td>
                                    <td>
                                        <span class="label label-warning">
                                            {l s='Pending' mod='aerpoints'}
                                        </span>
                                    </td>
                                    <td>{dateFormat date=$pending.date_add full=0}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    {/if}

    {if $points_history}
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">{l s='Points History' mod='aerpoints'}</h3>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{l s='Date' mod='aerpoints'}</th>
                                <th>{l s='Type' mod='aerpoints'}</th>
                                <th>{l s='Points' mod='aerpoints'}</th>
                                <th>{l s='Description' mod='aerpoints'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$points_history item=entry}
                                <tr>
                                    <td>{dateFormat date=$entry.date_add full=0}</td>
                                    <td>
                                        <span
                                            class="label label-{if $entry.type == 'earned'}success{elseif $entry.type == 'redeemed'}warning{else}info{/if}">
                                            {if $entry.type == 'earned'}{l s='Earned' mod='aerpoints'}
                                            {elseif $entry.type == 'redeemed'}{l s='Redeemed' mod='aerpoints'}
                                            {elseif $entry.type == 'expired'}{l s='Expired' mod='aerpoints'}
                                            {elseif $entry.type == 'manual_remove'}{l s='Redeemed' mod='aerpoints'}
                                            {elseif $entry.type == 'manual_add'}{l s='Earned' mod='aerpoints'}
                                            {else}{l s='Adjusted' mod='aerpoints'}
                                            {/if}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{if $entry.points > 0}-text-success{else}-text-danger{/if}">
                                            {if $entry.points > 0}+{/if}{$entry.points}
                                        </span>
                                    </td>
                                    <td>{$entry.description}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>

                {if $has_pagination}
                    <div class="text-center" style="margin-top: 15px;">
                        <ul class="pagination pagination-sm">
                            {if $current_page > 1}
                                <li>
                                    <a href="{$link->getModuleLink('aerpoints', 'customerpoints', ['page' => ($current_page-1)])|escape:'html':'UTF-8'}"
                                        title="{l s='Previous' mod='aerpoints'}">
                                        <i class="icon-angle-left"></i>
                                    </a>
                                </li>
                            {/if}

                            {for $i=1 to $total_pages}
                                {if $i == $current_page}
                                    <li class="active"><span>{$i}</span></li>
                                {else}
                                    <li>
                                        <a
                                            href="{$link->getModuleLink('aerpoints', 'customerpoints', ['page' => $i])|escape:'html':'UTF-8'}">{$i}</a>
                                    </li>
                                {/if}
                            {/for}

                            {if $current_page < $total_pages}
                                <li>
                                    <a href="{$link->getModuleLink('aerpoints', 'customerpoints', ['page' => ($current_page+1)])|escape:'html':'UTF-8'}"
                                        title="{l s='Next' mod='aerpoints'}">
                                        <i class="icon-angle-right"></i>
                                    </a>
                                </li>
                            {/if}
                        </ul>
                    </div>
                {/if}
            </div>
        </div>
    {else}
        <div class="alert alert-info">
            <p>{l s='No points history available yet. Start shopping to earn your first points!' mod='aerpoints'}</p>
        </div>
    {/if}

    <div class="footer_links clearfix">
        <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="icon-chevron-left"></i>
            {l s='Back to My Account' mod='aerpoints'}
        </a>
    </div>
</div>

<script type="text/javascript">
    {literal}
        $(document).ready(function() {
            var ajaxurl = '{/literal}{$link->getModuleLink('aerpoints', 'customerpoints')}{literal}';

            $('#aerpoints_apply_btn').click(function() {
                var $btn = $(this);
                var $message = $('#aerpoints_message');
                var points = parseInt($('#aerpoints_redeem_amount').val());
                var minRedemption = {/literal}{$min_redemption}{literal};

                // Hide previous messages
                $message.hide().removeClass('alert-danger alert-success');

                if (points < minRedemption) {
                    $message.addClass('alert alert-danger')
                        .html('<i class="icon-warning-sign"></i> {/literal}{l s='Minimum redemption is' mod='aerpoints'}{literal} ' + minRedemption + ' {/literal}{l s='points' mod='aerpoints'}{literal}')
                        .show();
                    return;
                }

                $btn.prop('disabled', true).html('<i class="icon-spinner icon-spin"></i> {/literal}{l s='Creating...' mod='aerpoints'}{literal}');

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
                            $message.addClass('alert alert-success')
                                .html('<i class="icon-check"></i> ' + response.message)
                                .show();
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $message.addClass('alert alert-danger')
                                .html('<i class="icon-warning-sign"></i> ' + response.message)
                                .show();
                            $btn.prop('disabled', false).html('<i class="icon-magic"></i> {/literal}{l s='Create Voucher' mod='aerpoints'}{literal}');
                        }
                    },
                    error: function() {
                        $message.addClass('alert alert-danger')
                            .html('<i class="icon-warning-sign"></i> {/literal}{l s='An error occurred. Please try again.' mod='aerpoints'}{literal}')
                            .show();
                        $btn.prop('disabled', false).html('<i class="icon-magic"></i> {/literal}{l s='Create Voucher' mod='aerpoints'}{literal}');
                    }
                });
            });
        });
    {/literal}
</script>