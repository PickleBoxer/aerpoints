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
