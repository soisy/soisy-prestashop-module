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

namespace Soisy\Plugins\SoisyPlugin\Implementations\PrestaShop;

use Exception;
use Soisy\Plugins\SoisyPlugin\Interfaces\IShopApi;
use Soisy\Plugins\SoisyPlugin\Models\IShopOrder;
use Soisy\Plugins\SoisyPlugin\Models\LoanCreationModel;
use Soisy\Plugins\SoisyPlugin\Models\OrderCreationModel;

class PsShopApi implements IShopApi
{
    public function loadUnboughtCartFromLoanModel($loanModel)
    {
        $savedContext = json_decode($loanModel->getContext());
        if ($this->module->psVersion > 16) {
            $orderId = \Order::getIdByCartId($savedContext->id_cart);
        } else {
            $orderId = \Order::getOrderByCartId($savedContext->id_cart);
        }
        if ($orderId !== false) {
            return false;
        }
        $this->context->language = new \Language($savedContext->id_language);
        $this->context->shop = new \Shop($savedContext->id_shop, $this->context->language->id);
        $this->context->currency = new \Currency($savedContext->id_currency, $this->context->language->id, $this->context->shop->id);
        $this->context->customer = new \Customer($savedContext->id_customer);
        $this->context->country = new \Country($savedContext->id_country);
        $this->context->cart = new \Cart($savedContext->id_cart, $this->context->language->id);
        return true;
    }


    public function buyCart($loanTokenSaved)
    {
        $extra_vars = [
            'transaction_id' => $loanTokenSaved,
        ];
        try {
            $psOrderStateId = $this->fromAbstractOrderState2PsOrderState(IShopOrder::REQUEST_PREAUTHORIZED);
            $cartTotal = $this->context->cart->getOrderTotal(true, \Cart::BOTH);

            if (version_compare(_PS_VERSION_, '1.7.6.0', '>=')) {
                $kernel = new \AppKernel('prod', false);
                $kernel->boot();
                $this->context->container = $kernel->getContainer();
            }
            $this->module->validateOrder(
                (int)$this->context->cart->id,
                $psOrderStateId,
                (float)$cartTotal,
                $this->module->displayName,
                null,
                $extra_vars,
                (int)$this->context->currency->id,
                false,
                $this->context->customer->secure_key,
                $this->context->shop
            );
            return new OrderCreationModel(
                (int)$this->module->currentOrder,
                (string)$this->module->currentOrderReference
            );
        } catch (Exception $e) {
            return null;
        }
    }


    public function resetCart()
    {
        $cart = $this->context->cart;
        if (\Validate::isLoadedObject($cart)) {
            \PrestaShopLogger::addLog(
                'Frontcontroller::init - Cart cannot be loaded or an order has already been placed using this cart',
                1,
                null,
                'Cart',
                (int)$this->context->cookie->id_cart,
                true
            );
            unset($this->context->cookie->id_cart, $cart, $this->context->cookie->checkedTOS);
            $this->context->cookie->check_cgv = false;
        }
    }


    public function updateOrderStateByOrderId($orderState, $orderId)
    {
        $psOrderState = $this->fromAbstractOrderState2PsOrderState($orderState);
        try {
            $order = new \Order($orderId);
            if (!\Validate::isLoadedObject($order)) {
                return false;
            }
            $this->context->currency = new \Currency($order->id_currency);
            $order->setCurrentState($psOrderState);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }


    public function createLoanFromCartByApi()
    {
        $result = $this->createSoisyOrder($this->module->soisyApi);
        if ($result['status'] !== 'ok') {
            return null;
        }
        $token = (string)$result['data']['token'];
        $redirectUrl = (string)$result['data']['redirectUrl'];
        return new LoanCreationModel($token, $redirectUrl);
    }


    public function cancelOrderById($orderId)
    {
        $psOrderState = \Configuration::get('PS_OS_ERROR'); // Stato Ordine PrestaShop "Errore di pagamento"
        try {
            $order = new \Order($orderId);
            if (!\Validate::isLoadedObject($order)) {
                return false;
            }
            if (version_compare(_PS_VERSION_, '1.7.6.0', '>=')) {
                $kernel = new \AppKernel('prod', false);
                $kernel->boot();
                $this->context->container = $kernel->getContainer();
            }
            $order->setCurrentState($psOrderState);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }


    public function findTotalPaidByOrderId($orderId)
    {
        try {
            $order = new \Order($orderId);
            if (!\Validate::isLoadedObject($order)) {
                return null;
            }
            return (float)$order->total_paid;
        } catch (Exception $e) {
            return null;
        }
    }


    // PRESTASHOP UTILS

    /** @param \SoisyApi $api */
    private function createSoisyOrder($api)
    {
        $ctx = \Context::getContext();

        $amount = $ctx->cart->getOrderTotal();
        if ($amount < 100 or $amount > 15000) {
            return false;
        }

        $invoiceAddress = new \Address($ctx->cart->id_address_invoice);
        $state = new \State($invoiceAddress->id_state);

        // TODO: Procedure for guests
        $email = $ctx->customer->email;
        $firstname = $ctx->customer->firstname;
        $lastname = $ctx->customer->lastname;
        $amountInCents = (int)($ctx->cart->getOrderTotal() * 100);

        $vatId = '';
        $vatCountry = '';
        $fiscalCode = $mobilePhone = $city = $province = $address = $civicNumber = $postalCode = ''; // To Avoid Exception from Soisy WS

        $zeroInterestRate = 'false';
        $zeroInterestRateConfig = \Configuration::get('SOISY_ZERO_INTEREST_RATE');
        if (isset($zeroInterestRateConfig) && $zeroInterestRateConfig) {
            $zeroInterestRate = 'true';
        }

        $successUrl = $this->context->link->getModuleLink(
            'soisy',
            'aftersoisy',
            array(
                'status' => 'success',
                'cart_id' => $this->context->cart->id,
            ),
            true
        );
        $errorUrl = $this->context->link->getModuleLink(
            'soisy',
            'aftersoisy',
            array(
                'cart_id' => $this->context->cart->id,
            ),
            true
        );
        $callbackUrl = $this->context->link->getModuleLink('soisy', 'callback', array(), true);

        return $api->createOrder(
            $email,
            $firstname,
            $lastname,
            $amountInCents,
            $vatId,
            $vatCountry,
            $fiscalCode,
            $mobilePhone,
            $city,
            $province,
            $address,
            $civicNumber,
            $postalCode,
            $zeroInterestRate,
            $successUrl,
            $errorUrl,
            $callbackUrl
        );
    }

    /**
     * @param int $orderState
     * @throws Exception
     */
    private function fromAbstractOrderState2PsOrderState($orderState)
    {
        switch ($orderState) {
            case IShopOrder::REQUEST_PREAUTHORIZED:
                return \Configuration::get(
                    $this->module->orderStates[\SoisyConfiguration::SOISY_ORDER_STATE_LOAN_APPROVED]['key']
                );
            case IShopOrder::REQUEST_COMPLETED:
                return \Configuration::get(
                    $this->module->orderStates[\SoisyConfiguration::SOISY_ORDER_STATE_REQUEST_COMPLETED]['key']
                );
            case IShopOrder::LOAN_APPROVED:
                return \Configuration::get(
                    $this->module->orderStates[\SoisyConfiguration::SOISY_ORDER_STATE_LOAN_VERIFIED]['key']
                );
            case IShopOrder::LOAN_PAID:
                return \Configuration::get(
                    $this->module->orderStates[\SoisyConfiguration::SOISY_ORDER_STATE_LOAN_PAID]['key']
                );
            case IShopOrder::LOAN_REJECTED:
                return \Configuration::get(
                    $this->module->orderStates[\SoisyConfiguration::SOISY_ORDER_STATE_USER_WAS_REJECTED]['key']
                );
            default:
                throw new Exception('Not found');
        }
    }


    // DEPENDENCIES

    /** @var \soisy */
    public $module;

    /** @var \Context */
    public $context;

    /**
     * @param \soisy   $module
     * @param \Context $context
     */
    public function __construct($module, $context)
    {
        $this->module = $module;
        $this->context = $context;
    }
}
