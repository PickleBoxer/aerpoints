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
                        {l s='Total Value:' mod='aerpoints'} 
                        <strong>{convertPrice price=$customer_points}</strong>
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
                        <li>{l s='Use points for discounts on future orders' mod='aerpoints'}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
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
                                <span class="label label-{if $entry.type == 'earned'}success{elseif $entry.type == 'redeemed'}warning{else}info{/if}">
                                    {if $entry.type == 'earned'}{l s='Earned' mod='aerpoints'}
                                    {elseif $entry.type == 'redeemed'}{l s='Redeemed' mod='aerpoints'}
                                    {elseif $entry.type == 'expired'}{l s='Expired' mod='aerpoints'}
                                    {else}{l s='Adjusted' mod='aerpoints'}
                                    {/if}
                                </span>
                            </td>
                            <td>
                                <span class="{if $entry.points > 0}text-success{else}text-danger{/if}">
                                    {if $entry.points > 0}+{/if}{$entry.points}
                                </span>
                            </td>
                            <td>{$entry.description}</td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
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
