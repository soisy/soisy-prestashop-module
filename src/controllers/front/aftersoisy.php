<?php
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

if (!defined('_PS_VERSION_')) {
    exit;
}

class SoisyaftersoisyModuleFrontController extends ModuleFrontController
{
    /** @var soisy */
    public $module;

    public function initContent()
    {
        parent::initContent();

        $cartId = @Tools::getValue('cart_id');
        $status = @Tools::getValue('status');
        if ($status === 'success') {
            $cart = new Cart($cartId);

            if (Validate::isLoadedObject($cart) && $this->context->customer
                && (int)$cart->id_customer === $this->context->customer->id) {
                $so = SoisyOrder::findSoisyOrderByCartId($cartId);
                if (Validate::isLoadedObject($so)) {
                    $orderId = $so->id_order;

                    if ($orderId) {
                        Tools::redirectLink(
                            $this->context->link->getPageLink(
                                'order-confirmation',
                                true,
                                null,
                                array(
                                    'id_cart' => $cartId,
                                    'id_module' => $this->module->id,
                                    'id_order' => $orderId,
                                    'key' => $this->context->customer->secure_key,
                                )
                            )
                        );
                        exit;
                    }
                }
            }
        }

        if (!is_null($cartId)) {
            $this->module->loanController->tryToReloadCartIfStillUnbought($cartId);
        }

        if ($this->module->psVersion > 16) {
            $this->setTemplate('module:' . $this->module->name . '/views/templates/front/17/loan-not-achieved.tpl');
        } else {
            $this->setTemplate('16/loan-not-achieved.tpl');
        }
    }
}
