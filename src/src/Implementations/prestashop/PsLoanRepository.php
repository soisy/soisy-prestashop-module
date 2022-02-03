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

use Soisy\Plugins\SoisyPlugin\Interfaces\ILoanRepository;
use Soisy\Plugins\SoisyPlugin\Models\LoanModel;

class PsLoanRepository implements ILoanRepository
{
    public function createLoan($token, $state, $log)
    {
        $so = new \SoisyOrder();

        $so->token = $token;
        $so->sandbox = $this->module->sandboxMode;
        $so->id_payment = $this->module->id;
        $so->last_event_id = $state;
        $so->callbacks_history = \SoisyOrder::createHistoryFromPast($log);
        $so->total_paid = 0;

        $so->id_cart = $this->context->cart->id;
        $so->id_shop = $this->context->cart->id_shop;
        $so->total_cart = $this->context->cart->getOrderTotal();
        $so->id_customer = $this->context->cart->id_customer;

        // Saving context
        $c = new \stdClass();
        $c->id_cart = $this->context->cart->id;
        $c->id_customer = $this->context->customer->id;
        $c->id_language = $this->context->language->id;
        $c->id_currency = $this->context->currency->id;
        $c->id_country = $this->context->country->id;
        $c->id_shop = $this->context->shop->id;
        $so->context = json_encode($c);

        return !!$so->save();
    }


    public function findLoanByToken($token)
    {
        $so = \SoisyOrder::findSoisyOrderByToken($token);
        if (!\Validate::isLoadedObject($so)) {
            return null;
        }
        $idOrder = (int)$so->id_order ?: null;
        return new LoanModel($so->id, $idOrder, $so->last_event_id, $so->context);
    }

    public function findLoanByCartId($cartId)
    {
        $so = \SoisyOrder::findSoisyOrderByCartId($cartId);
        if (!\Validate::isLoadedObject($so)) {
            return null;
        }
        $idOrder = (int)$so->id_order ?: null;
        return new LoanModel($so->id, $idOrder, $so->last_event_id, $so->context);
    }

    public function updateLoanStateById($state, $log, $id)
    {
        $so = new \SoisyOrder($id);
        if (!\Validate::isLoadedObject($so)) {
            return false;
        }
        $so->last_event_id = $state;
        $so->callbacks_history = \SoisyOrder::createHistoryFromPast($log, $so->callbacks_history);
        return !!$so->update();
    }

    public function updateLoanTotalPaidById($totalPaid, $id)
    {
        $so = new \SoisyOrder($id);
        if (!\Validate::isLoadedObject($so)) {
            return false;
        }
        $so->total_paid = $totalPaid;
        return !!$so->update();
    }

    public function updateShopOrderInfoByLoanId($orderId, $orderReference, $id)
    {
        $so = new \SoisyOrder($id);
        if (!\Validate::isLoadedObject($so)) {
            return false;
        }
        $so->id_order = $orderId;
        $so->order_reference = $orderReference;
        return !!$so->update();
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
