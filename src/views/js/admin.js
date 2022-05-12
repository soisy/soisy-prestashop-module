/**
 * 2007-2022 PrestaShop
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
 * @author    Soisy
 * @copyright 2007-2022 Soisy
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of Soisy
 */

jQuery(function ($) {
    if ($('#curr_products li').length > 0) {
        $('span#empty').hide();
    }

    $('#products').autocomplete(soisy_products_ajax_url, {
        minChars: 1,
        autoFill: true,
        max: 200,
        matchContains: true,
        mustMatch: true,
        scroll: true,
        cacheLength: 0,
        extraParams: {excludeIds: getProductExcludedIds()},
        formatItem: function (item) {
            return item[1] + ' - ' + item[0];
        }
    }).result(function (event, data, formatted) {
        if (data == null)
            return false;
        var id = data[1];
        var name = data[0];
        if ($('#curr_products li').length == 0) {
           $('span#empty').hide();
        }
        $('#curr_products').append('<li>' + name + '<a href="javascript:;" class="del_product"><img src="../img/admin/delete.gif" /></a><input type="hidden" name="id_product[]" value="' + id + '" /></li>');

        $('#products').setOptions({
            extraParams: {
                excludeIds: getProductExcludedIds()
            }
        });
    });
    $('#curr_products').delegate('.del_product', 'click', function () {
        $(this).closest('li').remove();
        if ($('#curr_products li').length == 0 ) {
            $('span#empty').show();
        }
        $('#products').setOptions({
            extraParams: {
                excludeIds: getProductExcludedIds()
            }
        });
    });
});

var getProductExcludedIds = function () {
    var excludeIds = '';
    $(':hidden[name="id_product[]"]').each(function () {
        excludeIds += $(this).val() + ',';
    });
    return excludeIds.substr(0, excludeIds.length - 1);
}