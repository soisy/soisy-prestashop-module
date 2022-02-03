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

    var last_monitored_price = 0;
    var last_monitored_price_element;

    function console_log(text) {
        console.log('SOISY >>>', text);
    }

    function console_table(obj) {
        console.table('SOISY >>>', obj);
    }

    $(document).ready(function () {
        //console_log('Soisy Product Info handler');

        // Preleva HTML da mostrare
        function get_info() {
            //console_log('Trying get info...');

            var current_price = get_current_price();
            last_monitored_price = current_price;
            //console_log('current_price: ' + current_price);

            $('.soisy-loan-quote').attr('amount', last_monitored_price);
        }

        function monitor_last_price_element() {
            var maybePriceElementValue;

            if (soisy_ps_version === 16) {
                maybePriceElementValue = $(last_monitored_price_element).text().replace(',','.').replace(/[^\d.-]/g,'');
            }else{
                maybePriceElementValue = $(last_monitored_price_element).attr('content');
            }

            if (parseFloat(last_monitored_price) !== parseFloat(maybePriceElementValue)) {
                //console_log('detected product update');
                get_info();
            }else{
                setTimeout(function () {
                    monitor_last_price_element();
                }, 1500);
            }
        }

        // determina prezzo corrente 1.6 e 1.7
        function get_current_price() {
            var maybePriceElementValue = "0.00";

            $("[itemtype=\"http://schema.org/Product\"], [itemtype=\"https://schema.org/Product\"]").each(function () {
                var maybePriceElement = $(this).find('[itemprop="price"]').eq(0);
                if ($(maybePriceElement).length) {
                    if (soisy_ps_version === 16) {
                        maybePriceElementValue = $(maybePriceElement).text().replace(',','.').replace(/[^\d.-]/g,'');
                    }else{
                        maybePriceElementValue = $(maybePriceElement).attr('content');
                    }
                    last_monitored_price_element = maybePriceElement;
                    return false;
                }
            });

            return maybePriceElementValue;
        }

        // determina se il prodotto può visualizzare l'informativa
        function detect_product_wrapper() {
            if (soisy_controller === 'product') {
                if ($('.soisy-product-loan-wrapper').length) {
                    //console_log('Info product wrapper detected');
                    get_info();
                }
            }
        }

        detect_product_wrapper();

        // determina eventuali aggiornamenti del prodotto
        $(document).ajaxSuccess(function (event, xhr, settings) {
            if (typeof settings.url !== "undefined" && settings.url.indexOf('controller=product') !== -1) {
                //console_log('In page product update detected');
                get_info();
            }
        });
    });

})();