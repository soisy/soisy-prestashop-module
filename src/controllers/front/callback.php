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

use Soisy\Plugins\SoisyPlugin\Exceptions\LoanControllerException;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SoisycallbackModuleFrontController extends ModuleFrontController
{
    /** @var soisy */
    public $module;

    public function init()
    {
        // TODO: Authorization
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Type: application/json');
        http_response_code(200);

        $log = array(
            'TYPE' => 'CALLBACK',
            'IP' => $_SERVER['REMOTE_ADDR'],
            'POST_event_id' => Tools::getValue('eventId'),
            'POST_eventMessage' => Tools::getValue('eventMessage'),
            'POST_eventDate' => Tools::getValue('eventDate'),
            'POST_orderToken' => Tools::getValue('orderToken'),
        );
        SoisyUtility::doLog(json_encode($log, JSON_PRETTY_PRINT));

        $eventId = Tools::getValue('eventId');
        $orderToken = Tools::getValue('orderToken');
        $loanTokenSaved = $orderToken;

        switch ($eventId) {
            case 'LoanWasApproved':
                try {
                    $this->module->loanController->requestPreAuthorized($orderToken, $log , $loanTokenSaved);
                    echo 'ok';
                } catch (LoanControllerException $e) {
                    http_response_code(500);
                    echo $this->module->l('Error') . ': ' . $e->getMessage();
                } catch (Exception $e) {
                    http_response_code(500);
                    echo $this->module->l('Error occurred');
                }
                break;

            case 'RequestCompleted':
                /* Temporarily deactivated
                try {
                    $this->module->loanController->requestCompleted($orderToken, $log);
                    echo 'ok';
                } catch (LoanControllerException $e) {
                    http_response_code(500);
                    echo $this->module->l('Error') . ': ' . $e->getMessage();
                } catch (Exception $e) {
                    http_response_code(500);
                    echo $this->module->l('Error occurred');
                }
                */
                echo 'ok';
                break;

            case 'LoanWasVerified':
                try {
                    $this->module->loanController->loanApproved($orderToken, $log);
                    echo 'ok';
                } catch (LoanControllerException $e) {
                    http_response_code(500);
                    echo $this->module->l('Error') . ': ' . $e->getMessage();
                } catch (Exception $e) {
                    http_response_code(500);
                    echo $this->module->l('Error occurred');
                }
                break;

            case 'LoanWasDisbursed':
                try {
                    $amoutInCents = (int)Tools::getValue('amount');
                    $untrimmedAmount = (float)($amoutInCents / 100);
                    $amount = Tools::ps_round($untrimmedAmount, 2);
                    $this->module->loanController->paidLoan($orderToken, $amount, $log);
                    echo 'ok';
                } catch (LoanControllerException $e) {
                    http_response_code(500);
                    echo $this->module->l('Error') . ': ' . $e->getMessage();
                } catch (Exception $e) {
                    http_response_code(500);
                    echo $this->module->l('Error occurred');
                }
                break;

            case 'UserWasRejected':
                try {
                    $this->module->loanController->loanRejected($orderToken, $log);
                    echo 'ok';
                } catch (LoanControllerException $e) {
                    http_response_code(500);
                    echo $this->module->l('Error') . ': ' . $e->getMessage();
                } catch (Exception $e) {
                    http_response_code(500);
                    echo $this->module->l('Error occurred');
                }
                break;

            default:
                SoisyUtility::doLog('NOT FOUND: unknown eventId=' . $eventId);
                break;
        }

        exit;
    }
}
