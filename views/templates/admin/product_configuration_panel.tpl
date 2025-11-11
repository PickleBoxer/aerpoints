<div class="panel" id="aerpoints-explanation-panel">
    <div class="panel-heading">
        <i class="icon-star"></i>
        {l s='Product Points Configuration' mod='aerpoints'}
    </div>
    <div class="panel-body">
        <p>
            {l s='Configure how many points customers earn when buying products, and how many points are required to purchase products. Only products with at least one points value will appear in the list above.' mod='aerpoints'}
        </p>

        <h4>{l s='Add New Product Points Configuration' mod='aerpoints'}</h4>
        <form id="bulk_add_aerpoints_product" method="post" action="{$current}&amp;token={$token}">

            <!-- Product Filters -->
            <div class="row" style="margin-bottom: 15px;">
                <div class="col-md-3">
                    <label for="category_filter">{l s='Filter by Category' mod='aerpoints'}</label>
                    <select id="category_filter" class="form-control">
                        <option value="">{l s='All Categories' mod='aerpoints'}</option>
                        {if isset($categories)}
                            {foreach from=$categories item=category}
                                <option value="{$category.id_category}">{$category.name}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="manufacturer_filter">{l s='Filter by Manufacturer' mod='aerpoints'}</label>
                    <select id="manufacturer_filter" class="form-control">
                        <option value="">{l s='All Manufacturers' mod='aerpoints'}</option>
                        {if isset($manufacturers)}
                            {foreach from=$manufacturers item=manufacturer}
                                <option value="{$manufacturer.id_manufacturer}">{$manufacturer.name}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search_filter">{l s='Search by Name/Reference' mod='aerpoints'}</label>
                    <input type="text" id="search_filter" class="form-control" placeholder="{l s='Enter product name or reference...' mod='aerpoints'}">
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="button" id="load_products_btn" class="btn btn-info form-control">{l s='Load Products' mod='aerpoints'}</button>
                </div>
            </div>

            <div class="form-group">
                <label>{l s='Products' mod='aerpoints'}</label>
                <div class="panel panel-default" style="padding: 0;">
                    <div class="panel-body" style="padding: 0;">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-striped table-hover" id="products-table" style="margin-bottom: 0;">
                                <thead style="background-color: #f5f5f5;">
                                    <tr>
                                        <th width="5%" class="text-center">
                                            <input type="checkbox" id="select_all_products" />
                                        </th>
                                        <th width="8%" class="text-center">{l s='ID' mod='aerpoints'}</th>
                                        <th width="35%">{l s='Product Name' mod='aerpoints'}</th>
                                        <th width="15%">{l s='Reference' mod='aerpoints'}</th>
                                        <th width="12%" class="text-right">{l s='Price' mod='aerpoints'}</th>
                                        <th width="10%" class="text-center">{l s='Stock' mod='aerpoints'}</th>
                                        <th width="15%" class="text-center">{l s='Points Status' mod='aerpoints'}</th>
                                    </tr>
                                </thead>
                                <tbody id="products-table-body">
                                    <tr>
                                        <td colspan="7" class="text-center" style="padding: 30px;">
                                            <i class="icon-info-circle"></i>
                                            <em>{l s='Use filters above to load products' mod='aerpoints'}</em>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <p class="help-block">
                    <i class="icon-info"></i>
                    {l s='First use the filters above to load products, then select the products you want to configure using the checkboxes.' mod='aerpoints'}
                </p>

                <!-- Selection summary -->
                <div id="selection-summary" class="alert alert-info" style="display: none;">
                    <i class="icon-check"></i>
                    <span id="selection-count">0</span> {l s='product(s) selected' mod='aerpoints'}
                </div>

                <!-- Hidden inputs for selected products -->
                <div id="selected-products-inputs"></div>
            </div>
            <div class="form-group">
                <label for="points_earn">{l s='Fixed Points Earned' mod='aerpoints'}</label>
                <input type="number" id="points_earn" name="points_earn" class="form-control" min="0" step="1" placeholder="0" value="0">
                <p class="help-block">{l s='Fixed points customer earns when buying this product (overrides ratio if set)' mod='aerpoints'}</p>
            </div>
            <div class="form-group">
                <label for="points_ratio">{l s='Points Ratio' mod='aerpoints'}</label>
                <input type="number" id="points_ratio" name="points_ratio" class="form-control" min="0" max="100" step="0.01" placeholder="0.00" value="0">
                <p class="help-block">{l s='Points per euro (tax-excluded). Used only if Fixed Points is 0. Max: 100' mod='aerpoints'}</p>
            </div>
            <div id="bulk-preview" class="alert alert-info" style="display: none;">
                <i class="icon-info-circle"></i>
                <strong>{l s='Preview:' mod='aerpoints'}</strong>
                <span id="bulk-preview-text"></span>
            </div>
            <input type="hidden" name="submitBulkAddaerpoints_product" value="1" />
            <button type="submit" name="submitBulkAddaerpoints_product" class="btn btn-primary">{l s='Set Points' mod='aerpoints'}</button>
        </form>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    var ajaxUrl = '{$ajax_url}';
    var selectedProducts = [];

    // Function to load products based on filters
    function loadProducts() {
        var category_id = $('#category_filter').val();
        var manufacturer_id = $('#manufacturer_filter').val();
        var search = $('#search_filter').val();

        // Show loading state
        $('#products-table-body').html('<tr><td colspan="7" class="text-center"><i class="icon-spinner icon-spin"></i> {l s='Loading products...' mod='aerpoints'}</td></tr>');
        $('#load_products_btn').prop('disabled', true).text('{l s='Loading...' mod='aerpoints'}');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                category_id: category_id,
                manufacturer_id: manufacturer_id,
                search: search,
                limit: 100
            },
            success: function(response) {
                $('#load_products_btn').prop('disabled', false).text('{l s='Load Products' mod='aerpoints'}');
                $('#products-table-body').empty();

                if (response.products && response.products.length > 0) {
                    $.each(response.products, function(index, product) {
                        var statusBadge = '';
                        if (product.has_points) {
                            var pointsInfo = '';
                            if (product.current_points_earn > 0) {
                                pointsInfo = 'Fixed: ' + product.current_points_earn + ' pts';
                            } else if (product.current_points_ratio > 0) {
                                pointsInfo = 'Ratio: ' + parseFloat(product.current_points_ratio).toFixed(2) + '×';
                            }
                            statusBadge = '<span class="label label-success" title="' + pointsInfo + '">Configured</span>';
                        } else {
                            statusBadge = '<span class="label label-default">Not set</span>';
                        }

                        var manufacturerName = product.manufacturer_name ? product.manufacturer_name : '';
                        var reference = product.reference ? product.reference : '-';
                        var isChecked = selectedProducts.indexOf(product.id_product.toString()) !== -1 ? 'checked' : '';
                        var rowClass = product.has_points ? 'info' : '';

                        var row = '<tr class="' + rowClass + '">' +
                            '<td class="text-center"><input type="checkbox" class="product-checkbox" value="' + product.id_product + '" ' + isChecked + '/></td>' +
                            '<td class="text-center"><strong>' + product.id_product + '</strong></td>' +
                            '<td>' +
                                '<strong>' + product.name + '</strong>' +
                                (manufacturerName ? '<br><small class="text-muted"><i class="icon-building"></i> ' + manufacturerName + '</small>' : '') +
                            '</td>' +
                            '<td><code>' + reference + '</code></td>' +
                            '<td class="text-right">€ ' + product.price + '</td>' +
                            '<td class="text-center">' +
                                '<span class="badge badge-' + (product.quantity > 0 ? 'success' : 'danger') + '">' + product.quantity + '</span>' +
                            '</td>' +
                            '<td class="text-center">' + statusBadge + '</td>' +
                        '</tr>';

                        $('#products-table-body').append(row);
                    });

                    if (response.products.length >= 100) {
                        $('#products-table-body').append('<tr class="warning"><td colspan="7" class="text-center"><i class="icon-warning"></i> <em>{l s='Showing first 100 products. Use filters to narrow down results.' mod='aerpoints'}</em></td></tr>');
                    }
                } else {
                    $('#products-table-body').html('<tr><td colspan="7" class="text-center" style="padding: 30px;"><i class="icon-info-circle"></i> <em>{l s='No products found with current filters' mod='aerpoints'}</em></td></tr>');
                }

                updateSelectAllCheckbox();
            },
            error: function() {
                $('#products-table-body').html('<tr><td colspan="7" class="text-center text-danger"><em>{l s='Error loading products. Please try again.' mod='aerpoints'}</em></td></tr>');
                $('#load_products_btn').prop('disabled', false).text('{l s='Load Products' mod='aerpoints'}');
            }
        });
    }

    // Function to update hidden inputs for selected products
    function updateSelectedProductsInputs() {
        $('#selected-products-inputs').empty();
        $.each(selectedProducts, function(index, productId) {
            $('#selected-products-inputs').append('<input type="hidden" name="id_product[]" value="' + productId + '">');
        });

        // Update selection summary
        $('#selection-count').text(selectedProducts.length);
        if (selectedProducts.length > 0) {
            $('#selection-summary').show();
        } else {
            $('#selection-summary').hide();
        }

        // Update form validation
        var submitBtn = $('button[name="submitBulkAddaerpoints_product"]');
        if (selectedProducts.length > 0) {
            submitBtn.prop('disabled', false).removeClass('btn-default').addClass('btn-primary');
        } else {
            submitBtn.prop('disabled', true).removeClass('btn-primary').addClass('btn-default');
        }
    }

    // Function to update select all checkbox state
    function updateSelectAllCheckbox() {
        var totalCheckboxes = $('.product-checkbox').length;
        var checkedCheckboxes = $('.product-checkbox:checked').length;

        if (totalCheckboxes === 0) {
            $('#select_all_products').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#select_all_products').prop('indeterminate', false).prop('checked', true);
        } else if (checkedCheckboxes === 0) {
            $('#select_all_products').prop('indeterminate', false).prop('checked', false);
        } else {
            $('#select_all_products').prop('indeterminate', true);
        }
    }

    // Event handlers
    $('#load_products_btn').click(function() {
        loadProducts();
    });

    // Load products when Enter is pressed in search field
    $('#search_filter').keypress(function(e) {
        if (e.which == 13) {
            loadProducts();
        }
    });

    // Auto-load products when category or manufacturer changes
    $('#category_filter, #manufacturer_filter').change(function() {
        loadProducts();
    });

    // Handle select all checkbox
    $(document).on('change', '#select_all_products', function() {
        var isChecked = $(this).prop('checked');
        $('.product-checkbox').prop('checked', isChecked);

        // Update selected products array
        selectedProducts = [];
        if (isChecked) {
            $('.product-checkbox').each(function() {
                selectedProducts.push($(this).val());
            });
        }

        updateSelectedProductsInputs();
        updateSelectAllCheckbox();
    });

    // Handle individual product checkbox
    $(document).on('change', '.product-checkbox', function() {
        var productId = $(this).val();
        var isChecked = $(this).prop('checked');

        if (isChecked) {
            if (selectedProducts.indexOf(productId) === -1) {
                selectedProducts.push(productId);
            }
        } else {
            var index = selectedProducts.indexOf(productId);
            if (index !== -1) {
                selectedProducts.splice(index, 1);
            }
        }

        updateSelectedProductsInputs();
        updateSelectAllCheckbox();
    });

    // Form submission validation
    $('#bulk_add_aerpoints_product').on('submit', function(e) {
        if (selectedProducts.length === 0) {
            e.preventDefault();
            alert('{l s='Please select at least one product.' mod='aerpoints'}');
            return false;
        }

        var pointsEarn = parseInt($('#points_earn').val()) || 0;
        var pointsRatio = parseFloat($('#points_ratio').val()) || 0;

        if (pointsEarn === 0 && pointsRatio === 0) {
            e.preventDefault();
            alert('{l s='Please enter either Fixed Points or Points Ratio (at least one must be greater than 0).' mod='aerpoints'}');
            return false;
        }

        if (pointsRatio > 100) {
            e.preventDefault();
            alert('{l s='Points Ratio cannot exceed 100.' mod='aerpoints'}');
            return false;
        }
    });

    // Preview calculation for bulk points
    function updateBulkPreview() {
        var pointsEarn = parseInt($('#points_earn').val()) || 0;
        var pointsRatio = parseFloat($('#points_ratio').val()) || 0;

        if (selectedProducts.length === 0) {
            $('#bulk-preview').hide();
            return;
        }

        if (pointsEarn > 0) {
            $('#bulk-preview-text').html('{l s='Mode: Fixed Points' mod='aerpoints'} - <strong>' + pointsEarn + ' ★</strong>');
            $('#bulk-preview').show();
        } else if (pointsRatio > 0) {
            $('#bulk-preview-text').html('{l s='Mode: Ratio-based' mod='aerpoints'} - <strong>' + pointsRatio.toFixed(2) + '×</strong> {l s='(calculated from product price)' mod='aerpoints'}');
            $('#bulk-preview').show();
        } else {
            $('#bulk-preview').hide();
        }
    }

    // Update preview when values change
    $('#points_earn, #points_ratio').on('input', function() {
        updateBulkPreview();
    });

    // Update preview when selection changes
    function updateSelectedProductsInputs() {
        $('#selected-products-inputs').empty();
        $.each(selectedProducts, function(index, productId) {
            $('#selected-products-inputs').append('<input type="hidden" name="id_product[]" value="' + productId + '">');
        });

        // Update selection summary
        $('#selection-count').text(selectedProducts.length);
        if (selectedProducts.length > 0) {
            $('#selection-summary').show();
        } else {
            $('#selection-summary').hide();
        }

        // Update form validation
        var submitBtn = $('button[name="submitBulkAddaerpoints_product"]');
        if (selectedProducts.length > 0) {
            submitBtn.prop('disabled', false).removeClass('btn-default').addClass('btn-primary');
        } else {
            submitBtn.prop('disabled', true).removeClass('btn-primary').addClass('btn-default');
        }

        // Update preview
        updateBulkPreview();
    }

    // Initial load with no filters (will show first 100 products)
    loadProducts();

    // Initialize form button state
    updateSelectedProductsInputs();
});

</script>
