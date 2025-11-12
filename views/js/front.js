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
});

function moveAerpointsPreviewToCart() {
    var $aerpointsTable = $('#aerpoints-cart-table');
    var $cartTable = $('#cart_summary');

    if (!$aerpointsTable.length || !$cartTable.length) {
        return;
    }

    // Remove existing aerpoints rows to avoid duplicates
    //$('.aerpoints-row, .cart_total_points').remove();

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
