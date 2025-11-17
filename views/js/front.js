/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

$(document).ready(function() {
    // Move AerPoints cart preview into cart summary table
    moveAerpointsPreviewToCart();

    // Re-run after AJAX cart updates
    $(document).ajaxComplete(function() {
        moveAerpointsPreviewToCart();
    });

    // Display user points in header
    displayUserPointsInHeader();

    // Add points icon to specific category links
    addPointsIconToCategories();

    // Handle cart notification for points
    initAerpointsCartNotification();
});

function moveAerpointsPreviewToCart() {
    var $aerpointsTable = $('#aerpoints-cart-table');
    var $cartTable = $('#cart_summary');

    if (!$aerpointsTable.length || !$cartTable.length) {
        return;
    }

    // Remove existing aerpoints rows from the CART TABLE to avoid duplicates
    $cartTable.find('.aerpoints-row, .cart_total_points').remove();

    // Clone the tbody (detail rows) and insert it into the cart table tbody
    var $tbody = $aerpointsTable.find('tbody').clone().show();
    if ($tbody.find('tr').length > 0) {
        var $lastTbody = $cartTable.find('tbody').last();
        if ($lastTbody.length) {
            $tbody.insertAfter($lastTbody);
        }
    }

    // Clone the tfoot (total row) and insert it into the cart table tfoot after the total price row
    var $tfootRow = $aerpointsTable.find('tfoot tr').clone();
    if ($tfootRow.length) {
        var $cartTfoot = $cartTable.find('tfoot');
        var $totalPriceRow = $cartTfoot.find('.cart_total_price').last();

        if ($totalPriceRow.length) {
            $tfootRow.insertAfter($totalPriceRow);
        } else {
            // Fallback: append to tfoot if total price row not found
            $cartTfoot.append($tfootRow);
        }
    }

    // Remove existing aerpoints rows to avoid duplicates
    //$('.aerpoints-row, .cart_total_points').remove();
}

function displayUserPointsInHeader() {
    if (typeof aerpoints_user_points === 'undefined') {
        return;
    }

    var $headerUserInfo = $('.header_user_info.hidden-xs');
    if (!$headerUserInfo.length || $headerUserInfo.find('.aerpoints-balance').length) {
        return;
    }

    var pointsHtml = '<span class="aerpoints-balance" style="margin-left: 10px;">' +
        '<strong>' + aerpoints_user_points + '</strong>' +
        '<img src="/modules/aerpoints/views/img/points-icon.svg" alt="points" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 3px; filter: brightness(0) invert(1);" />' +
        '</span>';

    $headerUserInfo.find('.account').append(pointsHtml);
}

function addPointsIconToCategories() {
    var categoryIds = [69, 387, 468, 1595, 1175, 73, 817, 458, 1680, 614, 74, 1487, 1462];
    var iconHtml = ' <img src="/modules/aerpoints/views/img/points-icon.svg" alt="points" style="width: 14px; height: 14px; vertical-align: middle; margin-left: 3px;" />';

    $('#56-innertab-54 a').each(function() {
        var href = $(this).attr('href');
        if (href) {
            for (var i = 0; i < categoryIds.length; i++) {
                if (href.indexOf('/' + categoryIds[i] + '-') !== -1 && !$(this).find('img').length) {
                    $(this).append(iconHtml);
                    break;
                }
            }
        }
    });
}

function initAerpointsCartNotification() {
    var pendingProducts = {};
    
    console.log('AerPoints cart notification initialized');
    
    // Store product ID when add to cart button is clicked
    $(document).on('click', '.ajax_add_to_cart_button, #buttoncat, button[name="Submit"]', function(e) {
        var $button = $(this);
        var productId = $button.data('id-product') || $button.attr('data-id-product');
        
        console.log('Button clicked, product ID:', productId);
        
        if (productId) {
            // Mark product as pending, quantity will come from AJAX data
            pendingProducts[productId] = true;
            console.log('Pending products:', pendingProducts);
        }
    });

    // When AJAX completes after button click, show notification
    $(document).ajaxComplete(function(event, xhr, settings) {
        console.log('AJAX completed, pending products:', pendingProducts);
        console.log('AJAX URL:', settings.url);
        console.log('AJAX data:', settings.data);
        
        if (Object.keys(pendingProducts).length === 0) return;
        
        try {
            var response = JSON.parse(xhr.responseText);
            console.log('Response parsed:', response);
            console.log('hasError:', response.hasError);
            
            if (response.hasError === false && settings.data) {
                // Extract product ID and quantity from AJAX data
                var dataParams = settings.data.split('&');
                var productId = null;
                var quantity = 1;
                
                for (var i = 0; i < dataParams.length; i++) {
                    var param = dataParams[i].split('=');
                    if (param[0] === 'id_product') {
                        productId = param[1];
                    } else if (param[0] === 'qty') {
                        quantity = parseInt(param[1]) || 1;
                    }
                }
                
                console.log('Extracted from AJAX - product ID:', productId, 'quantity:', quantity);
                
                // Check if this product is in our pending list
                if (productId && pendingProducts[productId]) {
                    console.log('Getting notification for product:', productId, 'quantity:', quantity);
                    
                    var $template = $('.aerpoints-cart-notification-template[data-product-id="' + productId + '"]').first();
                    console.log('Template found:', $template.length);
                    
                    if ($template.length) {
                        var $notification = $template.find('.aerpoints-notification-content').clone();
                        console.log('Notification cloned:', $notification);
                        
                        // Update points if quantity > 1
                        if (quantity > 1) {
                            console.log('Updating points for quantity:', quantity);
                            $notification.find('strong, span').each(function() {
                                var text = $(this).text();
                                var pointsMatch = text.match(/(\d+)\s+loyalty points/i);
                                if (pointsMatch) {
                                    var points = parseInt(pointsMatch[1]);
                                    var totalPoints = points * quantity;
                                    console.log('Points updated:', points, 'x', quantity, '=', totalPoints);
                                    $(this).text($(this).text().replace(pointsMatch[0], totalPoints + ' loyalty points'));
                                }
                            });
                        }
                        
                        $('#aerpoints_cart_notification').html($notification).hide().fadeIn('slow');
                        console.log('Notification displayed');
                    } else {
                        // No template found, clean up any old notification
                        $('#aerpoints_cart_notification').empty();
                        console.log('No template found, notification area cleared');
                    }
                    
                    // Remove this product from pending
                    delete pendingProducts[productId];
                    console.log('Product removed from pending, remaining:', pendingProducts);
                }
            }
        } catch(e) {
            console.error('Error processing notification:', e);
        }
    });
}
