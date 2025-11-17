{capture name=path}
    <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">{l s='My account' mod='aerpoints'}</a>
    <span class="navigation-pipe">{$navigationPipe}</span>
    <span class="navigation_page">{l s='Gift Catalog' mod='aerpoints'}</span>
{/capture}

{block name='page_content'}
    <div class="aerpoints-gifts-catalog">
        <div class="alert alert-info">
            <strong>{l s='Your Available Points:' mod='aerpoints'}</strong>
            <span>{$available_points} <img src="{$module_dir}views/img/points-icon.svg"
                    alt="points" style="width: 14px; height: 14px; vertical-align: middle;" /></span>
        </div>

        {if $gifts && count($gifts) > 0}
            <div class="row aerpoints-gifts-grid">
                {foreach from=$gifts item=gift}
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="aerpoints-gift-card {if !$gift.is_available}out-of-stock{/if}">
                            <div class="gift-image-container">
                                <img src="{$gift.image_path}" alt="{$gift.name|escape:'html'}" class="gift-image">
                                {if !$gift.is_available}
                                    <span class="stock-badge out-of-stock">{l s='Out of Stock' mod='aerpoints'}</span>
                                {else}
                                    <span class="stock-badge in-stock">{l s='Available' mod='aerpoints'}</span>
                                {/if}
                            </div>
                            <div class="gift-info">
                                <h3 class="gift-name">{$gift.name|escape:'html'}</h3>
                                <div class="gift-description">
                                    {$gift.description|strip_tags|truncate:100:'...'|escape:'html'}
                                </div>
                                <div class="gift-points">
                                    <span class="points-badge">{$gift.points_cost} <img src="{$module_dir}views/img/points-icon.svg"
                    alt="points" style="width: 14px; height: 14px; vertical-align: middle;" /></span>
                                </div>
                                <div class="gift-actions">
                                    {if $gift.is_available && $available_points >= $gift.points_cost}
                                        <button type="button" class="btn btn-primary btn-redeem"
                                            data-id-gift="{$gift.id_aerpoints_gift}" data-gift-name="{$gift.name|escape:'html'}"
                                            data-points="{$gift.points_cost}">
                                            {l s='Redeem Now' mod='aerpoints'}
                                        </button>
                                    {else if !$gift.is_available}
                                        <button type="button" class="btn btn-default" disabled>
                                            {l s='Unavailable' mod='aerpoints'}
                                        </button>
                                    {else}
                                        <button type="button" class="btn btn-default" disabled
                                            title="{l s='Need %d more points' sprintf=[$gift.points_cost - $available_points] mod='aerpoints'}">
                                            {l s='Insufficient Points' mod='aerpoints'}
                                        </button>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
        {else}
            <div class="alert alert-warning">
                <p>{l s='No gifts available at the moment. Please check back later.' mod='aerpoints'}</p>
            </div>
        {/if}
    </div>

    <!-- Redemption Modal -->
    <div id="redeemModal" style="display: none;">
        <div style="padding: 20px;">
            <h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #ddd;">
                {l s='Confirm Redemption' mod='aerpoints'}</h3>
            <div style="margin: 20px 0;">
                <p><strong>{l s='Gift:' mod='aerpoints'}</strong> <span id="modal-gift-name"></span></p>
                <p><strong>{l s='Points Required:' mod='aerpoints'}</strong> <span id="modal-points"></span> ★</p>
                <div class="form-group">
                    <label for="customer-notes">{l s='Delivery Notes (Optional):' mod='aerpoints'}</label>
                    <textarea class="form-control" id="customer-notes" rows="3"
                        placeholder="{l s='Any special instructions or delivery preferences' mod='aerpoints'}"></textarea>
                </div>
                <div class="alert alert-info">
                    {l s='Your points will be deducted immediately. Our team will process your order soon.' mod='aerpoints'}
                </div>
            </div>
            <div style="padding-top: 15px; border-top: 1px solid #ddd; text-align: right;">
                <button type="button" class="btn btn-default"
                    onclick="$.fancybox.close();">{l s='Cancel' mod='aerpoints'}</button>
                <button type="button"
                    class="btn btn-primary btn-confirm-redeem">{l s='Confirm Redemption' mod='aerpoints'}</button>
            </div>
        </div>
    </div>

    <script>
        var aerpointsGiftAjaxUrl = '{$ajax_url}';
        var currentGiftId = 0;

        $(document).ready(function() {
            console.log('AJAX URL:', aerpointsGiftAjaxUrl);
            
            $('.btn-redeem').on('click', function() {
                currentGiftId = $(this).data('id-gift');
                var giftName = $(this).data('gift-name');
                var points = $(this).data('points');

                // Update modal content
                var modalContent = $('#redeemModal').clone();
                modalContent.find('#modal-gift-name').text(giftName);
                modalContent.find('#modal-points').text(points);
                modalContent.find('#customer-notes').val('');
                modalContent.show();

                $.fancybox.open({
                    'padding': 0,
                    'width': 460,
                    autoScale: true,
                    content: modalContent.html(),
                    afterShow: function() {
                        $('.btn-confirm-redeem').off('click').on('click', function() {
                            var btn = $(this);
                            var customerNotes = $('.fancybox-inner #customer-notes').val();
                            
                            console.log('Starting AJAX request...');
                            console.log('Gift ID:', currentGiftId);
                            console.log('Customer Notes:', customerNotes);
                            console.log('URL:', aerpointsGiftAjaxUrl);
                            
                            btn.prop('disabled', true).text('{l s='Processing...' mod='aerpoints'}');

                            $.ajax({
                                url: aerpointsGiftAjaxUrl,
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    ajax: true,
                                    action: 'RedeemGift',
                                    id_gift: currentGiftId,
                                    customer_notes: customerNotes
                                },
                                success: function(response) {
                                    console.log('Success response:', response);
                                    if (response && response.success) {
                                        $.fancybox.close();
                                        alert(response.message);
                                        location.reload();
                                    } else {
                                        alert(response ? response.error : '{l s='An error occurred. Please try again.' mod='aerpoints'}');
                                        btn.prop('disabled', false).text('{l s='Confirm Redemption' mod='aerpoints'}');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.log('AJAX Error:', error);
                                    console.log('Status:', status);
                                    console.log('Response Text:', xhr.responseText);
                                    console.log('Response Status:', xhr.status);
                                    alert('{l s='An error occurred. Please try again.' mod='aerpoints'}');
                                    btn.prop('disabled', false).text('{l s='Confirm Redemption' mod='aerpoints'}');
                                }
                            });
                        });
                    }
                });
            });
        });
    </script>
{/block}