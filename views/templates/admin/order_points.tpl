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

<div class="panel" id="aerpoints-order-panel">
    <div class="panel-heading">
        <img src="../modules/aerpoints/views/img/points-icon.svg" alt="points" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;" />
        {l s='AerPoints Information' mod='aerpoints'}
    </div>
    <div class="panel-body">

        {if $order_history || $pending_points}
            {if $order_history}
            <h4>{l s='Points Transactions for this Order' mod='aerpoints'}</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{l s='Date' mod='aerpoints'}</th>
                            <th>{l s='Customer' mod='aerpoints'}</th>
                            <th>{l s='Type' mod='aerpoints'}</th>
                            <th>{l s='Points' mod='aerpoints'}</th>
                            <th>{l s='Description' mod='aerpoints'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$order_history item=transaction}
                        <tr>
                            <td>{dateFormat date=$transaction.date_add full=0}</td>
                            <td>{$transaction.firstname} {$transaction.lastname}</td>
                            <td>
                                <span class="label label-{if $transaction.type == 'earned'}success{elseif $transaction.type == 'redeemed'}warning{else}info{/if}">
                                    {if $transaction.type == 'earned'}{l s='Earned' mod='aerpoints'}
                                    {elseif $transaction.type == 'redeemed'}{l s='Redeemed' mod='aerpoints'}
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
            {/if}

            {if $pending_points}
            <h4>{l s='Pending Points' mod='aerpoints'}</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{l s='Status' mod='aerpoints'}</th>
                            <th>{l s='Points to Earn' mod='aerpoints'}</th>
                            <th>{l s='Points Redeemed' mod='aerpoints'}</th>
                            <th>{l s='Date Created' mod='aerpoints'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$pending_points item=pending}
                        <tr>
                            <td>
                                <span class="label label-{if $pending.status == 'pending'}warning{elseif $pending.status == 'completed'}success{else}danger{/if}">
                                    {if $pending.status == 'pending'}{l s='Pending' mod='aerpoints'}
                                    {elseif $pending.status == 'completed'}{l s='Completed' mod='aerpoints'}
                                    {else}{l s='Cancelled' mod='aerpoints'}
                                    {/if}
                                </span>
                            </td>
                            <td class="text-success">+{$pending.points_to_earn}</td>
                            <td class="text-danger">{if $pending.points_redeemed > 0}-{$pending.points_redeemed}{else}0{/if}</td>
                            <td>{dateFormat date=$pending.date_add full=0}</td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            {/if}
        {else}
            <div class="alert alert-info">
                <p>{l s='No points transactions found for this order.' mod='aerpoints'}</p>
            </div>
        {/if}
    </div>
</div>
