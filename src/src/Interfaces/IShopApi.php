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

namespace Soisy\Plugins\SoisyPlugin\Interfaces;

use Soisy\Plugins\SoisyPlugin\Models\LoanCreationModel;
use Soisy\Plugins\SoisyPlugin\Models\LoanModel;
use Soisy\Plugins\SoisyPlugin\Models\OrderCreationModel;

interface IShopApi
{
    /** @param LoanModel $loanModel */
    public function loadUnboughtCartFromLoanModel($loanModel);

    /** @return OrderCreationModel|null */
    public function buyCart($loanTokenSaved);

    public function resetCart();

    /**
     * @param int $orderState From IShopOrder, not depending on Implementations.
     * @param int $orderId
     */
    public function updateOrderStateByOrderId($orderState, $orderId);

    /** @return LoanCreationModel|null */
    public function createLoanFromCartByApi();

    /** @param int $orderId */
    public function cancelOrderById($orderId);

    /**
     * @param int $orderId
     * @return float|null
     */
    public function findTotalPaidByOrderId($orderId);
}
