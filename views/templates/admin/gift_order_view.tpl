<div class="panel">
    <div class="panel-heading">
        {l s='Gift Order Details' mod='aerpoints'} #{$order.id}
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <h4>{l s='Customer Information' mod='aerpoints'}</h4>
                <table class="table table-bordered table-striped table-hover">
                    <tr>
                        <th>{l s='Name:' mod='aerpoints'}</th>
                        <th><a href="{$customer.link}" target="_blank">{$customer.firstname} {$customer.lastname}</a></th>
                    </tr>
                    <tr>
                        <th>{l s='Email:' mod='aerpoints'}</th>
                        <th>{$customer.email}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h4>{l s='Gift Information' mod='aerpoints'}</h4>
                <table class="table table-bordered table-striped table-hover">
                    <tr>
                        <th>{l s='Gift:' mod='aerpoints'}</th>
                        <th>{$order.gift_name}</th>
                    </tr>
                    <tr>
                        <th>{l s='Points Spent:' mod='aerpoints'}</th>
                        <th><span class="label label-info">{$order.points_spent} &#9733;</span></th>
                    </tr>
                    <tr>
                        <th>{l s='Status:' mod='aerpoints'}</td>
                        <th>
                            <div class="form-group">
                                <select id="order-status" class="form-control input-sm">
                                    {foreach from=$statuses item=status}
                                        <option value="{$status.value}" {if $status.selected}selected{/if}>{$status.label}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h4>{l s='Customer Notes' mod='aerpoints'}</h4>
                <div class="well">
                    {if $order.customer_notes}
                        {$order.customer_notes|nl2br}
                    {else}
                        <em>{l s='No customer notes' mod='aerpoints'}</em>
                    {/if}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h4>{l s='Admin Notes' mod='aerpoints'}</h4>
                <textarea id="admin-notes" class="form-control" rows="5">{$order.admin_notes}</textarea>
                <br>
                <button type="button" id="save-notes" class="btn btn-primary">{l s='Save Notes' mod='aerpoints'}</button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h4>{l s='Order Timeline' mod='aerpoints'}</h4>
                <table class="table">
                    <tr>
                        <th>{l s='Created:' mod='aerpoints'}</th>
                        <th>{$order.date_add}</td>
                    </tr>
                    <tr>
                        <th>{l s='Last Updated:' mod='aerpoints'}</th>
                        <th>{$order.date_upd}</th>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#order-status').on('change', function() {
        var newStatus = $(this).val();
        if (confirm('{l s='Are you sure you want to change the status?' mod='aerpoints'}')) {
            $.ajax({
                url: '{$current}&ajax=1&action=updateOrderStatus&token={$token}',
                type: 'POST',
                dataType: 'json',
                data: {
                    id_order: {$order.id},
                    new_status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessMessage(response.message);
                    } else {
                        alert(response.error);
                    }
                },
                error: function() {
                    alert('{l s='An error occurred' mod='aerpoints'}');
                }
            });
        }
    });

    $('#save-notes').on('click', function() {
        $.ajax({
            url: '{$current}&ajax=1&action=updateAdminNotes&token={$token}',
            type: 'POST',
            dataType: 'json',
            data: {
                id_order: {$order.id},
                admin_notes: $('#admin-notes').val()
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.message);
                } else {
                    alert(response.error);
                }
            },
            error: function() {
                alert('{l s='An error occurred' mod='aerpoints'}');
            }
        });
    });
});
</script>
