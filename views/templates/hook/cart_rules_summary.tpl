{if $total_points > 0}
<div class="aerpoints-cart-rules alert alert-info" style="margin-top: 15px;">
    <h4 style="margin-top: 0;">
        {l s='Points You\'ll Earn' mod='aerpoints'}
    </h4>

    <div class="points-breakdown">
        <div class="row" style="margin-bottom: 5px;">
            <div class="col-xs-8">{l s='Product Points:' mod='aerpoints'}</div>
            <div class="col-xs-4 text-right"><strong>{$base_points} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;" /></strong></div>
        </div>

        {if $multiplier > 1}
        <div class="row text-success" style="margin-bottom: 5px;">
            <div class="col-xs-8">
                {l s='Multiplier Bonus:' mod='aerpoints'} ({$multiplier}x)
            </div>
            <div class="col-xs-4 text-right">
                <strong>+{($base_points * ($multiplier - 1))|intval} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;" /></strong>
            </div>
        </div>
        {/if}

        {if $bonus_points > 0}
        <div class="row text-success" style="margin-bottom: 5px;">
            <div class="col-xs-8">{l s='Bonus Points:' mod='aerpoints'}</div>
            <div class="col-xs-4 text-right"><strong>+{$bonus_points} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle;" /></strong></div>
        </div>
        {/if}

        <hr style="margin: 10px 0;">

        <div class="row">
            <div class="col-xs-8"><strong>{l s='Total Points:' mod='aerpoints'}</strong></div>
            <div class="col-xs-4 text-right">
                <strong class="text-primary" style="font-size: 1.2em;">{$total_points} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 16px; height: 16px; vertical-align: middle;" /></strong>
            </div>
        </div>
    </div>

    {if isset($applicable_rules) && count($applicable_rules) > 0}
    <div class="active-rules" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #d6e9c6; font-size: 0.9em;">
        <em><i class="icon-gift"></i> {l s='Active promotions:' mod='aerpoints'}</em>
        <ul style="margin: 5px 0 0 20px; padding-left: 0;">
        {foreach from=$applicable_rules item=rule}
            <li style="list-style-type: none; margin-bottom: 3px;">
                <i class="icon-check-circle text-success"></i> {$rule.name|escape:'html':'UTF-8'}
                {if $rule.type == 'multiplier'}
                    <span class="label label-info">{$rule.value}x</span>
                {else}
                    <span class="label label-success">+{$rule.value|intval} <img src="{$module_dir}views/img/points-icon.svg" alt="points" style="width: 12px; height: 12px; vertical-align: middle;" /></span>
                {/if}
            </li>
        {/foreach}
        </ul>
    </div>
    {/if}
</div>
{/if}
