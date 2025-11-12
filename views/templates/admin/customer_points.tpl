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
<div class="col-lg-12">
    <div class="panel" id="aerpoints-customer-panel">
        <div class="panel-heading">
            <img src="../modules/aerpoints/views/img/points-icon.svg" alt="points" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;" />
            {l s='AerPoints Summary' mod='aerpoints'}
        </div>
        <div class="panel-body">

            <div class="row">
                <div class="col-md-6">
                    <div class="panel">
                        <div class="panel-heading">
                            {l s='Current Balance' mod='aerpoints'}
                        </div>
                        <div class="panel-body text-center">
                            <h2 class="text-success">
                                <img src="../modules/aerpoints/views/img/points-icon.svg" alt="points" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 5px;" />
                                {$customer_points|intval} {l s='Points' mod='aerpoints'}
                            </h2>
                            <p class="text-muted">
                                {l s='Estimated value:' mod='aerpoints'}
                                <strong>{convertPrice price=($customer_points/100)}</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="panel">
                        <div class="panel-heading">
                            {l s='Quick Actions' mod='aerpoints'}
                        </div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label>{l s='Manual Adjustment:' mod='aerpoints'}</label>
                                <div class="input-group">
                                    <input type="number" id="manual_points" class="form-control" placeholder="Points">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-success"
                                            onclick="adjustPoints({$customer_id}, 'add')">
                                            <i class="icon-plus"></i> {l s='Add' mod='aerpoints'}
                                        </button>
                                        <button type="button" class="btn btn-warning"
                                            onclick="adjustPoints({$customer_id}, 'remove')">
                                            <i class="icon-minus"></i> {l s='Remove' mod='aerpoints'}
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {if $customer_history}
                <h4>{l s='Recent Points Transactions (Last 10)' mod='aerpoints'}</h4>
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
                            {foreach from=$customer_history item=transaction}
                                <tr>
                                    <td>{dateFormat date=$transaction.date_add full=0}</td>
                                    <td>
                                        <span
                                            class="label label-{if $transaction.type == 'earned'}success{elseif $transaction.type == 'redeemed'}warning{elseif $transaction.type == 'manual_add'}info{elseif $transaction.type == 'manual_remove'}danger{else}default{/if}">
                                            {if $transaction.type == 'earned'}{l s='Earned' mod='aerpoints'}
                                            {elseif $transaction.type == 'redeemed'}{l s='Redeemed' mod='aerpoints'}
                                            {elseif $transaction.type == 'manual_add'}{l s='Manual Add' mod='aerpoints'}
                                            {elseif $transaction.type == 'manual_remove'}{l s='Manual Remove' mod='aerpoints'}
                                            {elseif $transaction.type == 'refund'}{l s='Refunded' mod='aerpoints'}
                                            {else}{l s='Other' mod='aerpoints'}
                                            {/if}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{if $transaction.points > 0}text-success{else}text-danger{/if}">
                                            {if $transaction.points > 0}+{/if}{$transaction.points}
                                        </span>
                                    </td>
                                    <td>{$transaction.description}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>

                <div class="text-center">
                    <a href="{$link->getModuleLink('aerpoints', 'customerpoints')|escape:'html':'UTF-8'}?id_customer={$customer_id}"
                        class="btn btn-primary" target="_blank">
                        <i class="icon-external-link"></i>
                        {l s='View Full Points History' mod='aerpoints'}
                    </a>
                </div>
            {else}
                <div class="alert alert-info">
                    <p>{l s='No points transactions found for this customer.' mod='aerpoints'}</p>
                </div>
            {/if}
        </div>
    </div>
</div>

<script type="text/javascript">
    var ajax_url = '{$ajax_url}';
    {literal}
        function adjustPoints(customerId, action) {
            var points = parseInt(document.getElementById('manual_points').value);
            if (isNaN(points) || points <= 0) {
                alert('Please enter a valid number of points');
                return;
            }

            if (confirm('Are you sure you want to ' + action + ' ' + points + ' points for this customer?')) {
                // AJAX call for manual point adjustments
                $.ajax({
                    url: ajax_url,
                    type: 'POST',
                    cache: false,
                    dataType: 'json',
                    data: {
                        ajax: true,
                        action: 'adjustPoints',
                        customer_id: customerId,
                        points: points,
                        adjust_type: action
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Points successfully ' + (action == 'add' ? 'added' : 'removed') +
                                '. New balance: ' + response.new_balance + ' points.');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing the request.');
                    }
                });
            }
        }
    {/literal}
</script>
