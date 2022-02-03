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

;(function () {
    let IS_CONSOLE_LOG_ENABLED = true;
    let isAdvancedConfigVisible = false;

    // Log utility
    function console_log(obj) {
        if (IS_CONSOLE_LOG_ENABLED) {
            console.log('SOISY >', obj);
        }
    }

    function console_error(obj) {
        if (IS_CONSOLE_LOG_ENABLED) {
            console.error('SOISY >', obj);
        }
    }

    console_log('PS16');

    $(document).ready(function ($) {
        if ($('#bookmark_advanced_settings').length) {
            let $main_parent = $('#bookmark_advanced_settings').closest('.panel');
            if ($main_parent.length) {
                $main_parent.find('.form-wrapper').hide();
                $main_parent.find('.panel-footer').hide();

                $main_parent.find('.panel-heading').css('cursor', 'pointer');
                $main_parent.find('.panel-heading').on('click', function () {
                    if (!isAdvancedConfigVisible) {
                        $main_parent.find('.form-wrapper').show();
                        $main_parent.find('.panel-footer').show();
                        isAdvancedConfigVisible = true;
                    } else {
                        $main_parent.find('.form-wrapper').hide();
                        $main_parent.find('.panel-footer').hide();
                        isAdvancedConfigVisible = false;
                    }
                });
            }


        }
    });

    $(window).load(function () {
    });
})();
