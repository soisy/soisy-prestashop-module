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

namespace Soisy\Plugins\SoisyPlugin;

use Soisy\Plugins\SoisyPlugin\Exceptions\LoanControllerException;
use Soisy\Plugins\SoisyPlugin\Interfaces\ILoanRepository;
use Soisy\Plugins\SoisyPlugin\Interfaces\ILogger;
use Soisy\Plugins\SoisyPlugin\Interfaces\IShopApi;
use Soisy\Plugins\SoisyPlugin\Interfaces\ITranslator;
use Soisy\Plugins\SoisyPlugin\Models\IShopOrder;
use Soisy\Plugins\SoisyPlugin\Models\LoanModel;

class LoanController
{
    /**
     * @param array $log
     * @return string The redirect URL in which the user is going to customize his/her loan request.
     * @throws LoanControllerException
     */
    public function createLoanFromCurrentCart($log)
    {
        $loanCreationModel = $this->shopApi->createLoanFromCartByApi();
        if (is_null($loanCreationModel)) {
            throw new LoanControllerException($this->tr->t('Unable to create loan'));
        }
        $this->shopApi->resetCart();
        $token = $loanCreationModel->getToken();
        $redirectUrl = $loanCreationModel->getRedirectUrl();

        $hasBeenCreated = $this->loanRepository->createLoan($token, LoanModel::CUSTOMER_REDIRECTED, $log);
        if (!$hasBeenCreated) {
            throw new LoanControllerException($this->tr->t('Unable to save created loan'));
        }
        return $redirectUrl;
    }

    /**
     * @param string $loanToken
     * @param array  $log
     * @throws LoanControllerException
     */
    public function requestPreAuthorized($loanToken, $log, $loanTokenSaved)
    {
        $loan = $this->loanRepository->findLoanByToken($loanToken);
        if (is_null($loan)) {
            throw new LoanControllerException($this->tr->t('Unable to find loan'));
        }

        $wasUnbought = $this->shopApi->loadUnboughtCartFromLoanModel($loan);
        if (!$wasUnbought) {
            throw new LoanControllerException($this->tr->t('Cart was already bought'));
        }

        $orderCreationModel = $this->shopApi->buyCart($loanTokenSaved);
        if (is_null($orderCreationModel)) {
            throw new LoanControllerException($this->tr->t('Unable to create shop order'));
        }

        $hasBeenUpdated = $this->loanRepository->updateShopOrderInfoByLoanId(
            $orderCreationModel->getOrderId(),
            $orderCreationModel->getOrderReference(),
            $loan->getId()
        );
        if (!$hasBeenUpdated) {
            throw new LoanControllerException($this->tr->t('Unable to update loan data from order'));
        }

        $hasBeenUpdated = $this->loanRepository->updateLoanStateById(
            LoanModel::REQUEST_PREAUTHORIZED,
            $log,
            $loan->getId()
        );
        if (!$hasBeenUpdated) {
            throw new LoanControllerException($this->tr->t('Unable to update loan state'));
        }
    }

    /**
     * @param string $loanToken
     * @param array  $log
     * @throws LoanControllerException
     */
    public function requestCompleted($loanToken, $log)
    {
        $loan = $this->loanRepository->findLoanByToken($loanToken);
        if (is_null($loan)) {
            throw new LoanControllerException($this->tr->t('Unable to find loan'));
        }

        $shopOrderId = $loan->getShopOrderId();
        if (is_null($shopOrderId)) {
            throw new LoanControllerException($this->tr->t('Found loan without an order'));
        }
        $hasBeenUpdated = $this->shopApi->updateOrderStateByOrderId(IShopOrder::REQUEST_COMPLETED, $shopOrderId);
        if (!$hasBeenUpdated) {
            throw new LoanControllerException($this->tr->t('Unable to update shop order'));
        }

        $hasBeenUpdated = $this->loanRepository->updateLoanStateById(
            LoanModel::REQUEST_COMPLETED,
            $log,
            $loan->getId()
        );
        if (!$hasBeenUpdated) {
            throw new LoanControllerException($this->tr->t('Unable to update loan state'));
        }
    }

    /**
     * @param string $loanToken
     * @param array  $log
     * @throws LoanControllerException
     */
    public function loanApproved($loanToken, $log)
    {
        $loan = $this->loanRepository->findLoanByToken($loanToken);
        if (is_null($loan)) {
            throw new LoanControllerException($this->tr->t('Unable to find loan'));
        }

        $shopOrderId = $loan->getShopOrderId();
        if (is_null($shopOrderId)) {
            throw new LoanControllerException($this->tr->t('Unable to find related shop order'));
        }

        $hasBeenUpdated = $this->shopApi->updateOrderStateByOrderId(IShopOrder::LOAN_APPROVED, $shopOrderId);
        if (!$hasBeenUpdated) {
            throw new LoanControllerException($this->tr->t('Unable to update shop order'));
        }

        $hasBeenUpdated = $this->loanRepository->updateLoanStateById(LoanModel::APPROVED, $log, $loan->getId());
        if (!$hasBeenUpdated) {
            throw new LoanControllerException($this->tr->t('Unable to update loan state'));
        }
    }

    /**
     * @param string $loanToken
     * @param float  $totalPaid
     * @param array  $log
     * @throws LoanControllerException
     */
    public function paidLoan($loanToken, $totalPaid, $log)
    {
        $loan = $this->loanRepository->findLoanByToken($loanToken);
        if (is_null($loan)) {
            throw new LoanControllerException($this->tr->t('Unable to find loan'));
        }

        $shopOrderId = $loan->getShopOrderId();
        if (is_null($shopOrderId)) {
            throw new LoanControllerException($this->tr->t('Found loan without an order'));
        }

        $orderTotal = $this->shopApi->findTotalPaidByOrderId($shopOrderId);
        if (is_null($orderTotal)) {
            throw new LoanControllerException($this->tr->t('Unable to find order total'));
        }

        if ($orderTotal == $totalPaid) {
            $hasBeenUpdated = $this->shopApi->updateOrderStateByOrderId(IShopOrder::LOAN_PAID, $shopOrderId);
            if (!$hasBeenUpdated) {
                throw new LoanControllerException($this->tr->t('Unable to update shop order'));
            }
        } else {
            $hasBeenUpdated = $this->shopApi->cancelOrderById($shopOrderId);
            if (!$hasBeenUpdated) {
                throw new LoanControllerException($this->tr->t('Unable to cancel shop order'));
            }
        }
        $hasBeenUpdated = $this->loanRepository->updateLoanTotalPaidById($totalPaid, $loan->getId());
        if (!$hasBeenUpdated) {
            throw new LoanControllerException($this->tr->t('Unable to update loan total'));
        }
        $hasBeenUpdated = $this->loanRepository->updateLoanStateById(LoanModel::PAID, $log, $loan->getId());
        if (!$hasBeenUpdated) {
            throw new LoanControllerException($this->tr->t('Unable to update loan state'));
        }
    }

    /**
     * @param string $loanToken
     * @param array  $log
     * @throws LoanControllerException
     */
    public function loanRejected($loanToken, $log)
    {
        $loan = $this->loanRepository->findLoanByToken($loanToken);
        if (is_null($loan)) {
            throw new LoanControllerException($this->tr->t('Unable to find loan'));
        }

        $shopOrderId = $loan->getShopOrderId();
        if (!is_null($shopOrderId)) {
            $hasBeenUpdated = $this->shopApi->updateOrderStateByOrderId(IShopOrder::LOAN_REJECTED, $shopOrderId);
            if (!$hasBeenUpdated) {
                throw new LoanControllerException($this->tr->t('Unable to update shop order'));
            }
        }
        $hasBeenUpdated = $this->loanRepository->updateLoanStateById(LoanModel::REJECTED, $log, $loan->getId());
        if (!$hasBeenUpdated) {
            throw new LoanControllerException($this->tr->t('Unable to update loan state'));
        }
    }


    /** @param int $cartId */
    public function tryToReloadCartIfStillUnbought($cartId)
    {
        $loan = $this->loanRepository->findLoanByCartId($cartId);
        if (!is_null($loan)) {
            $this->shopApi->loadUnboughtCartFromLoanModel($loan);
        }
    }


    // DEPENDENCIES

    /** @var ILoanRepository */
    private $loanRepository;

    /** @param ILoanRepository $soisyRepository */
    public function setLoanRepository($soisyRepository)
    {
        $this->loanRepository = $soisyRepository;
    }

    /** @var IShopApi */
    private $shopApi;

    /** @param IShopApi $shopApi */
    public function setShopApi($shopApi)
    {
        $this->shopApi = $shopApi;
    }

    /** @var ILogger */
    private $logger;

    /** @param ILogger $logger */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /** @var ITranslator */
    private $tr;

    /** @param ITranslator $tr */
    public function setTranslator($tr)
    {
        $this->tr = $tr;
    }
}
