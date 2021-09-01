<?php
/**
 * 2007-2021 PrestaShop
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
 * @copyright 2007-2021 Soisy
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of Soisy
 */

namespace Soisy\Plugins\SoisyPlugin\Interfaces;

use Soisy\Plugins\SoisyPlugin\Models\LoanModel;

interface ILoanRepository
{
    /**
     * @param string $token
     * @param string $state
     * @param array  $log
     * @return bool
     */
    public function createLoan($token, $state, $log);

    /**
     * @param string $token
     * @return LoanModel|null
     */
    public function findLoanByToken($token);

    /**
     * @param int $cartId
     * @return LoanModel|null
     */
    public function findLoanByCartId($cartId);

    /**
     * @param string $state
     * @param array  $log
     * @param int    $id
     * @return bool
     */
    public function updateLoanStateById($state, $log, $id);

    /**
     * @param float $totalPaid
     * @param int   $id
     * @return bool
     */
    public function updateLoanTotalPaidById($totalPaid, $id);

    /**
     * @param int    $orderId
     * @param string $orderReference
     * @param int    $id
     * @return bool
     */
    public function updateShopOrderInfoByLoanId($orderId, $orderReference, $id);
}
