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
    // AerPoints calculation preview
    function updateAerpointsPreview() {
        var fixedPoints = parseInt($('#aerpoints_earn').val()) || 0;
        var ratio = parseFloat($('#aerpoints_ratio').val()) || 0;
        var examplePrice = 10; // Example calculation with €10 product

        var mode = '';
        var calculatedPoints = 0;

        if (fixedPoints > 0) {
            mode = 'Fixed Points';
            calculatedPoints = fixedPoints;
        } else if (ratio > 0) {
            mode = 'Ratio-based (' + ratio + '× per €1)';
            calculatedPoints = Math.floor(examplePrice * ratio);
        } else {
            mode = 'Not configured';
            calculatedPoints = 0;
        }

        $('#preview-mode').text('Mode: ' + mode);
        $('#preview-calc').text('For a €' + examplePrice + ' product: ' + calculatedPoints + ' points');
    }

    // Validate ratio max value
    $('#aerpoints_ratio').on('input', function() {
        var val = parseFloat($(this).val());
        if (val > 100) {
            $(this).val(100);
        }
        if (val < 0) {
            $(this).val(0);
        }
        updateAerpointsPreview();
    });

    // Update preview when fixed points change
    $('#aerpoints_earn').on('input', function() {
        updateAerpointsPreview();
    });

    // Initial preview update
    if ($('#aerpoints_earn').length || $('#aerpoints_ratio').length) {
        updateAerpointsPreview();
    }
});
