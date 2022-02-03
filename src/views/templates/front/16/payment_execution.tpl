{*
* 2007-2016 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2022 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}"
       title="{l s='Go back to the Checkout' mod='soisy'}">{l s='Checkout' mod='soisy'}</a>
    <span class="navigation-pipe">{$navigationPipe}</span>{l s='Pay with Soisy' mod='soisy'}
{/capture}

<h2>{l s='Order summary' mod='soisy'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
    <p class="warning">{l s='Your shopping cart is empty.' mod='soisy'}</p>
{else}
    <h3>{l s='Pay with Soisy' mod='soisy'}</h3>
    <p>{l s='The box below shows a simulation of the instalments. Continue to pay with Soisy.' mod='soisy'}</p>
    <div style="margin: 30px 0 35px 0;">
        {hook h='displayInternalLoanSimulation'}
    </div>
    <a href="{$link->getModuleLink('soisy', 'redirect', [], true)|escape:'htmlall':'UTF-8'}"
       class="button btn btn-default button-medium">
        <span>{l s='Confirm my order' mod='soisy'}<i class="icon-chevron-right right"></i></span>
    </a>
{/if}
