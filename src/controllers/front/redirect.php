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

class SoisyredirectModuleFrontController extends ModuleFrontController
{
    /** @var soisy */
    public $module;

    public function initContent()
    {
        parent::initContent();

        $context = Context::getContext();
        if (!$this->module->isModuleUsable($context->cart)) {
            exit;
        }

        $log = array(
            'TYPE' => 'REQUEST',
            'IP' => $_SERVER['REMOTE_ADDR'],
        );
        SoisyUtility::doLog(json_encode($log, JSON_PRETTY_PRINT));

        try {
            $redirectUrl = $this->module->loanController->createLoanFromCurrentCart($log);
            Tools::redirect($redirectUrl);
        } catch (LoanControllerException $e) {
            $context->smarty->assign('error_message', $e->getMessage());
            SoisyUtility::doLog($e->getMessage());
        } catch (Exception $e) {
            $context->smarty->assign('error_message', 'Error occurred');
            SoisyUtility::doLog($e->getMessage());
        }

        if ($this->module->psVersion > 16) {
            $this->setTemplate('module:' . $this->module->name . '/views/templates/front/17/pre-payment-stopped.tpl');
        } else {
            $this->setTemplate('16/pre-payment-stopped.tpl');
        }
    }
}
