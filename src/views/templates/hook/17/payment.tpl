{*
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
*}
<div class="row">
    <div class="col-xs-12 col-md-6">
        <p class="payment_module" id="soisy_basic_payment_button">
            <a href="{$link->getModuleLink('soisy', 'redirect', [], true)|escape:'htmlall':'UTF-8'}"
               title="{l s='Pay with Soisy' mod='soisy'}">
                <img src="{$module_dir|escape:'htmlall':'UTF-8'}/logo.png"
                     alt="{l s='Pay with my payment module' mod='soisy'}" width="32" height="32"/>
                {l s='Pay with Soisy (you will be redirected to soisy.it)' mod='soisy'}
            </a>
        </p>
    </div>
</div>
